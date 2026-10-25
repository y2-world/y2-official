<?php

namespace App\Filament\Concerns;

use App\Support\AdminData;
use Illuminate\Database\Eloquent\Model;

// 管理者だけの画面（マイページの人には、メニューにも出さず、開けないようにする）
trait AdminOnly
{
    public static function canViewAny(): bool
    {
        return !AdminData::isMember() && parent::canViewAny();
    }

    public static function canCreate(): bool
    {
        return !AdminData::isMember() && parent::canCreate();
    }

    public static function canEdit(Model $record): bool
    {
        return !AdminData::isMember() && parent::canEdit($record);
    }

    public static function canDelete(Model $record): bool
    {
        return !AdminData::isMember() && parent::canDelete($record);
    }

    public static function canDeleteAny(): bool
    {
        return !AdminData::isMember() && parent::canDeleteAny();
    }
}
