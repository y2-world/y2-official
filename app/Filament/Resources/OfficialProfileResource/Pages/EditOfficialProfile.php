<?php

namespace App\Filament\Resources\OfficialProfileResource\Pages;

use App\Filament\Resources\OfficialProfileResource;
use App\Models\OfficialProfile;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditOfficialProfile extends EditRecord
{
    protected static string $resource = OfficialProfileResource::class;

    public function mount($record = null): void
    {
        // 常にID=1のレコードを編集
        $profile = OfficialProfile::firstOrCreate(['id' => 1]);
        parent::mount($profile->id);
    }

    protected function getHeaderActions(): array
    {
        return [
            // 削除アクションを削除
        ];
    }
}
