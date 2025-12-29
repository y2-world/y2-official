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
use Filament\Forms\Components\DateTimePicker;

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
                TextInput::make('title')
                    ->label('タイトル')
                    ->required(),
                Toggle::make('visible')
                    ->label('公開')
                    ->onColor('success')
                    ->offColor('gray')
                    ->default(1)
                    ->formatStateUsing(fn($state) => $state == 1)
                    ->dehydrateStateUsing(fn($state) => $state ? 1 : 0),
                RichEditor::make('text')
                    ->label('本文')
                    ->required(),
                DatePicker::make('date')
                    ->label(__('公開日'))
                    ->native(false)
                    ->displayFormat('Y.m.d')
                    ->required(),
                DateTimePicker::make('published_at')
                    ->label('投稿日時')
                    ->native(false)
                    ->displayFormat('Y.m.d H:i')
                    ->seconds(false)
                    ->default(now()),
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
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                TextColumn::make('title')
                    ->label('タイトル')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('date')
                    ->label('公開日')
                    ->date('Y.m.d')
                    ->sortable(),
                TextColumn::make('published_at')
                    ->label('投稿日時')
                    ->dateTime('Y.m.d H:i')
                    ->sortable(),
                Tables\Columns\ToggleColumn::make('visible')
                    ->label('公開')
                    ->onColor('success')
                    ->offColor('gray')
                    ->getStateUsing(fn($record) => $record->visible == 1) // 1を公開（ON）として表示
                    ->updateStateUsing(function ($record, $state) {
                        $record->update(['visible' => $state ? 1 : 0]); // ONを1、OFFを0として保存
                        return $state;
                    }),
            ])
            ->filters([
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
                Tables\Actions\DeleteAction::make(),
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
