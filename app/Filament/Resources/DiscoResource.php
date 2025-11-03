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
                    ->label('タイトル')
                    ->required()
                    ->maxLength(191),
                Forms\Components\TextInput::make('subtitle')
                    ->label('サブタイトル')
                    ->maxLength(191),
                Forms\Components\DatePicker::make('date')
                    ->label('発売日')
                    ->native(false)
                    ->displayFormat('Y.m.d'),
                Forms\Components\Toggle::make('type')
                    ->label('アルバム')
                    ->onColor('success')
                    ->offColor('gray'),
                Forms\Components\Toggle::make('visible')
                    ->label('公開')
                    ->onColor('success')
                    ->offColor('gray')
                    ->default(1)
                    ->formatStateUsing(fn($state) => $state == 1)
                    ->dehydrateStateUsing(fn($state) => $state ? 1 : 0),
                Forms\Components\Repeater::make('tracklist')
                    ->label('収録曲')
                    ->schema([
                        // 曲名（横幅いっぱい）
                        Forms\Components\Select::make('id')
                            ->label('曲名')
                            ->options(fn() => \App\Models\Lyric::pluck('title', 'id'))
                            ->searchable()
                            ->native(false)
                            ->columnSpanFull(),

                        // 折りたたみ「詳細設定」に例外を入れる（横幅いっぱい）
                        Forms\Components\Section::make('詳細設定')
                            ->collapsible()
                            ->collapsed() // 初期状態で閉じる
                            ->schema([
                                Forms\Components\TextInput::make('exception')
                                    ->label('例外 (バージョン違いなど)')
                                    ->columnSpanFull(),
                            ])
                            ->columnSpanFull(),
                    ])
                    ->columns(1) // 1列構成（曲名 → 詳細設定）
                    ->defaultItems(1)
                    ->reorderable()
                    ->itemLabel(function ($state, $component): string {
                        $items = array_values($component->getState() ?: []);
                        foreach ($items as $i => $item) {
                            if ($item === $state) {
                                return (string) ($i + 1);
                            }
                        }
                        return (string) (count($items) ?: 1);
                    })
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('text')
                    ->label('テキスト')
                    ->rows(5)
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('url')
                    ->label('URL')
                    ->maxLength(255),
                Forms\Components\FileUpload::make('image')
                    ->label('画像')
                    ->image()
                    ->disk('cloudinary')
                    ->directory('images')
                    ->visibility('public'),
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
                    ->label('タイトル')
                    ->searchable(),
                Tables\Columns\TextColumn::make('subtitle')
                    ->label('サブタイトル')
                    ->searchable(),
                Tables\Columns\TextColumn::make('date')
                    ->label('発売日')
                    ->date('Y.m.d')
                    ->sortable(),
                Tables\Columns\ToggleColumn::make('type')
                    ->label('アルバム')
                    ->onColor('success')
                    ->offColor('gray'),
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
                Tables\Filters\SelectFilter::make('type')
                    ->label('種別')
                    ->options([
                        0 => 'シングル',
                        1 => 'アルバム',
                    ]),
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
