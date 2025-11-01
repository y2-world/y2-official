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
use App\Models\Post;

class ArtistResource extends Resource
{
    protected static ?string $model = Artist::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Setlists';

    protected static ?int $navigationSort = 11;

    protected static ?string $navigationLabel = 'アーティスト';

    protected static ?string $modelLabel = 'アーティスト';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')->label('アーティスト名'),
                Toggle::make('hidden')
                    ->label('公開')
                    ->onColor('success')
                    ->offColor('gray')
                    ->default(0)
                    ->formatStateUsing(fn ($state) => $state == 0) // 0をON、1をOFFとして表示
                    ->dehydrateStateUsing(fn ($state) => $state ? 0 : 1) // ONを0、OFFを1として保存
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('ID'),
                TextColumn::make('name')->label('アーティスト'),
                Tables\Columns\ToggleColumn::make('hidden')
                    ->label('公開')
                    ->onColor('success')
                    ->offColor('gray')
                    ->getStateUsing(fn ($record) => $record->hidden == 0) // 0を公開（ON）として表示
                    ->updateStateUsing(function ($record, $state) {
                        $record->update(['hidden' => $state ? 0 : 1]); // Toggle時に0と1を逆にしてhiddenカラムを更新
                        return $state;
                    })
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
