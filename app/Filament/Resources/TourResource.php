<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TourResource\Pages;
use App\Models\Tour;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Textarea;

class TourResource extends Resource
{
    protected static ?string $model = Tour::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar';

    protected static ?string $navigationLabel = 'ツアー情報';

    protected static ?string $modelLabel = 'ツアー情報';

    protected static ?string $navigationGroup = 'Database';

    protected static ?int $navigationSort = 20;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('基本情報')
                    ->schema([
                        TextInput::make('title')
                            ->label('タイトル')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Radio::make('type')
                            ->label('タイプ')
                            ->options([
                                0 => 'ツアー',
                                1 => '単発ライブ',
                                2 => 'イベント',
                                3 => 'ap bank fes',
                                4 => 'ソロ',
                            ])
                            ->default(0)
                            ->required()
                            ->columnSpanFull(),

                        Forms\Components\DatePicker::make('date1')
                            ->label('開始日')
                            ->required()
                            ->native(false)
                            ->displayFormat('Y.m.d'),

                        Forms\Components\DatePicker::make('date2')
                            ->label('終了日')
                            ->native(false)
                            ->displayFormat('Y.m.d'),

                        Forms\Components\Select::make('venue')
                            ->label('会場')
                            ->searchable()
                            ->native(false)
                            ->options(function () {
                                $venues = collect();

                                // Setlistから会場を取得
                                $setlistVenues = \App\Models\Setlist::query()
                                    ->whereNotNull('venue')
                                    ->where('venue', '!=', '')
                                    ->distinct()
                                    ->pluck('venue');

                                // Tourから会場を取得
                                $tourVenues = \App\Models\Tour::query()
                                    ->whereNotNull('venue')
                                    ->where('venue', '!=', '')
                                    ->distinct()
                                    ->pluck('venue');

                                // マージしてソート
                                return $venues->merge($setlistVenues)
                                    ->merge($tourVenues)
                                    ->unique()
                                    ->sort()
                                    ->mapWithKeys(fn($v) => [$v => $v]);
                            })
                            ->createOptionForm([
                                Forms\Components\TextInput::make('venue')
                                    ->label('新しい会場名')
                                    ->required()
                                    ->maxLength(255),
                            ])
                            ->createOptionUsing(function (array $data): string {
                                return $data['venue'];
                            })
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('詳細情報')
                    ->schema([
                        Textarea::make('schedule')
                            ->label('スケジュール')
                            ->rows(10)
                            ->columnSpanFull(),

                        Textarea::make('text')
                            ->label('コメント')
                            ->rows(10)
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),

                Forms\Components\Placeholder::make('setlist_note')
                    ->label('セットリスト管理')
                    ->content('セットリストは「ツアーセットリスト」リソースで管理してください。')
                    ->columnSpanFull(),
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
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->label('タイプ')
                    ->formatStateUsing(fn ($state) => match($state) {
                        0 => 'ツアー',
                        1 => '単発ライブ',
                        2 => 'イベント',
                        3 => 'ap bank fes',
                        4 => 'ソロ',
                        default => '-',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('date1')
                    ->label('開始日')
                    ->date('Y.m.d')
                    ->sortable(),
                Tables\Columns\TextColumn::make('date2')
                    ->label('終了日')
                    ->date('Y.m.d')
                    ->sortable(),
                Tables\Columns\TextColumn::make('venue')
                    ->label('会場')
                    ->searchable()
                    ->limit(30),
                Tables\Columns\TextColumn::make('tourSetlists_count')
                    ->label('パターン数')
                    ->counts('tourSetlists')
                    ->suffix('個'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('タイプ')
                    ->options([
                        0 => 'ツアー',
                        1 => '単発ライブ',
                        2 => 'イベント',
                        3 => 'ap bank fes',
                        4 => 'ソロ',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('date1', 'desc');
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
            'index' => Pages\ListTours::route('/'),
            'create' => Pages\CreateTour::route('/create'),
            'edit' => Pages\EditTour::route('/{record}/edit'),
        ];
    }
}
