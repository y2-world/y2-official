<?php

namespace App\Filament\Resources\ProfileResource\Pages;

use App\Filament\Resources\ProfileResource;
use App\Models\Profile;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProfile extends EditRecord
{
    protected static string $resource = ProfileResource::class;

    public function mount($record = null): void
    {
        // 常にID=1のレコードを編集
        $profile = Profile::firstOrCreate(['id' => 1]);
        parent::mount($profile->id);
    }

    protected function getHeaderActions(): array
    {
        return [
            // 削除アクションを削除
        ];
    }
}
