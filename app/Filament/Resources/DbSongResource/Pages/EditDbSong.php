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
            $originalExtraIds = $this->record->slSongs()->skip(1)->pluck('id')->all();
            $remainingIds = collect($data['extra_sl_songs'] ?? [])->pluck('id')->filter()->map(fn ($id) => (int) $id)->all();
            $this->extraSlSongIdsToDetach = array_diff($originalExtraIds, $remainingIds);
            unset($data['extra_sl_songs']);
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

    // ユーザーが選択欄で「別の特定のSlSongを選んだ」場合のみ、その差分を反映する。
    // それ以外（未操作、または明示的に空欄へクリアした場合）は、ここでは一切何もせず、
    // $record->save()の中で既に発火済みのDbSong::booted()のsavedイベント（自動紐付け）の
    // 結果をそのまま残す。
    //
    // 「空欄にクリアする」操作の意図は「今の紐付けを強制的に外す」ことではなく「自動照合に
    // 任せる」ことである。タイトル変更（例: EXIT→Little）と同時に空欄クリアした場合、
    // 自動紐付けは既に新タイトルに合う別のSlSong（Little）へ切り替え済みのはずで、
    // ここで「今のDB値(=Little) != フォームの値(=null)だから解除する」と判断すると、
    // 自動紐付けの結果を消してしまう。そのため「ユーザーが具体的な別の値を選んだ」
    // （slSongIdToSyncがnullでない）場合のみ、明示的な操作とみなして反映する。
    protected function afterSave(): void
    {
        if (!empty($this->extraSlSongIdsToDetach)) {
            SlSong::whereIn('id', $this->extraSlSongIdsToDetach)->update(['db_song_id' => null]);
        }

        if ($this->slSongIdToSync === false || $this->slSongIdToSync === null) {
            return;
        }

        if ($this->slSongIdToSync === $this->originalSlSongId) {
            return;
        }

        $currentSlSongId = $this->record->slSongs()->value('id');

        if ($currentSlSongId) {
            SlSong::where('id', $currentSlSongId)->update(['db_song_id' => null]);
        }

        SlSong::where('id', $this->slSongIdToSync)->update(['db_song_id' => $this->record->id]);
    }
}
