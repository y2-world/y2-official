<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProfileResource\Pages;
use App\Filament\Resources\ProfileResource\RelationManagers;
use App\Models\Profile;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ProfileResource extends Resource
{
    protected static ?string $model = Profile::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Official';

    protected static ?int $navigationSort = 30;

    protected static ?string $navigationLabel = 'プロフィール';

    protected static ?string $modelLabel = 'プロフィール';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('名前')
                    ->maxLength(255),

                Forms\Components\TextInput::make('info')
                    ->label('生年月日・出身地')
                    ->maxLength(255),

                Forms\Components\Textarea::make('text')
                    ->label('本文')
                    ->rows(10),

                Forms\Components\FileUpload::make('image')
                    ->label('画像')
                    ->image()
                    ->disk('cloudinary')
                    ->directory('images'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('名前')
                    ->searchable(),
                Tables\Columns\TextColumn::make('info')
                    ->label('生年月日・出身地')
                    ->searchable(),
                Tables\Columns\ImageColumn::make('image')
                    ->label('画像'),
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
            'index' => Pages\EditProfile::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }
}
