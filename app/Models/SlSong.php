<?php

namespace App\Models;

use App\Support\SongTitleNormalizer;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SlSong extends Model
{
    protected $table = 'sl_songs';

    use HasFactory;

    protected $fillable = [
        'title',
        'artist_id',
        'db_song_id',
    ];

    protected static function booted()
    {
        static::saving(function (SlSong $song) {
            if (!$song->artist_id || !$song->title) {
                return;
            }

            // db_song_idが未設定なら常に自動照合する（新規登録時など）。
            // 既に紐付いている場合は、タイトルが今回変更されていない限り再照合しない
            // （db_song_id自体を手動で選び直した操作を、無関係な自動照合で上書きしないため）。
            //
            // タイトルが変更された場合は、既存の紐付け先が新タイトルとまだ一致しているかを
            // 確認し、一致しなくなっていれば新タイトルで候補を再検索して張り替える。これが
            // ないと「タイトルだけ変えてdb_song_idはそのまま保存」した際、紐付け先DbSongの
            // タイトルとは一致しなくなっているのに何もチェックされず古い紐付けが残り続ける
            // （実際にSlSong「とうとい」がDbSong「WINNER」に紐付いたまま残る事故として発生した）。
            if ($song->db_song_id) {
                if (!$song->isDirty('title')) {
                    return;
                }

                $currentDbSong = DbSong::find($song->db_song_id);
                if ($currentDbSong
                    && SongTitleNormalizer::normalize($currentDbSong->title) === SongTitleNormalizer::normalize($song->title)
                ) {
                    return;
                }
            }

            $normalizedTitle = SongTitleNormalizer::normalize($song->title);
            // DbSong側は1曲に複数バージョンのSlSongを束ねる正当なケースがあるため制約しないが、
            // SlSong側から見ると基本1対1（1つのSlSongが指すDbSongは1つ）であるべきなので、
            // 「既に自分以外の別のSlSongが同じ組み合わせで紐付いている」DbSongは自動紐付けの
            // 対象から除外する。ここを見ずに1件と判定すると、既存の紐付けを問答無用で奪って
            // しまう（実際にWith You/Show Me Your LoveがEXIT/With youの紐付けを奪い合う
            // 事故として発生した）。
            $candidates = DbSong::where('artist_id', $song->artist_id)->get(['id', 'title']);
            $titleMatches = $candidates->filter(
                fn(DbSong $candidate) => SongTitleNormalizer::normalize($candidate->title) === $normalizedTitle
            );
            $matches = $titleMatches->filter(
                fn(DbSong $candidate) => !SlSong::where('db_song_id', $candidate->id)
                    ->where('id', '!=', $song->id)
                    ->exists()
            );

            if ($matches->count() === 1) {
                $song->db_song_id = $matches->first()->id;
                return;
            }

            // 候補が2件以上で自動では決められない場合、サイレントにスキップすると
            // 気づかれないまま未紐付けが放置されるため、管理画面上に通知する
            // （モデルイベントはArtisanコマンド等、通知先のUIが無い文脈からも発火するため、
            // 通知の送信だけはWeb/Livewireリクエスト内に限る。db_song_idの状態自体を
            // 変えない分岐なので、コンソール実行でもここは素通りしてよい）。
            if ($matches->count() > 1) {
                if (!app()->runningInConsole()) {
                    Notification::make()
                        ->warning()
                        ->title('database楽曲の自動紐付けが曖昧です')
                        ->body("「{$song->title}」に一致するdatabase楽曲が複数見つかったため、自動紐付けをスキップしました。手動で紐付けてください。")
                        ->persistent()
                        ->send();
                }
                return;
            }

            // タイトルには一致するが、既に他のSlSongに奪われているため除外された場合、
            // 「対応するDbSongが存在しない」通常ケースとは違い、データの取り合いが起きている
            // 異常な状態なので気づけるようにする。
            if ($titleMatches->count() > 0 && $matches->isEmpty() && !app()->runningInConsole()) {
                $stolenDbSong = $titleMatches->first();
                $stolenBy = SlSong::where('db_song_id', $stolenDbSong->id)->where('id', '!=', $song->id)->first();
                Notification::make()
                    ->warning()
                    ->title('database楽曲が既に別のセットリスト楽曲に紐付いています')
                    ->body("「{$song->title}」に一致するdatabase楽曲「{$stolenDbSong->title}」は既に別のセットリスト楽曲「"
                        . ($stolenBy?->title ?? '(不明)')
                        . '」に紐付いているため、自動紐付けできませんでした。')
                    ->persistent()
                    ->send();
            }

            // 候補が0件で、かつタイトル変更によって既存の紐付けと不一致になった場合、
            // このままでは「間違ったDbSongに紐付いたまま」残ってしまう。新しい紐付け先が
            // 見つからない以上、古い紐付けを保持し続ける理由はないため解除する
            // （db_song_idをnullにする処理自体はコンソール実行でも必ず行い、通知の送信だけを
            // Web/Livewireリクエスト内に限る）。
            if ($song->db_song_id) {
                $oldDbSong = DbSong::find($song->db_song_id);
                $song->db_song_id = null;
                if (!app()->runningInConsole()) {
                    Notification::make()
                        ->warning()
                        ->title('タイトル変更により紐付けを解除しました')
                        ->body("「{$song->title}」は、これまで紐付いていたdatabase楽曲「"
                            . ($oldDbSong?->title ?? '(不明)')
                            . '」とタイトルが一致しなくなったため、紐付けを解除しました。新しい紐付け先を手動で選んでください。')
                        ->persistent()
                        ->send();
                }
            }
        });
    }

    public function artist()
    {
        return $this->belongsTo(Artist::class);
    }

    public function dbSong()
    {
        return $this->belongsTo(DbSong::class);
    }

    // この曲が含まれるセットリスト（fes の block の中も見る。文字のまま入っている曲はタイトルで照合）。新しい順
    public function performedSetlists()
    {
        $id = $this->id;
        $title = $this->title;

        return SlSetlist::all()->filter(function ($setlist) use ($id, $title) {
            // setlist フィールドをチェック
            $setlistArr = is_array($setlist->setlist)
                ? $setlist->setlist
                : json_decode($setlist->setlist ?? '[]', true);

            // encore フィールドをチェック
            $encoreArr = is_array($setlist->encore)
                ? $setlist->encore
                : json_decode($setlist->encore ?? '[]', true);

            // fes_setlist フィールドをチェック
            $fesSetlistArr = is_array($setlist->fes_setlist)
                ? $setlist->fes_setlist
                : json_decode($setlist->fes_setlist ?? '[]', true);

            // fes_encore フィールドをチェック
            $fesEncoreArr = is_array($setlist->fes_encore)
                ? $setlist->fes_encore
                : json_decode($setlist->fes_encore ?? '[]', true);

            // fes_setlist/fes_encoreのblockアイテムを展開
            $expandFes = function ($items) {
                $result = [];
                foreach ($items as $item) {
                    if (($item['type'] ?? 'song') === 'block') {
                        foreach ($item['songs'] ?? [] as $s) {
                            $result[] = $s;
                        }
                    } else {
                        $result[] = $item;
                    }
                }
                return $result;
            };

            $allLists = array_merge($setlistArr, $encoreArr, $expandFes($fesSetlistArr), $expandFes($fesEncoreArr));

            foreach ($allLists as $entry) {
                // 数値IDの場合：SetlistSongのIDと一致するか
                if (is_numeric($entry['song'] ?? null) && (int)$entry['song'] === (int)$id) {
                    return true;
                }

                // 文字列の場合：タイトルが一致するか（例外処理）
                if (!is_numeric($entry['song'] ?? null)) {
                    $entryTitle = preg_replace('/\s*\[[^\]]+\]/u', '', $entry['song'] ?? '');
                    if (trim($entryTitle) === $title) {
                        return true;
                    }
                }
            }
            return false;
        })
        ->sortByDesc('date')
        ->values();
    }

    // アーティストの曲を「聴いた順」（はじめてセットリストに出てきた公演の日付順、同じ公演ならセットリストの順）に並べた id。
    // 曲ページの Previous / Next と番号に使う。まだ聴いていない曲（これからの公演だけ・どこにも無い）は、そのあとに id 順
    public static function heardOrderIds(int $artistId): array
    {
        $songs = static::where('artist_id', $artistId)->orderBy('id')->get(['id', 'title']);
        $idSet = $songs->pluck('id')->flip();
        $idByTitle = $songs->pluck('id', 'title');
        $expandFes = fn ($items) => collect($items ?? [])->flatMap(fn ($item) => ($item['type'] ?? 'song') === 'block' ? ($item['songs'] ?? []) : [$item])->all();

        $order = [];
        $setlists = SlSetlist::where('date', '<=', now()->toDateString())->orderBy('date')->orderBy('id')->get();
        foreach ($setlists as $setlist) {
            $entries = array_merge((array) ($setlist->setlist ?? []), (array) ($setlist->encore ?? []), $expandFes($setlist->fes_setlist), $expandFes($setlist->fes_encore));
            foreach ($entries as $entry) {
                $song = $entry['song'] ?? null;
                if (is_numeric($song)) {
                    $id = isset($idSet[(int) $song]) ? (int) $song : null;
                } else {
                    // 文字列のまま入っている曲は、曲名が一致すればその曲（performedSetlists と同じ）
                    $id = $idByTitle[trim(preg_replace('/\s*\[[^\]]+\]/u', '', (string) $song))] ?? null;
                }
                if ($id !== null && !isset($order[$id])) {
                    $order[$id] = true;
                }
            }
        }

        return array_merge(array_keys($order), $songs->pluck('id')->reject(fn ($id) => isset($order[$id]))->values()->all());
    }
}
