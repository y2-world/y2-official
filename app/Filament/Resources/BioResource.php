<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BioResource\Pages;
use App\Filament\Resources\BioResource\RelationManagers;
use App\Models\Bio;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class BioResource extends Resource
{
    protected static ?string $model = Bio::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Mr.Children Database';

    protected static ?int $navigationSort = 24;

    protected static ?string $navigationLabel = 'バイオグラフィー';

    protected static ?string $modelLabel = 'バイオグラフィー';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('year')
                    ->label('年')
                    ->numeric(),
                Forms\Components\Textarea::make('text')
                    ->label('テキスト')
                    ->rows(5)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('year')
                    ->label('年')
                    ->numeric(decimalPlaces: 0, thousandsSeparator: '')
                    ->sortable(),
                Tables\Columns\TextColumn::make('text')
                    ->label('テキスト')
                    ->limit(50),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('Y.m.d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime('Y.m.d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListBios::route('/'),
            'create' => Pages\CreateBio::route('/create'),
            'edit' => Pages\EditBio::route('/{record}/edit'),
        ];
    }
}
