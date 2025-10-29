<?php

namespace App\Filament\Resources;

use App\Filament\Resources\NewsResource\Pages;
use App\Filament\Resources\NewsResource\RelationManagers;
use App\Models\News;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;

class NewsResource extends Resource
{
    protected static ?string $model = News::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Official';

    protected static ?int $navigationSort = 34;

    protected static ?string $navigationLabel = 'ニュース';

    protected static ?string $modelLabel = 'ニュース';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('title')->label('タイトル'),
                Toggle::make('visible')
                    ->label('公開')
                    ->onColor('success')
                    ->dehydrateStateUsing(fn($state) => $state ? 0 : 1) // 保存時に 0 と 1 を逆にする
                    ->formatStateUsing(fn($state) => $state == 0), // 表示時に 0 を true（ON）、1 を false（OFF）にするÏ
                RichEditor::make('text')
                    ->label('本文')
                    ->required(),
                DatePicker::make('date')
                    ->label(__('公開日'))
                    ->native(false)
                    ->displayFormat('Y.m.d')
                    ->required(),
                FileUpload::make('image')
                    ->label('画像')
                    ->image()
                    ->preserveFilenames(false) // Cloudinary にアップロードするときは false 推奨
                    ->disk('cloudinary') // Cloudinary を指定
                    ->directory('images') // Cloudinary のフォルダ名
                    ->visibility('public'), // 画像を公開
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('ID'),
                TextColumn::make('title')->label('タイトル'),
                TextColumn::make('date')->date('Y.m.d')->label('公開日'),
                Tables\Columns\ToggleColumn::make('visible')
                    ->label('公開')
                    ->onColor('success')
                    ->offColor('danger')
                    ->beforeStateUpdated(function ($record, $state) {
                        return $state ? 0 : 1; // Toggle時に0と1を逆にする
                    })
                    ->getStateUsing(fn ($record) => $record->visible == 0) // 0を公開（ON）として表示
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
            'index' => Pages\ListNews::route('/'),
            'create' => Pages\CreateNews::route('/create'),
            'edit' => Pages\EditNews::route('/{record}/edit'),
        ];
    }
}
