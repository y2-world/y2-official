<?php

namespace App\Filament\Resources\VenuePrefectureResource\Pages;

use App\Filament\Resources\VenuePrefectureResource;
use App\Models\VenuePrefecture;
use Filament\Resources\Pages\ListRecords;

class ListVenuePrefectures extends ListRecords
{
    protected static string $resource = VenuePrefectureResource::class;

    // 開いたときに、セットリスト・参加記録に新しく出てきた会場を足す（都道府県は未設定のまま）
    public function mount(): void
    {
        VenuePrefecture::addMissingVenues();

        parent::mount();
    }
}
