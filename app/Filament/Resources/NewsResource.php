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
                TextInput::make('title')
                    ->label('タイトル')
                    ->required(),
                Toggle::make('hidden')
                    ->label('公開')
                    ->onColor('success')
                    ->offColor('gray')
                    ->default(0)
                    ->formatStateUsing(fn ($state) => $state == 0)
                    ->dehydrateStateUsing(fn ($state) => $state ? 0 : 1),
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
                TextColumn::make('title')
                    ->label('タイトル')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('date')
                    ->label('公開日')
                    ->date('Y.m.d')
                    ->sortable(),
                Tables\Columns\ToggleColumn::make('hidden')
                    ->label('公開')
                    ->onColor('success')
                    ->offColor('gray')
                    ->getStateUsing(fn ($record) => $record->hidden == 0) // 0を公開（ON）として表示
                    ->updateStateUsing(function ($record, $state) {
                        $record->update(['hidden' => $state ? 0 : 1]); // ONを0、OFFを1として保存
                        return $state;
                    }),
            ])
            ->filters([
                //
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
