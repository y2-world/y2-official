<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SlSetlistResource\Pages;
use App\Models\SlSetlist;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SlSetlistResource extends Resource
{
    protected static ?string $model = SlSetlist::class;

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
                                $setlistVenues = \App\Models\SlSetlist::query()
                                    ->whereNotNull('venue')
                                    ->where('venue', '!=', '')
                                    ->distinct()
                                    ->pluck('venue');

                                // Tourから会場を取得
                                $tourVenues = \App\Models\DbConcert::query()
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
                            ->live(debounce: 0)
                            ->label('本編')
                            ->schema([
                                Forms\Components\Hidden::make('_uuid')
                                    ->default(fn() => \Illuminate\Support\Str::uuid()->toString()),
                                // 🎵 曲名セレクト
                                Forms\Components\Select::make('song')
                                    ->label('曲名')
                                    ->required()
                                    ->options(function () {
                                        $songs = \App\Models\SlSong::query()
                                            ->leftJoin('artists', 'artists.id', '=', 'setlist_songs.artist_id')
                                            ->select('setlist_songs.id', 'setlist_songs.title', 'artists.name as artist_name')
                                            ->orderBy('setlist_songs.title')
                                            ->get();
                                        
                                        // タイトルごとの出現回数をカウント
                                        $titleCounts = $songs->groupBy('title')->map->count();
                                        
                                        return $songs->mapWithKeys(function ($song) use ($titleCounts) {
                                            $label = $song->title;
                                            // 重複している場合のみアーティスト名を追加
                                            if ($titleCounts[$song->title] > 1 && $song->artist_name) {
                                                $label .= ' - ' . $song->artist_name;
                                            }
                                            return [$song->id => $label];
                                        });
                                    })
                                    ->searchable()
                                    ->native(false)
                                    ->live()
                                    ->createOptionForm([
                                        Forms\Components\TextInput::make('title')
                                            ->label('新しい曲名')
                                            ->required()
                                            ->maxLength(255),
                                    ])
                                    ->createOptionUsing(function (array $data, Forms\Get $get): int {
                                        $artistId = $get('../../artist_id');

                                        $song = \App\Models\SlSong::firstOrCreate(
                                            [
                                                'title' => $data['title'],
                                                'artist_id' => $artistId,
                                            ],
                                            []
                                        );

                                        return $song->id;
                                    })
                                    ->columnSpanFull(),

                                // 🎛️ 詳細設定（折りたたみ）
                                Forms\Components\Section::make('詳細設定')
                                    ->schema([
                                        Forms\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\Toggle::make('medley')
                                                    ->label('メドレー')
                                                    ->default(false),

                                                Forms\Components\TextInput::make('featuring')
                                                    ->label('共演者・アーティスト')
                                                    ->placeholder('例: ゲスト名')
                                                    ->maxLength(255),
                                            ]),
                                        Forms\Components\TextInput::make('version')
                                            ->label('バージョン')
                                            ->placeholder('例: (Acoustic Version)')
                                            ->maxLength(255),
                                    ])
                                    ->collapsible() // ← 折りたたみ可能
                                    ->collapsed()   // ← 初期は閉じる
                                    ->columnSpanFull(),
                            ])
                            ->columns(1)
                            ->defaultItems(0)
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

                        Forms\Components\Repeater::make('encore')
                            ->label('アンコール')
                            ->schema([
                                Forms\Components\Hidden::make('_uuid')
                                    ->default(fn() => \Illuminate\Support\Str::uuid()->toString()),

                                // 🎵 曲名セレクト
                                Forms\Components\Select::make('song')
                                    ->label('曲名')
                                    ->required()
                                    ->options(function () {
                                        $songs = \App\Models\SlSong::query()
                                            ->leftJoin('artists', 'artists.id', '=', 'setlist_songs.artist_id')
                                            ->select('setlist_songs.id', 'setlist_songs.title', 'artists.name as artist_name')
                                            ->orderBy('setlist_songs.title')
                                            ->get();
                                        
                                        // タイトルごとの出現回数をカウント
                                        $titleCounts = $songs->groupBy('title')->map->count();
                                        
                                        return $songs->mapWithKeys(function ($song) use ($titleCounts) {
                                            $label = $song->title;
                                            // 重複している場合のみアーティスト名を追加
                                            if ($titleCounts[$song->title] > 1 && $song->artist_name) {
                                                $label .= ' - ' . $song->artist_name;
                                            }
                                            return [$song->id => $label];
                                        });
                                    })
                                    ->searchable()
                                    ->native(false)
                                    ->live()
                                    ->createOptionForm([
                                        Forms\Components\TextInput::make('title')
                                            ->label('新しい曲名')
                                            ->required()
                                            ->maxLength(255),
                                    ])
                                    ->createOptionUsing(function (array $data, Forms\Get $get): int {
                                        $artistId = $get('../../artist_id');

                                        $song = \App\Models\SlSong::firstOrCreate(
                                            [
                                                'title' => $data['title'],
                                                'artist_id' => $artistId,
                                            ],
                                            []
                                        );

                                        return $song->id;
                                    })
                                    ->columnSpanFull(),

                                // 🎛️ 詳細設定（折りたたみ）
                                Forms\Components\Section::make('詳細設定')
                                    ->schema([
                                        Forms\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\Toggle::make('medley')
                                                    ->label('メドレー')
                                                    ->default(false),

                                                Forms\Components\TextInput::make('featuring')
                                                    ->label('共演者・アーティスト')
                                                    ->placeholder('例: ゲスト名')
                                                    ->maxLength(255),
                                            ]),
                                        Forms\Components\TextInput::make('version')
                                            ->label('バージョン')
                                            ->placeholder('例: (Acoustic Version)')
                                            ->maxLength(255),
                                    ])
                                    ->collapsible() // ← 折りたたみ可能
                                    ->collapsed()   // ← 初期は閉じる
                                    ->columnSpanFull(),
                            ])
                            ->columns(1)
                            ->defaultItems(0)
                            ->live()
                            ->reorderable()
                            ->itemLabel(function (array $state, $component, Forms\Get $get): ?string {
                                // 本編セットリストから曲数を取得
                                $setlistItems = $get('setlist') ?? [];
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
                    ->visible(fn(Forms\Get $get) => $get('fes') == 0)
                    ->collapsible()
                    ->columnSpanFull(),

                // フェスの場合のセットリスト
                Forms\Components\Section::make('セットリスト（フェス）')
                    ->schema([
                        Forms\Components\Repeater::make('fes_setlist')
                            ->label('本編')
                            ->schema([
                                Forms\Components\Hidden::make('_uuid')
                                    ->default(fn() => \Illuminate\Support\Str::uuid()->toString()),
                                Forms\Components\Select::make('artist')
                                    ->label('アーティスト')
                                    ->options(fn() => \App\Models\Artist::pluck('name', 'id'))
                                    ->searchable()
                                    ->native(false)
                                    ->nullable()
                                    ->createOptionForm([
                                        Forms\Components\TextInput::make('name')
                                            ->label('アーティスト名')
                                            ->required()
                                            ->maxLength(255),
                                    ])
                                    ->createOptionUsing(function (array $data) {
                                        $artist = \App\Models\Artist::create([
                                            'name' => $data['name'],
                                            'hidden' => 0,
                                        ]);
                                        return $artist->id;
                                    })
                                    ->columnSpan(1),

                                Forms\Components\Select::make('song')
                                    ->label('曲名')
                                    ->required()
                                    ->options(function () {
                                        $songs = \App\Models\SlSong::query()
                                            ->leftJoin('artists', 'artists.id', '=', 'setlist_songs.artist_id')
                                            ->select('setlist_songs.id', 'setlist_songs.title', 'artists.name as artist_name')
                                            ->orderBy('setlist_songs.title')
                                            ->get();
                                        
                                        // タイトルごとの出現回数をカウント
                                        $titleCounts = $songs->groupBy('title')->map->count();
                                        
                                        return $songs->mapWithKeys(function ($song) use ($titleCounts) {
                                            $label = $song->title;
                                            // 重複している場合のみアーティスト名を追加
                                            if ($titleCounts[$song->title] > 1 && $song->artist_name) {
                                                $label .= ' - ' . $song->artist_name;
                                            }
                                            return [$song->id => $label];
                                        });
                                    })
                                    ->searchable()
                                    ->native(false)
                                    ->live()
                                    ->createOptionForm([
                                        Forms\Components\TextInput::make('title')
                                            ->label('曲名')
                                            ->required()
                                            ->maxLength(255),
                                    ])
                                    ->createOptionUsing(function (array $data, Forms\Set $set, Forms\Get $get): int {
                                        $artistId = $get('artist');

                                        $song = \App\Models\SlSong::firstOrCreate(
                                            [
                                                'title' => $data['title'],
                                                'artist_id' => $artistId,
                                            ],
                                            []
                                        );

                                        return $song->id;
                                    })
                                    ->columnSpan(1),

                                // 🎛️ 詳細設定（アコーディオン）
                                Forms\Components\Section::make('詳細設定')
                                    ->collapsible()
                                    ->collapsed() // 初期は閉じる
                                    ->schema([
                                        Forms\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\Toggle::make('medley')
                                                    ->label('メドレー')
                                                    ->inline(false)
                                                    ->default(false)
                                                    ->dehydrated(),

                                                Forms\Components\TextInput::make('featuring')
                                                    ->label('共演者・アーティスト')
                                                    ->placeholder('例: ゲスト名')
                                                    ->maxLength(255)
                                                    ->dehydrated(),
                                            ]),
                                        Forms\Components\TextInput::make('version')
                                            ->label('バージョン')
                                            ->placeholder('例: (Acoustic Version)')
                                            ->maxLength(255)
                                            ->dehydrated(),
                                    ])
                                    ->columnSpanFull(),
                            ])
                            ->columns(2)
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
                                    ->default(fn() => \Illuminate\Support\Str::uuid()->toString()),

                                Forms\Components\Select::make('artist')
                                    ->label('アーティスト')
                                    ->options(fn() => \App\Models\Artist::pluck('name', 'id'))
                                    ->searchable()
                                    ->native(false)
                                    ->required()
                                    ->createOptionForm([
                                        Forms\Components\TextInput::make('name')
                                            ->label('アーティスト名')
                                            ->required()
                                            ->maxLength(255),
                                    ])
                                    ->createOptionUsing(function (array $data) {
                                        $artist = \App\Models\Artist::create([
                                            'name' => $data['name'],
                                            'hidden' => 0,
                                        ]);
                                        return $artist->id;
                                    })
                                    ->columnSpan(1),

                                Forms\Components\Select::make('song')
                                    ->label('曲名')
                                    ->required()
                                    ->options(function () {
                                        $songs = \App\Models\SlSong::query()
                                            ->leftJoin('artists', 'artists.id', '=', 'setlist_songs.artist_id')
                                            ->select('setlist_songs.id', 'setlist_songs.title', 'artists.name as artist_name')
                                            ->orderBy('setlist_songs.title')
                                            ->get();
                                        
                                        // タイトルごとの出現回数をカウント
                                        $titleCounts = $songs->groupBy('title')->map->count();
                                        
                                        return $songs->mapWithKeys(function ($song) use ($titleCounts) {
                                            $label = $song->title;
                                            // 重複している場合のみアーティスト名を追加
                                            if ($titleCounts[$song->title] > 1 && $song->artist_name) {
                                                $label .= ' - ' . $song->artist_name;
                                            }
                                            return [$song->id => $label];
                                        });
                                    })
                                    ->searchable()
                                    ->native(false)
                                    ->live()
                                    ->createOptionForm([
                                        Forms\Components\TextInput::make('title')
                                            ->label('曲名')
                                            ->required()
                                            ->maxLength(255),
                                    ])
                                    ->createOptionUsing(function (array $data, Forms\Set $set, Forms\Get $get): int {
                                        $artistId = $get('artist');

                                        $song = \App\Models\SlSong::create([
                                            'title' => $data['title'],
                                            'artist_id' => $artistId,
                                        ]);

                                        return $song->id;
                                    })
                                    ->columnSpan(1),

                                // 🎛️ 詳細設定（アコーディオン）
                                Forms\Components\Section::make('詳細設定')
                                    ->collapsible()
                                    ->collapsed() // 初期は閉じる
                                    ->schema([
                                        Forms\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\Toggle::make('medley')
                                                    ->label('メドレー')
                                                    ->inline(false)
                                                    ->default(false)
                                                    ->dehydrated(),

                                                Forms\Components\TextInput::make('featuring')
                                                    ->label('共演者・アーティスト')
                                                    ->placeholder('例: ゲスト名')
                                                    ->maxLength(255)
                                                    ->dehydrated(),
                                            ]),
                                        Forms\Components\TextInput::make('version')
                                            ->label('バージョン')
                                            ->placeholder('例: (Acoustic Version)')
                                            ->maxLength(255)
                                            ->dehydrated(),
                                    ])
                                    ->columnSpanFull(),
                            ])
                            ->columns(2)
                            ->defaultItems(0)
                            ->live()
                            ->reorderable()
                            ->itemLabel(function (array $state, $component, Forms\Get $get): ?string {
                                // 本編の fes_setlist から曲数を取得
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
                    ->visible(fn(Forms\Get $get) => $get('fes') == 1)
                    ->collapsible()
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
                        return \App\Models\SlSetlist::query()
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
            'index' => Pages\ListSlSetlists::route('/'),
            'create' => Pages\CreateSlSetlist::route('/create'),
            'edit' => Pages\EditSlSetlist::route('/{record}/edit'),
        ];
    }
}
