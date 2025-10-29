<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DiscoResource\Pages;
use App\Filament\Resources\DiscoResource\RelationManagers;
use App\Models\Disco;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class DiscoResource extends Resource
{
    protected static ?string $model = Disco::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Official';

    protected static ?int $navigationSort = 31;

    protected static ?string $navigationLabel = 'ディスコグラフィー';

    protected static ?string $modelLabel = 'ディスコグラフィー';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('title')
                    ->required()
                    ->maxLength(191),
                Forms\Components\TextInput::make('subtitle')
                    ->maxLength(191),
                Forms\Components\DatePicker::make('date'),
                Forms\Components\TextInput::make('tracklist'),
                Forms\Components\Textarea::make('text')
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('url')
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('image')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('type')
                    ->numeric(),
                Forms\Components\TextInput::make('visible')
                    ->numeric()
                    ->default(0),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('title')
                    ->searchable(),
                Tables\Columns\TextColumn::make('subtitle')
                    ->searchable(),
                Tables\Columns\TextColumn::make('date')
                    ->date()
                    ->sortable(),
                Tables\Columns\ToggleColumn::make('type')
                    ->label('タイプ')
                    ->onColor('success')
                    ->offColor('gray'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('Y.m.d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime('Y.m.d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\ToggleColumn::make('visible')
                    ->label('公開')
                    ->onColor('success')
                    ->offColor('danger')
                    ->beforeStateUpdated(function ($record, $state) {
                        return $state ? 0 : 1;
                    })
                    ->getStateUsing(fn ($record) => $record->visible == 0),
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
            'index' => Pages\ListDiscos::route('/'),
            'create' => Pages\CreateDisco::route('/create'),
            'edit' => Pages\EditDisco::route('/{record}/edit'),
        ];
    }
}
