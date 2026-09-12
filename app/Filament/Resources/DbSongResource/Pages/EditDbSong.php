<?php

namespace App\Filament\Resources\DbSongResource\Pages;

use App\Filament\Resources\DbSongResource;
use App\Models\SlSong;
use App\Support\SongTitleNormalizer;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\ValidationException;

class EditDbSong extends EditRecord
{
    protected static string $resource = DbSongResource::class;

    // Filament標準のrelationship()保存はHasMany + multiple()に対応していないため、
    // sl_song_idはモデルのfillableに含めず、この一時プロパティ経由で自前で同期する。
    //
    // 注意: fillForm()（マウント時、GETリクエスト）で取得した値をprotectedプロパティに
    // 保持しておく方式は使えない。Livewireはpublicプロパティのみをリクエスト間で
    // シリアライズ/復元するため、保存アクション（別のPOSTリクエスト）の時点では
    // protectedプロパティはデフォルト値にリセットされてしまう。
    // そのため「フォームを開いた時点の紐付け」の代わりに、保存直前（モデルのsave()より前、
    // 同一リクエスト内）にDBから読み直した値を「操作前の状態」として使う。
    protected int|false|null $slSongIdToSync = false;

    protected ?int $originalSlSongId = null;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    // extra_sl_songsリピーターから消された（＝解除ボタンを押された）SlSongのidを保持し、
    // afterSaveで実際にdb_song_idをnullにする。
    protected array $extraSlSongIdsToDetach = [];

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->originalSlSongId = $this->record->slSongs()->value('id');

        if (array_key_exists('extra_sl_songs', $data)) {
            // slSongs()（クエリビルダー）へのskip(1)はOFFSET句のみになりMySQLの構文エラーになるため、
            // 既にロード済みのslSongs（Eloquentコレクション）側のskip(1)（配列操作）を使う。
            $originalExtraIds = $this->record->slSongs->skip(1)->pluck('id')->all();
            $remainingIds = collect($data['extra_sl_songs'] ?? [])->pluck('id')->filter()->map(fn ($id) => (int) $id)->all();
            $this->extraSlSongIdsToDetach = array_diff($originalExtraIds, $remainingIds);
            unset($data['extra_sl_songs']);

            // ここ（$record->save()より前）で解除する。afterSaveまで待つと、
            // $record->save()の中で発火するDbSong::booted()のsavedイベント（タイトル一致の
            // 自動照合）が、まだ解除されていない（db_song_idが埋まったままの）SlSongを
            // whereNull('db_song_id')の候補から除外してしまい、本来紐付くべきだったものが
            // 見つからないまま自動照合が失敗してしまう
            // （実際に「Show Me Your Love」を空欄クリア + 「With You」の紐付き解除を同時に
            // 行った際、Withyouがまだ紐付いたままの状態で自動照合が走り、結局どちらも
            // 未紐付けのまま終わってしまう事故として発生した）。
            if (!empty($this->extraSlSongIdsToDetach)) {
                SlSong::whereIn('id', $this->extraSlSongIdsToDetach)->update(['db_song_id' => null]);
            }
        }

        $this->slSongIdToSync = array_key_exists('sl_song_id', $data)
            ? (int) $data['sl_song_id'] ?: null
            : false;

        // ユーザーが実際に選択を変えようとしている場合のみ、選んだSlSongが既に
        // 「別の」DbSongに紐付いていないかを確認する。ここでサイレントに奪ってしまうと、
        // 奪われた側のDbSongが気づかないまま未紐付けに戻ってしまう事故が起きるため、
        // 保存自体を中断してフォームにエラー表示する。
        if ($this->slSongIdToSync !== false
            && $this->slSongIdToSync !== null
            && $this->slSongIdToSync !== $this->originalSlSongId
        ) {
            $conflicting = SlSong::where('id', $this->slSongIdToSync)
                ->whereNotNull('db_song_id')
                ->where('db_song_id', '!=', $this->record->id)
                ->first();

            if ($conflicting) {
                $conflictingDbSong = $conflicting->dbSong;
                throw ValidationException::withMessages([
                    'data.sl_song_id' => "このセットリスト楽曲「{$conflicting->title}」は既に別の楽曲「"
                        . ($conflictingDbSong?->title ?? '(id: ' . $conflicting->db_song_id . ')')
                        . '」に紐付いています。先にそちらの紐付けを解除してください。',
                ]);
            }
        }

        // 紐付け欄を空欄にして保存した場合（＝自動照合に任せる場合）、保存後のsavedイベントで
        // 初めて自動照合が走るため、そこで失敗しても保存自体は止められない。
        // タイトル変更と紐付け解除を同時に行うワークフロー（順番を間違えて登録した曲を後から
        // 直す場合など）でこの失敗が起きると気づかれないまま終わってしまうため、
        // 保存前の時点で「新しいタイトルに一致するSlSongがあるが、既に別のDbSongに奪われている」
        // ケースを検出し、保存自体をエラーで止める。
        if ($this->slSongIdToSync === null && !empty($data['title'])) {
            $artistId = $data['artist_id'] ?? $this->record->artist_id;
            $normalizedTitle = SongTitleNormalizer::normalize($data['title']);

            $stolenByOther = SlSong::where('artist_id', $artistId)
                ->whereNotNull('db_song_id')
                ->where('db_song_id', '!=', $this->record->id)
                ->get(['id', 'title', 'db_song_id'])
                ->first(fn (SlSong $candidate) => SongTitleNormalizer::normalize($candidate->title) === $normalizedTitle);

            if ($stolenByOther) {
                $owner = $stolenByOther->dbSong;
                throw ValidationException::withMessages([
                    'data.sl_song_id' => "タイトルが一致するセットリスト楽曲「{$stolenByOther->title}」は既に別の楽曲「"
                        . ($owner?->title ?? '(id: ' . $stolenByOther->db_song_id . ')')
                        . '」に紐付いているため、自動紐付けできません。先にそちらの紐付けを解除するか、直接選択してください。',
                ]);
            }
        }

        unset($data['sl_song_id']);

        return $data;
    }

    // ユーザーが選択欄で「別の特定のSlSongを選んだ」場合は、その差分を反映する。
    // 「空欄にクリアした」場合は、まず自動照合（DbSong::booted()のsavedイベント、
    // $record->save()の中で既に発火済み）の結果を尊重する。
    //
    // ただし「自動照合の結果を尊重する」のは、それが何かを実際に変えられた場合のみ。
    // 自動照合は「未紐付けの候補が1件だけ見つかれば新たに紐付ける」ことしかせず、
    // 「既存の紐付けを外す」処理を持たないため、候補が見つからなかった（0件・複数件・
    // 既に他に奪われている）場合は保存前の紐付け（originalSlSongId）がそのまま残ってしまう。
    // これでは「空欄にして保存」という操作が何も反映されない結果になるため、保存後も
    // 紐付け先がoriginalSlSongIdのまま変化していなければ、明示的に解除する。
    protected function afterSave(): void
    {
        if ($this->slSongIdToSync !== false && $this->slSongIdToSync !== $this->originalSlSongId) {
            if ($this->slSongIdToSync === null) {
                // originalSlSongIdが今もこのDbSongに紐付いたままなら、自動照合は何も変えられなかった
                // ということなので明示的に解除する。record->slSongs()->value('id')（順序未指定の
                // 先頭1件）で判定すると、複数紐付いている間はどのidが返るか不定で誤判定するため、
                // originalSlSongId自体がまだ紐付いているかを直接確認する。
                if ($this->originalSlSongId
                    && SlSong::where('id', $this->originalSlSongId)->where('db_song_id', $this->record->id)->exists()
                ) {
                    SlSong::where('id', $this->originalSlSongId)->update(['db_song_id' => null]);
                }
            } else {
                $currentSlSongId = $this->record->slSongs()->value('id');

                if ($currentSlSongId) {
                    SlSong::where('id', $currentSlSongId)->update(['db_song_id' => null]);
                }

                SlSong::where('id', $this->slSongIdToSync)->update(['db_song_id' => $this->record->id]);
            }
        }

        // sl_song_id・extra_sl_songsはモデルのfillableではなくこのページで独自に同期している
        // フィールドのため、上記の更新（自動紐付けの再照合結果や手動同期の結果）はFilament標準の
        // 「保存後にフォームへ書き戻す」対象に含まれない。何もしないと、保存ボタンを押しただけでは
        // 画面上の紐付け表示が古いままになり、手動でページを再読み込みしない限り反映されない。
        // フォームを明示的に再fillしてこの画面全体を最新のDB状態で描き直す。
        $this->record->refresh();
        $this->fillForm();
    }
}
