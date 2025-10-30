<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SetlistResource\Pages;
use App\Models\Setlist;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SetlistResource extends Resource
{
    protected static ?string $model = Setlist::class;

    protected static ?string $navigationIcon = 'heroicon-o-musical-note';

    protected static ?string $navigationLabel = 'セットリスト';

    protected static ?string $modelLabel = 'セットリスト';

    protected static ?string $navigationGroup = 'Setlists';

    protected static ?int $navigationSort = 10;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('基本情報')
                    ->schema([
                        Forms\Components\Select::make('artist_id')
                            ->label('アーティスト')
                            ->relationship('artist', 'name')
                            ->searchable()
                            ->preload()
                            ->native(false),

                        Forms\Components\TextInput::make('title')
                            ->label('タイトル')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Forms\Components\DatePicker::make('date')
                            ->label('公演日')
                            ->required()
                            ->native(false)
                            ->displayFormat('Y.m.d'),

                        Forms\Components\Select::make('venue')
                            ->label('会場')
                            ->required()
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
                            }),

                        Forms\Components\Radio::make('fes')
                            ->label('イベント種別')
                            ->options([
                                0 => '単独ライブ',
                                1 => 'フェス',
                            ])
                            ->default(0)
                            ->reactive()
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                // 単独ライブの場合のセットリスト
                Forms\Components\Section::make('セットリスト（単独ライブ）')
                    ->schema([
                        Forms\Components\Repeater::make('setlist')
                            ->label('本編')
                            ->schema([
                                Forms\Components\Hidden::make('_uuid')
                                    ->default(fn () => \Illuminate\Support\Str::uuid()->toString()),
                                Forms\Components\TextInput::make('song')
                                    ->label('曲名')
                                    ->required()
                                    ->columnSpan(2),
                                Forms\Components\Toggle::make('medley')
                                    ->label('Medley')
                                    ->default(false)
                                    ->inline(false)
                                    ->columnSpan(1),
                            ])
                            ->columns(3)
                            ->defaultItems(0)
                            ->live()
                            ->reorderable()
                            ->itemLabel(function (array $state, $component): ?string {
                                $items = $component->getState();
                                if (!is_array($items)) return '1';

                                // UUIDで現在のアイテムを特定
                                $currentUuid = $state['_uuid'] ?? null;
                                if (!$currentUuid) return '?';

                                $number = 0;
                                foreach ($items as $item) {
                                    $isMedley = !empty($item['medley']);
                                    $uuid = $item['_uuid'] ?? null;

                                    if ($uuid === $currentUuid) {
                                        if ($isMedley) {
                                            return '';
                                        } else {
                                            $number++;
                                            return (string)$number;
                                        }
                                    }

                                    if (!$isMedley) {
                                        $number++;
                                    }
                                }

                                return '1';
                            })
                            ->addActionLabel('曲を追加')
                            ->columnSpanFull(),

                        Forms\Components\Repeater::make('encore')
                            ->label('アンコール')
                            ->schema([
                                Forms\Components\Hidden::make('_uuid')
                                    ->default(fn () => \Illuminate\Support\Str::uuid()->toString()),
                                Forms\Components\TextInput::make('song')
                                    ->label('曲名')
                                    ->required()
                                    ->columnSpan(2),
                                Forms\Components\Toggle::make('medley')
                                    ->label('Medley')
                                    ->default(false)
                                    ->inline(false)
                                    ->columnSpan(1),
                            ])
                            ->columns(3)
                            ->defaultItems(0)
                            ->live()
                            ->reorderable()
                            ->itemLabel(function (array $state, $component, Forms\Get $get): ?string {
                                // 本編のsetlistから曲数を取得
                                $setlistItems = $get('setlist') ?? [];
                                $setlistCount = 0;
                                foreach ($setlistItems as $item) {
                                    if (empty($item['medley'])) {
                                        $setlistCount++;
                                    }
                                }

                                $items = $component->getState();
                                if (!is_array($items)) return (string)($setlistCount + 1);

                                // UUIDで現在のアイテムを特定
                                $currentUuid = $state['_uuid'] ?? null;
                                if (!$currentUuid) return '?';

                                $number = $setlistCount;
                                foreach ($items as $item) {
                                    $isMedley = !empty($item['medley']);
                                    $uuid = $item['_uuid'] ?? null;

                                    if ($uuid === $currentUuid) {
                                        if ($isMedley) {
                                            return '';
                                        } else {
                                            $number++;
                                            return (string)$number;
                                        }
                                    }

                                    if (!$isMedley) {
                                        $number++;
                                    }
                                }

                                return (string)($setlistCount + 1);
                            })
                            ->addActionLabel('曲を追加')
                            ->columnSpanFull(),
                    ])
                    ->visible(fn (Forms\Get $get) => $get('fes') == 0)
                    ->collapsible()
                    ->columnSpanFull(),

                // フェスの場合のセットリスト
                Forms\Components\Section::make('セットリスト（フェス）')
                    ->schema([
                        Forms\Components\Repeater::make('fes_setlist')
                            ->label('本編')
                            ->schema([
                                Forms\Components\Hidden::make('_uuid')
                                    ->default(fn () => \Illuminate\Support\Str::uuid()->toString()),
                                Forms\Components\Select::make('artist')
                                    ->label('アーティスト')
                                    ->relationship('artist', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->native(false)
                                    ->required()
                                    ->columnSpan(2),
                                Forms\Components\TextInput::make('song')
                                    ->label('曲名')
                                    ->required()
                                    ->columnSpan(2),
                                Forms\Components\Toggle::make('medley')
                                    ->label('Medley')
                                    ->default(false)
                                    ->inline(false)
                                    ->columnSpan(1),
                            ])
                            ->columns(5)
                            ->defaultItems(0)
                            ->live()
                            ->reorderable()
                            ->itemLabel(function (array $state, $component): ?string {
                                $items = $component->getState();
                                if (!is_array($items)) return '1';

                                $currentUuid = $state['_uuid'] ?? null;
                                if (!$currentUuid) return '?';

                                $number = 0;
                                foreach ($items as $item) {
                                    $isMedley = !empty($item['medley']);
                                    $uuid = $item['_uuid'] ?? null;

                                    if ($uuid === $currentUuid) {
                                        if ($isMedley) {
                                            return '';
                                        } else {
                                            $number++;
                                            return (string)$number;
                                        }
                                    }

                                    if (!$isMedley) {
                                        $number++;
                                    }
                                }

                                return '1';
                            })
                            ->addActionLabel('曲を追加')
                            ->columnSpanFull(),

                        Forms\Components\Repeater::make('fes_encore')
                            ->label('アンコール')
                            ->schema([
                                Forms\Components\Hidden::make('_uuid')
                                    ->default(fn () => \Illuminate\Support\Str::uuid()->toString()),
                                Forms\Components\Select::make('artist')
                                    ->label('アーティスト')
                                    ->relationship('artist', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->native(false)
                                    ->required()
                                    ->columnSpan(2),
                                Forms\Components\TextInput::make('song')
                                    ->label('曲名')
                                    ->required()
                                    ->columnSpan(2),
                                Forms\Components\Toggle::make('medley')
                                    ->label('Medley')
                                    ->default(false)
                                    ->inline(false)
                                    ->columnSpan(1),
                            ])
                            ->columns(5)
                            ->defaultItems(0)
                            ->live()
                            ->reorderable()
                            ->itemLabel(function (array $state, $component, Forms\Get $get): ?string {
                                // 本編のfes_setlistから曲数を取得
                                $setlistItems = $get('fes_setlist') ?? [];
                                $setlistCount = 0;
                                foreach ($setlistItems as $item) {
                                    if (empty($item['medley'])) {
                                        $setlistCount++;
                                    }
                                }

                                $items = $component->getState();
                                if (!is_array($items)) return (string)($setlistCount + 1);

                                $currentUuid = $state['_uuid'] ?? null;
                                if (!$currentUuid) return '?';

                                $number = $setlistCount;
                                foreach ($items as $item) {
                                    $isMedley = !empty($item['medley']);
                                    $uuid = $item['_uuid'] ?? null;

                                    if ($uuid === $currentUuid) {
                                        if ($isMedley) {
                                            return '';
                                        } else {
                                            $number++;
                                            return (string)$number;
                                        }
                                    }

                                    if (!$isMedley) {
                                        $number++;
                                    }
                                }

                                return (string)($setlistCount + 1);
                            })
                            ->addActionLabel('曲を追加')
                            ->columnSpanFull(),
                    ])
                    ->visible(fn (Forms\Get $get) => $get('fes') == 1)
                    ->collapsible()
                    ->columnSpanFull(),
            ])
            ->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('artist.name')
                    ->label('アーティスト')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('title')
                    ->label('タイトル')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('date')
                    ->label('公演日')
                    ->date('Y.m.d')
                    ->sortable(),
                Tables\Columns\TextColumn::make('year')
                    ->label('年')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('venue')
                    ->label('会場')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('作成日')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('fes')
                    ->label('種別')
                    ->options([
                        0 => '単独ライブ',
                        1 => 'フェス',
                    ]),
                Tables\Filters\SelectFilter::make('artist')
                    ->relationship('artist', 'name')
                    ->label('アーティスト')
                    ->searchable(),
                Tables\Filters\SelectFilter::make('year')
                    ->label('年')
                    ->options(function () {
                        return \App\Models\Setlist::query()
                            ->whereNotNull('year')
                            ->distinct()
                            ->orderBy('year', 'desc')
                            ->pluck('year', 'year');
                    }),
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
            ->defaultSort('date', 'desc');
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
            'index' => Pages\ListSetlists::route('/'),
            'create' => Pages\CreateSetlist::route('/create'),
            'edit' => Pages\EditSetlist::route('/{record}/edit'),
        ];
    }
}
