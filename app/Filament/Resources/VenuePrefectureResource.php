<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VenuePrefectureResource\Pages;
use App\Models\VenuePrefecture;
use App\Support\AdminData;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

// 会場の都道府県（Statistics の Map で、行った会場の都道府県を染める）。公式の管理者だけ
class VenuePrefectureResource extends Resource
{
    protected static ?string $model = VenuePrefecture::class;

    protected static ?string $navigationIcon = 'heroicon-o-map';

    protected static ?string $navigationGroup = 'General';

    protected static ?int $navigationSort = 13;

    protected static ?string $navigationLabel = '会場';

    protected static ?string $modelLabel = '会場';

    public static function canAccess(): bool
    {
        return !AdminData::isMember();
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('venue')->label('会場')->searchable()->sortable()->wrap(),
                // 一覧のまま選んで直す（海外は国の名前も入れる）
                Tables\Columns\SelectColumn::make('prefecture')
                    ->label('都道府県')
                    ->options(VenuePrefecture::NAMES)
                    ->placeholder('未設定'),
                Tables\Columns\TextInputColumn::make('country')
                    ->label('国')
                    ->disabled(fn (VenuePrefecture $record) => $record->prefecture !== VenuePrefecture::OVERSEAS),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('assigned')
                    ->label('都道府県')
                    ->placeholder('すべて')
                    ->trueLabel('設定済み')
                    ->falseLabel('未設定')
                    ->queries(
                        true: fn ($query) => $query->whereNotNull('prefecture'),
                        false: fn ($query) => $query->whereNull('prefecture'),
                    ),
                Tables\Filters\SelectFilter::make('prefecture')->label('都道府県で絞り込む')->options(VenuePrefecture::NAMES),
            ])
            // 登録した順の新しい順
            ->defaultSort('id', 'desc')
            ->paginated([25, 50, 100, 'all']);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVenuePrefectures::route('/'),
        ];
    }
}
