<?php

namespace App\Filament\Resources\SlSongResource\Pages;

use App\Filament\Resources\SlSongResource;
use App\Models\SlSong;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateSlSong extends CreateRecord
{
    protected static string $resource = SlSongResource::class;

    // 「保存して次を作成」の直後だけ、続けて同じアーティストの曲を登録しやすいようartist_idを引き継ぐ。
    // 一覧から改めて新規作成を開いた場合はここを通らないため、通常通り未選択に戻る。
    public function create(bool $another = false): void
    {
        $artistId = $another ? $this->form->getRawState()['artist_id'] ?? null : null;

        parent::create($another);

        if ($another && $artistId) {
            $this->form->fill(['artist_id' => $artistId]);
        }
    }

    // DbSong側は同じ曲の複数バージョンを1つのdatabase楽曲に束ねる正当なケースがあるため、
    // 複数紐付けそのものは禁止しない。ただしタイトルが違う曲を誤って同じdatabase楽曲に
    // 紐付けてしまう事故が過去に実際に起きているため、保存後に気づけるよう警告だけ出す。
    protected function afterCreate(): void
    {
        if (!$this->record->db_song_id) {
            return;
        }

        $siblingWithDifferentTitle = SlSong::where('db_song_id', $this->record->db_song_id)
            ->where('id', '!=', $this->record->id)
            ->where('title', '!=', $this->record->title)
            ->exists();

        if ($siblingWithDifferentTitle) {
            Notification::make()
                ->warning()
                ->title('タイトルが異なる曲が同じdatabase楽曲に紐付いています')
                ->body('紐付け先のdatabase楽曲を確認してください。意図しない紐付けの可能性があります。')
                ->persistent()
                ->send();
        }
    }
}
