<?php

namespace App\Filament\Resources\RadioResource\Pages;

use App\Filament\Resources\RadioResource;
use App\Models\Radio;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRadio extends EditRecord
{
    protected static string $resource = RadioResource::class;

    public function mount($record = null): void
    {
        // 常にID=1のレコードを編集
        $radio = Radio::firstOrCreate(['id' => 1]);
        parent::mount($radio->id);
    }

    protected function getHeaderActions(): array
    {
        return [
            // 削除アクションを削除
        ];
    }
}
