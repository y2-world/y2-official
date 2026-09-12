<?php

namespace App\Filament\Resources\DbSongResource\Pages;

use App\Filament\Resources\DbSongResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateDbSong extends CreateRecord
{
    protected static string $resource = DbSongResource::class;

    // Filament標準のrelationship()保存はHasMany + multiple()に対応していないため、
    // sl_song_idはモデルのfillableに含めず、この一時プロパティ経由で自前で同期する。
    protected ?int $slSongIdToSync = null;

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

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->slSongIdToSync = $data['sl_song_id'] ?? null;
        unset($data['sl_song_id']);

        return $data;
    }

    // 新規作成なので、この曲に紐付いていた既存のSlSongは存在しない前提で、
    // 選択されたものだけにdb_song_idをセットすれば足りる（nullリセットは不要＝事故の余地がない）。
    protected function afterCreate(): void
    {
        if ($this->slSongIdToSync) {
            \App\Models\SlSong::where('id', $this->slSongIdToSync)->update(['db_song_id' => $this->record->id]);
        }
    }
}
