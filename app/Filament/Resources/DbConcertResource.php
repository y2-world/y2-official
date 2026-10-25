<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DbConcertResource\Pages;
use App\Models\DbConcert;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Textarea;
use App\Support\AdminData;

class DbConcertResource extends Resource
{
    protected static ?string $model = DbConcert::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar';

    protected static ?string $navigationLabel = 'ツアー・ライブ情報';

    protected static ?string $modelLabel = 'ツアー・ライブ情報';

    protected static ?string $navigationGroup = 'Database';

    protected static ?int $navigationSort = 20;

    // 「ap bank fes」「ソロ」タイプはMr.Children専用の選択肢
    private const MR_CHILDREN_ARTIST_ID = 2;

    // 公式の Mr.Children か（マイページのアーティストの id とは別物なので、公式のテーブルのときだけ）
    private static function isMrChildren($artistId): bool
    {
        return !AdminData::isMember() && (int) $artistId === self::MR_CHILDREN_ARTIST_ID;
    }

    // 管理者は公式のツアー、マイページの人は自分のツアー（AdminData）
    public static function getModel(): string
    {
        return AdminData::model('concert');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('基本情報')
                    ->schema([
                        Forms\Components\Select::make('artist_id')
                            ->label('アーティスト')
                            ->options(fn() => AdminData::artistOptions())
                            // 選択肢（読み書きしてよいアーティスト）にあるものだけ
                            ->in(fn () => array_keys(AdminData::artistOptions()))
                            ->required()
                            ->native(false)
                            ->searchable()
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                // 「ap bank fes」「ソロ」はMr.Children専用の選択肢のため、
                                // Mr.Children以外に切り替えた際は選択済みならリセットする
                                if (!self::isMrChildren($state) && in_array((int) $get('type'), [3, 4], true)) {
                                    $set('type', 0);
                                }
                            })
                            ->columnSpanFull(),
                        TextInput::make('title')
                            ->label('タイトル')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Radio::make('type')
                            ->label('タイプ')
                            ->options(fn(Forms\Get $get) => self::isMrChildren($get('artist_id'))
                                ? [
                                    0 => 'ツアー',
                                    1 => '単発ライブ',
                                    2 => 'イベント',
                                    3 => 'ap bank fes',
                                    4 => 'ソロ',
                                ]
                                : [
                                    0 => 'ツアー',
                                    1 => '単発ライブ',
                                    2 => 'イベント',
                                ])
                            ->default(0)
                            ->required()
                            ->live()
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
                            ->options(function (Forms\Get $get) {
                                // 今の会場（まだどこにも無い会場でも出す）
                                $venues = collect(filled($get('venue')) ? [$get('venue')] : []);

                                // Setlistから会場を取得（Yuki's Setlists は公式だけ）
                                $setlistVenues = AdminData::supports('slSong') ? \App\Models\SlSetlist::query()
                                    ->whereNotNull('venue')
                                    ->where('venue', '!=', '')
                                    ->distinct()
                                    ->pluck('venue') : collect();

                                // Tourから会場を取得（マイページの人は自分のツアー）
                                $tourVenues = AdminData::query('concert')
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
                            ->label('テキスト')
                            ->rows(10)
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('artist.name')
                    ->label('アーティスト')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('title')
                    ->label('タイトル')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->label('タイプ')
                    ->formatStateUsing(fn ($state) => match((int) $state) {
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
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('artist_id')
                    ->label('アーティスト')
                    ->options(fn() => AdminData::artistOptions())
                    // マイページのテーブルは項目名が違うので、どちらのモデルにもある ofArtist で絞る
                    ->query(fn ($query, array $data) => filled($data['value']) ? $query->ofArtist($data['value']) : $query)
                    ->searchable(),
                Tables\Filters\SelectFilter::make('type')
                    ->label('タイプ')
                    // ap bank fes・ソロは公式の Mr.Children だけのタイプなので、マイページの人には出さない
                    ->options(fn () => [0 => 'ツアー', 1 => '単発ライブ', 2 => 'イベント'] + (AdminData::isMember() ? [] : [3 => 'ap bank fes', 4 => 'ソロ'])),
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

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return AdminData::query('concert')
            ->orderBy('id', 'desc');
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
            'index' => Pages\ListDbConcerts::route('/'),
            'create' => Pages\CreateDbConcert::route('/create'),
            'edit' => Pages\EditDbConcert::route('/{record}/edit'),
        ];
    }
}
