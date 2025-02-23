<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ArtistResource\Pages;
use App\Filament\Resources\ArtistResource\RelationManagers;
use App\Artist;
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
use App\Models\Post;

class ArtistResource extends Resource
{
    protected static ?string $model = Artist::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')->label('タイトル'),
                Toggle::make('visible')
                    ->label('公開')
                    ->onColor('success')
                    ->dehydrateStateUsing(fn($state) => $state ? 0 : 1) // 保存時に 0 と 1 を逆にする
                    ->formatStateUsing(fn($state) => $state == 0), // 表示時に 0 を true（ON）、1 を false（OFF）にする
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('ID'),
                TextColumn::make('name')->label('アーティスト'),
                TextColumn::make('visible')
                    ->label('公開')
                    ->formatStateUsing(fn($state) => $state == 0 ? '公開' : '非公開') // 0 → 公開, 1 → 非公開
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make() // ここでDeleteアイコンを追加
                    ->requiresConfirmation() // 確認ダイアログを表示
                    ->action(fn(Artist $record) => $record->delete())
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
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
