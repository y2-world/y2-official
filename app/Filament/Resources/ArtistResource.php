<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ArtistResource\Pages;
use App\Filament\Resources\ArtistResource\RelationManagers;
use App\Models\Artist;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\Action;
use App\Support\AdminData;
use Illuminate\Database\Eloquent\Model;

class ArtistResource extends Resource
{
    protected static ?string $model = Artist::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Setlists';

    protected static ?int $navigationSort = 12;

    protected static ?string $navigationLabel = 'アーティスト';

    protected static ?string $modelLabel = 'アーティスト';

    // 管理者は公式のアーティスト、マイページの人は自分のアーティスト（AdminData）
    public static function getModel(): string
    {
        return AdminData::model('artist');
    }

    public static function getEloquentQuery(): Builder
    {
        return AdminData::query('artist');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')->label('アーティスト名')->required(),
                Toggle::make('visible')
                    ->label('公開')
                    ->onColor('success')
                    ->offColor('gray')
                    // 新しく作るとき：公式のアーティストは公開、マイページのアーティストはオフ（マイページで作るときと同じ）
                    ->default(fn () => AdminData::isMember() ? 0 : 1)
                    ->formatStateUsing(fn ($state) => $state == 1)
                    ->dehydrateStateUsing(fn ($state) => $state ? 1 : 0),
                // 全セットリスト登録済：印があるときだけスタンプ帳に未演奏の背景を出す（マイページの Manage My Artists と同じ）
                Toggle::make('setlists_complete')
                    ->label('全セットリスト登録済')
                    ->helperText('スタンプ画面で未演奏の曲は背景を斜線にします')
                    ->onColor('success')
                    ->offColor('gray'),
                // ソロプロジェクト：印を付けるとバンドを選べる。データベースのセットリストで、お互いの曲を選べる
                Forms\Components\Checkbox::make('_solo_project')
                    ->label('ソロプロジェクト')
                    ->columnSpanFull()
                    ->dehydrated(false)
                    ->live()
                    ->afterStateHydrated(fn (Forms\Components\Checkbox $component, ?Model $record) => $component->state(filled($record?->band_artist_id)))
                    ->afterStateUpdated(fn ($state, Forms\Set $set) => $state ? null : $set('band_artist_id', null)),
                Forms\Components\Select::make('band_artist_id')
                    ->label('アーティスト名')
                    ->columnStart(1)
                    ->options(fn (?Model $record) => AdminData::query('artist')
                        ->when($record, fn ($q) => $q->whereKeyNot($record->getKey()))
                        ->whereNull('band_artist_id')
                        ->orderBy('name')
                        ->pluck('name', 'id'))
                    ->searchable()
                    ->native(false)
                    ->required(fn (Forms\Get $get) => (bool) $get('_solo_project'))
                    ->hidden(fn (Forms\Get $get) => !$get('_solo_project'))
                    // 印を外したときもバンドを空にして保存する
                    ->dehydratedWhenHidden(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('ID'),
                TextColumn::make('name')->label('アーティスト'),
                Tables\Columns\ToggleColumn::make('visible')
                    ->label('公開')
                    ->onColor('success')
                    ->offColor('gray')
                    ->getStateUsing(fn($record) => $record->visible == 1) // 1を公開（ON）として表示
                    ->updateStateUsing(function ($record, $state) {
                        $record->update(['visible' => $state ? 1 : 0]); // ONを1、OFFを0として保存
                        return $state;
                    }),
                Tables\Columns\ToggleColumn::make('setlists_complete')
                    ->label('全セットリスト登録済')
                    ->onColor('success')
                    ->offColor('gray'),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('setlists_complete')
                    ->label('全セットリスト登録済'),
                Tables\Filters\TernaryFilter::make('visible')
                    ->label('公開状態')
                    ->placeholder('すべて')
                    ->trueLabel('公開')
                    ->falseLabel('非公開')
                    ->queries(
                        true: fn($query) => $query->where('visible', 1),
                        false: fn($query) => $query->where('visible', 0),
                    ),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make() // ここでDeleteアイコンを追加
                    ->requiresConfirmation() // 確認ダイアログを表示
                    ->action(fn($record) => $record->delete())
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('id', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListArtists::route('/'),
            'create' => Pages\CreateArtist::route('/create'),
            'edit' => Pages\EditArtist::route('/{record}/edit'),
        ];
    }
}
