<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SlSetlistResource\Pages;
use App\Models\DbConcert;
use App\Models\DbSetlist;
use App\Models\DbSong;
use App\Models\SlSetlist;
use App\Models\SlSong;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
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

                        Forms\Components\Select::make('db_concert_id')
                            ->label('database側のツアーとの紐付け')
                            ->options(function (Forms\Get $get) {
                                $artistId = $get('artist_id');
                                if (!$artistId) return [];
                                return DbConcert::where('artist_id', $artistId)->orderByDesc('date1')->pluck('title', 'id');
                            })
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->nullable()
                            ->hidden(fn (Forms\Get $get) => (bool) $get('fes'))
                            ->columnSpanFull(),

                    ])
                    ->columns(2),

                Forms\Components\Radio::make('fes')
                    ->label('イベント種別')
                    ->options([
                        0 => '単独ライブ',
                        1 => 'フェス',
                    ])
                    ->default(0)
                    ->live()
                    ->columnSpanFull(),


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
                                // 候補にはSlSong（既存のセットリスト楽曲）に加え、DbSongのうちまだ
                                // どのSlSongとも紐付いていないもの（セットリスト楽曲としては未登録）も
                                // 出す。候補に無ければ今まで通りcreateOptionFormから新規作成する。
                                Forms\Components\Select::make('song')
                                    ->label('曲名')
                                    ->required()
                                    ->options(fn() => static::songOptionsWithUnlinkedDbSongs())
                                    ->searchable()
                                    ->native(false)
                                    ->live()
                                    ->afterStateUpdated(fn($state, $set) => $set('song', static::resolveSongOptionValue($state)))
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
                                            ]),
                                        Forms\Components\Radio::make('featuring_type')
                                            ->label('表示形式')
                                            ->options([
                                                'guest' => '共演者（feat.表記のまま表示）',
                                                'artist' => '別名義アーティスト（「 / 」区切りで表示）',
                                            ])
                                            ->default('guest')
                                            ->inline(),
                                        Forms\Components\TextInput::make('featuring')
                                            ->label('共演者・アーティスト')
                                            ->placeholder('例: ゲスト名')
                                            ->maxLength(255),
                                        Forms\Components\TextInput::make('alternative_title')
                                            ->label('別表記')
                                            ->placeholder('例: I\'LL BE')
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
                                // 候補にはSlSong（既存のセットリスト楽曲）に加え、DbSongのうちまだ
                                // どのSlSongとも紐付いていないもの（セットリスト楽曲としては未登録）も
                                // 出す。候補に無ければ今まで通りcreateOptionFormから新規作成する。
                                Forms\Components\Select::make('song')
                                    ->label('曲名')
                                    ->required()
                                    ->options(fn() => static::songOptionsWithUnlinkedDbSongs())
                                    ->searchable()
                                    ->native(false)
                                    ->live()
                                    ->afterStateUpdated(fn($state, $set) => $set('song', static::resolveSongOptionValue($state)))
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
                                            ]),
                                        Forms\Components\Radio::make('featuring_type')
                                            ->label('表示形式')
                                            ->options([
                                                'guest' => '共演者（feat.表記のまま表示）',
                                                'artist' => '別名義アーティスト（「 / 」区切りで表示）',
                                            ])
                                            ->default('guest')
                                            ->inline(),
                                        Forms\Components\TextInput::make('featuring')
                                            ->label('共演者・アーティスト')
                                            ->placeholder('例: ゲスト名')
                                            ->maxLength(255),
                                        Forms\Components\TextInput::make('alternative_title')
                                            ->label('別表記')
                                            ->placeholder('例: I\'LL BE')
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
                    ->hidden(fn(Forms\Get $get) => (bool)$get('fes'))
                    ->columnSpanFull(),

                // フェスの場合のセットリスト
                Forms\Components\Section::make('セットリスト（フェス）')
                    ->schema([
                        Forms\Components\Repeater::make('fes_setlist')
                            ->label('本編')
                            ->schema([
                                Forms\Components\Hidden::make('_uuid')
                                    ->default(fn() => \Illuminate\Support\Str::uuid()->toString()),
                                Forms\Components\Select::make('type')
                                    ->label('種別')
                                    ->options([
                                        'block' => 'ブロック（アーティスト単位）',
                                        'song' => '1曲（交互）',
                                    ])
                                    ->default('block')
                                    ->live()
                                    ->required()
                                    ->columnSpanFull(),

                                // artist は song/block 共通キー
                                Forms\Components\Select::make('artist')
                                    ->label('アーティスト')
                                    ->options(fn() => \App\Support\JapaneseNameSorter::sortOptions(\App\Models\Artist::pluck('name', 'id')->all()))
                                    ->searchable()
                                    ->native(false)
                                    ->nullable()
                                    ->afterStateHydrated(fn(Forms\Components\Select $component, $state) => $component->state($state !== null ? (int)$state : null))
                                    ->createOptionForm([
                                        Forms\Components\TextInput::make('name')
                                            ->label('アーティスト名')
                                            ->required()
                                            ->maxLength(255),
                                    ])
                                    ->createOptionUsing(function (array $data) {
                                        $artist = \App\Models\Artist::create(['name' => $data['name'], 'hidden' => 0]);
                                        return $artist->id;
                                    })
                                    ->columnSpan(fn(Forms\Get $get) => $get('type') === 'block' ? 'full' : 1),

                                // type:'song' のみ表示
                                Forms\Components\Select::make('song')
                                    ->label('曲名')
                                    ->options(fn() => static::songOptionsWithUnlinkedDbSongs())
                                    ->afterStateHydrated(fn(Forms\Components\Select $component, $state) => $component->state($state !== null ? (int)$state : null))
                                    ->searchable()
                                    ->native(false)
                                    ->live()
                                    ->afterStateUpdated(fn($state, $set) => $set('song', static::resolveSongOptionValue($state)))
                                    ->createOptionForm([
                                        Forms\Components\TextInput::make('title')
                                            ->label('曲名')
                                            ->required()
                                            ->maxLength(255),
                                    ])
                                    ->createOptionUsing(function (array $data, Forms\Get $get): int {
                                        $artistId = $get('artist');
                                        $song = \App\Models\SlSong::firstOrCreate(
                                            ['title' => $data['title'], 'artist_id' => $artistId],
                                            []
                                        );
                                        return $song->id;
                                    })
                                    ->hidden(fn(Forms\Get $get) => $get('type') === 'block')
                                    ->columnSpan(1),

                                Forms\Components\Section::make('詳細設定')
                                    ->collapsible()
                                    ->collapsed()
                                    ->schema([
                                        Forms\Components\Grid::make(2)->schema([
                                            Forms\Components\Toggle::make('medley')
                                                ->label('メドレー')
                                                ->inline(false)
                                                ->default(false)
                                                ->dehydrated(),
                                            Forms\Components\TextInput::make('featuring')
                                                ->label('共演者・アーティスト')
                                                ->maxLength(255)
                                                ->dehydrated(),
                                        ]),
                                        Forms\Components\TextInput::make('alternative_title')
                                            ->label('別表記')
                                            ->maxLength(255)
                                            ->dehydrated(),
                                    ])
                                    ->hidden(fn(Forms\Get $get) => $get('type') === 'block')
                                    ->columnSpanFull(),

                                // type:'block' のみ表示
                                Forms\Components\Repeater::make('songs')
                                    ->label('曲リスト（ブロック内）')
                                    ->schema([
                                        Forms\Components\Hidden::make('_uuid')
                                            ->default(fn() => \Illuminate\Support\Str::uuid()->toString()),
                                        Forms\Components\Select::make('song')
                                            ->label('曲名')
                                            ->required()
                                            ->afterStateHydrated(fn(Forms\Components\Select $component, $state) => $component->state($state !== null ? (int)$state : null))
                                            ->options(fn() => static::songOptionsWithUnlinkedDbSongs())
                                            ->searchable()
                                            ->native(false)
                                            ->live()
                                            ->afterStateUpdated(fn($state, $set) => $set('song', static::resolveSongOptionValue($state)))
                                            ->createOptionForm([
                                                Forms\Components\TextInput::make('title')
                                                    ->label('曲名')
                                                    ->required()
                                                    ->maxLength(255),
                                            ])
                                            ->createOptionUsing(function (array $data, Forms\Get $get): int {
                                                $artistId = $get('../../artist');
                                                $song = \App\Models\SlSong::firstOrCreate(
                                                    ['title' => $data['title'], 'artist_id' => $artistId],
                                                    []
                                                );
                                                return $song->id;
                                            })
                                            ->columnSpanFull(),
                                        Forms\Components\Grid::make(2)->schema([
                                            Forms\Components\Toggle::make('medley')
                                                ->label('メドレー')
                                                ->inline(false)
                                                ->default(false)
                                                ->dehydrated(),
                                            Forms\Components\TextInput::make('featuring')
                                                ->label('共演者')
                                                ->maxLength(255)
                                                ->dehydrated(),
                                        ]),
                                        Forms\Components\Radio::make('featuring_type')
                                            ->label('表示形式')
                                            ->options([
                                                'guest' => '共演者（feat.表記のまま表示）',
                                                'artist' => '別名義アーティスト（「 / 」区切りで表示）',
                                            ])
                                            ->default('guest')
                                            ->inline()
                                            ->dehydrated(),
                                        Forms\Components\TextInput::make('alternative_title')
                                            ->label('別表記')
                                            ->maxLength(255)
                                            ->dehydrated(),
                                    ])
                                    ->columns(1)
                                    ->defaultItems(1)
                                    ->reorderable()
                                    ->addActionLabel('曲を追加')
                                    ->hidden(fn(Forms\Get $get) => $get('type') !== 'block')
                                    ->columnSpanFull(),
                            ])
                            ->columns(2)
                            ->defaultItems(0)
                            ->live()
                            ->reorderable()
                            ->itemLabel(function (array $state, $component): ?string {
                                $type = $state['type'] ?? 'song';
                                if ($type === 'block') {
                                    $artistName = \App\Models\Artist::find($state['artist'] ?? null)?->name ?? 'ブロック';
                                    $songCount = count($state['songs'] ?? []);
                                    return "[ブロック] {$artistName} ({$songCount}曲)";
                                }
                                $items = $component->getState();
                                if (!is_array($items)) return '1';
                                $currentUuid = $state['_uuid'] ?? null;
                                $number = 0;
                                foreach ($items as $item) {
                                    if (($item['type'] ?? 'song') === 'block') continue;
                                    if (empty($item['medley'])) $number++;
                                    if (($item['_uuid'] ?? null) === $currentUuid) {
                                        return empty($item['medley']) ? (string)$number : '';
                                    }
                                }
                                return (string)$number;
                            })
                            ->addActionLabel('追加')
                            ->columnSpanFull(),

                        Forms\Components\Repeater::make('fes_encore')
                            ->label('アンコール')
                            ->schema([
                                Forms\Components\Hidden::make('_uuid')
                                    ->default(fn() => \Illuminate\Support\Str::uuid()->toString()),
                                Forms\Components\Select::make('type')
                                    ->label('種別')
                                    ->options([
                                        'block' => 'ブロック（アーティスト単位）',
                                        'song' => '1曲（交互）',
                                    ])
                                    ->default('block')
                                    ->live()
                                    ->required()
                                    ->columnSpanFull(),

                                Forms\Components\Select::make('artist')
                                    ->label('アーティスト')
                                    ->options(fn() => \App\Support\JapaneseNameSorter::sortOptions(\App\Models\Artist::pluck('name', 'id')->all()))
                                    ->searchable()
                                    ->native(false)
                                    ->nullable()
                                    ->afterStateHydrated(fn(Forms\Components\Select $component, $state) => $component->state($state !== null ? (int)$state : null))
                                    ->createOptionForm([
                                        Forms\Components\TextInput::make('name')
                                            ->label('アーティスト名')
                                            ->required()
                                            ->maxLength(255),
                                    ])
                                    ->createOptionUsing(function (array $data) {
                                        $artist = \App\Models\Artist::create(['name' => $data['name'], 'hidden' => 0]);
                                        return $artist->id;
                                    })
                                    ->columnSpan(fn(Forms\Get $get) => $get('type') === 'block' ? 'full' : 1),

                                Forms\Components\Select::make('song')
                                    ->label('曲名')
                                    ->options(fn() => static::songOptionsWithUnlinkedDbSongs())
                                    ->afterStateHydrated(fn(Forms\Components\Select $component, $state) => $component->state($state !== null ? (int)$state : null))
                                    ->searchable()
                                    ->native(false)
                                    ->live()
                                    ->afterStateUpdated(fn($state, $set) => $set('song', static::resolveSongOptionValue($state)))
                                    ->createOptionForm([
                                        Forms\Components\TextInput::make('title')
                                            ->label('曲名')
                                            ->required()
                                            ->maxLength(255),
                                    ])
                                    ->createOptionUsing(function (array $data, Forms\Get $get): int {
                                        $artistId = $get('artist');
                                        $song = \App\Models\SlSong::firstOrCreate(
                                            ['title' => $data['title'], 'artist_id' => $artistId],
                                            []
                                        );
                                        return $song->id;
                                    })
                                    ->hidden(fn(Forms\Get $get) => $get('type') === 'block')
                                    ->columnSpan(1),

                                Forms\Components\Section::make('詳細設定')
                                    ->collapsible()
                                    ->collapsed()
                                    ->schema([
                                        Forms\Components\Grid::make(2)->schema([
                                            Forms\Components\Toggle::make('medley')
                                                ->label('メドレー')
                                                ->inline(false)
                                                ->default(false)
                                                ->dehydrated(),
                                            Forms\Components\TextInput::make('featuring')
                                                ->label('共演者・アーティスト')
                                                ->maxLength(255)
                                                ->dehydrated(),
                                        ]),
                                        Forms\Components\TextInput::make('alternative_title')
                                            ->label('別表記')
                                            ->maxLength(255)
                                            ->dehydrated(),
                                    ])
                                    ->hidden(fn(Forms\Get $get) => $get('type') === 'block')
                                    ->columnSpanFull(),

                                Forms\Components\Repeater::make('songs')
                                    ->label('曲リスト（ブロック内）')
                                    ->schema([
                                        Forms\Components\Hidden::make('_uuid')
                                            ->default(fn() => \Illuminate\Support\Str::uuid()->toString()),
                                        Forms\Components\Select::make('song')
                                            ->label('曲名')
                                            ->required()
                                            ->afterStateHydrated(fn(Forms\Components\Select $component, $state) => $component->state($state !== null ? (int)$state : null))
                                            ->options(fn() => static::songOptionsWithUnlinkedDbSongs())
                                            ->searchable()
                                            ->native(false)
                                            ->live()
                                            ->afterStateUpdated(fn($state, $set) => $set('song', static::resolveSongOptionValue($state)))
                                            ->createOptionForm([
                                                Forms\Components\TextInput::make('title')
                                                    ->label('曲名')
                                                    ->required()
                                                    ->maxLength(255),
                                            ])
                                            ->createOptionUsing(function (array $data, Forms\Get $get): int {
                                                $artistId = $get('../../artist');
                                                $song = \App\Models\SlSong::firstOrCreate(
                                                    ['title' => $data['title'], 'artist_id' => $artistId],
                                                    []
                                                );
                                                return $song->id;
                                            })
                                            ->columnSpanFull(),
                                        Forms\Components\Grid::make(2)->schema([
                                            Forms\Components\Toggle::make('medley')
                                                ->label('メドレー')
                                                ->inline(false)
                                                ->default(false)
                                                ->dehydrated(),
                                            Forms\Components\TextInput::make('featuring')
                                                ->label('共演者')
                                                ->maxLength(255)
                                                ->dehydrated(),
                                        ]),
                                        Forms\Components\Radio::make('featuring_type')
                                            ->label('表示形式')
                                            ->options([
                                                'guest' => '共演者（feat.表記のまま表示）',
                                                'artist' => '別名義アーティスト（「 / 」区切りで表示）',
                                            ])
                                            ->default('guest')
                                            ->inline()
                                            ->dehydrated(),
                                        Forms\Components\TextInput::make('alternative_title')
                                            ->label('別表記')
                                            ->maxLength(255)
                                            ->dehydrated(),
                                    ])
                                    ->columns(1)
                                    ->defaultItems(1)
                                    ->reorderable()
                                    ->addActionLabel('曲を追加')
                                    ->hidden(fn(Forms\Get $get) => $get('type') !== 'block')
                                    ->columnSpanFull(),
                            ])
                            ->columns(2)
                            ->defaultItems(0)
                            ->live()
                            ->reorderable()
                            ->itemLabel(function (array $state, $component): ?string {
                                $type = $state['type'] ?? 'song';
                                if ($type === 'block') {
                                    $artistName = \App\Models\Artist::find($state['artist'] ?? null)?->name ?? 'ブロック';
                                    $songCount = count($state['songs'] ?? []);
                                    return "[ブロック] {$artistName} ({$songCount}曲)";
                                }
                                $items = $component->getState();
                                if (!is_array($items)) return '1';
                                $currentUuid = $state['_uuid'] ?? null;
                                $number = 0;
                                foreach ($items as $item) {
                                    if (($item['type'] ?? 'song') === 'block') continue;
                                    if (empty($item['medley'])) $number++;
                                    if (($item['_uuid'] ?? null) === $currentUuid) {
                                        return empty($item['medley']) ? (string)$number : '';
                                    }
                                }
                                return (string)$number;
                            })
                            ->addActionLabel('追加')
                            ->columnSpanFull(),
                    ])
                    ->hidden(fn(Forms\Get $get) => !(bool)$get('fes'))
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
                Tables\Columns\IconColumn::make('db_concert_id')
                    ->label('DB紐付け')
                    ->icon(fn (SlSetlist $record) => $record->fes ? null : ($record->db_concert_id !== null ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle'))
                    ->color(fn (SlSetlist $record) => $record->db_concert_id !== null ? 'success' : 'danger')
                    ->getStateUsing(fn (SlSetlist $record) => $record->db_concert_id !== null),
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
                Tables\Filters\TernaryFilter::make('db_concert_id')
                    ->label('DB紐付け状態')
                    ->nullable()
                    ->trueLabel('紐付け済み')
                    ->falseLabel('未紐付け（フェス除く）')
                    ->queries(
                        true: fn ($query) => $query->whereNotNull('db_concert_id'),
                        false: fn ($query) => $query->whereNull('db_concert_id')->where('fes', 0),
                    ),
            ])
            ->actions([
                Tables\Actions\Action::make('copyToDatabase')
                    ->label('DBにコピー')
                    ->icon('heroicon-o-document-duplicate')
                    ->color('gray')
                    ->visible(fn (SlSetlist $record) => !$record->fes && !$record->db_concert_id && (!empty($record->setlist) || !empty($record->encore)))
                    ->modalHeading('データベースにコピー')
                    ->modalDescription('このセットリストを新しいツアー・ライブとしてデータベース（database側）にコピーします。曲目はSlSong側の紐付け（db_song_id）があればそれを使い、無ければ同名のDbSongを新規作成します。')
                    ->modalSubmitActionLabel('コピーする')
                    ->form([
                        Forms\Components\Select::make('artist_id')
                            ->label('アーティスト')
                            ->options(fn () => \App\Support\JapaneseNameSorter::sortOptions(\App\Models\Artist::pluck('name', 'id')->all()))
                            ->required()
                            ->native(false)
                            ->searchable(),

                        Forms\Components\TextInput::make('title')
                            ->label('タイトル')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Radio::make('type')
                            ->label('タイプ')
                            ->options([
                                0 => 'ツアー',
                                1 => '単発ライブ',
                                2 => 'イベント',
                                3 => 'ap bank fes',
                                4 => 'ソロ',
                            ])
                            ->default(0)
                            ->required(),

                        Forms\Components\DatePicker::make('date1')
                            ->label('開始日')
                            ->required()
                            ->native(false)
                            ->displayFormat('Y.m.d'),

                        Forms\Components\DatePicker::make('date2')
                            ->label('終了日')
                            ->native(false)
                            ->displayFormat('Y.m.d'),

                        Forms\Components\TextInput::make('venue')
                            ->label('会場')
                            ->maxLength(255),

                        Forms\Components\Textarea::make('schedule')
                            ->label('スケジュール')
                            ->rows(6),
                    ])
                    ->fillForm(fn (SlSetlist $record) => [
                        'artist_id' => $record->artist_id,
                        'title' => $record->title,
                        'type' => 0,
                        'date1' => $record->date,
                        'venue' => $record->venue,
                    ])
                    ->action(function (array $data, SlSetlist $record) {
                        $dbSetlist = static::copySlSetlistToDatabase($record, $data);

                        // 同じツアー（artist_id + title）の他の公演日も含めて
                        // 「コピー済み」にする。同じツアーへ複数回行った場合でも
                        // ツアー自体は1回コピーすれば十分なため。
                        // タイトルの完全一致ではなく正規化した上での比較にするのは、
                        // スマートクォート("")と直引用符("")のような表記ゆれがあると
                        // 完全一致では同一ツアーの別公演を見つけられず、片方だけ
                        // コピー済みになったままボタンが消えない事故が起きるため。
                        $normalizedTitle = static::normalizeTourTitle($record->title);
                        SlSetlist::where('artist_id', $record->artist_id)
                            ->get(['id', 'title'])
                            ->filter(fn (SlSetlist $s) => static::normalizeTourTitle($s->title) === $normalizedTitle)
                            ->each(fn (SlSetlist $s) => $s->update(['db_concert_id' => $dbSetlist->tour_id]));

                        Notification::make()
                            ->success()
                            ->title('データベースにコピーしました')
                            ->body("「{$dbSetlist->tour->title}」として登録しました。")
                            ->send();

                        return redirect(DbSetlistResource::getUrl('edit', ['record' => $dbSetlist]));
                    }),
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

    // 同じツアーかどうかを判定するためのタイトル正規化。SongTitleNormalizer（曲名専用）とは
    // 別に用意する。スマートクォート("")と直引用符("")、波ダッシュ・全角チルダ、
    // 空白の有無といった表記ゆれを吸収し、db_concert_idの一括紐付け判定にのみ使う。
    private static function normalizeTourTitle(string $title): string
    {
        $title = mb_convert_kana($title, 'as');
        $title = str_replace(["\u{201C}", "\u{201D}", "\u{2018}", "\u{2019}", "'"], '"', $title);
        $title = preg_replace('/[\x{301C}\x{FF5E}~]/u', '', $title);
        $title = preg_replace('/\s+/u', '', $title);
        return mb_strtolower($title);
    }

    // SlSetlist（公開セトリ投稿）1件を、新しいDbConcert（database側のツアー・ライブ）+
    // DbSetlist（セットリストパターン1つ）としてコピーする。
    // アーティスト・タイトル・タイプ・日程・会場・スケジュールは確認モーダルで
    // 編集された$dataの内容を使う（初期値はSlSetlistの値だが、その場で修正できる）。
    // 曲目はSlSong.db_song_idの既存紐付けがあればそれを使い、無ければ同名のDbSongを
    // firstOrCreateする（タイトル一致で自動的に他の箇所からも参照できる曲になる）。
    private static function copySlSetlistToDatabase(SlSetlist $record, array $data): DbSetlist
    {
        $tour = DbConcert::create([
            'artist_id' => $data['artist_id'],
            'title' => $data['title'],
            'type' => $data['type'],
            'date1' => $data['date1'],
            'date2' => $data['date2'] ?? null,
            'venue' => $data['venue'] ?? null,
            'schedule' => $data['schedule'] ?? null,
        ]);

        $toDbSongItems = function (array $items) use ($data) {
            $result = [];
            foreach ($items as $item) {
                if (!isset($item['song']) || $item['song'] === '') {
                    continue;
                }

                $slSong = SlSong::find($item['song']);
                if (!$slSong) {
                    continue;
                }

                if ($slSong->db_song_id) {
                    $dbSongId = $slSong->db_song_id;
                } else {
                    $dbSong = DbSong::firstOrCreate(
                        ['artist_id' => $data['artist_id'], 'title' => $slSong->title],
                        []
                    );
                    $dbSongId = $dbSong->id;
                }

                $newItem = ['song' => (string) $dbSongId];
                if (!empty($item['medley'])) {
                    $newItem['medley'] = true;
                }
                if (!empty($item['featuring'])) {
                    $newItem['featuring'] = $item['featuring'];
                    $newItem['featuring_type'] = $item['featuring_type'] ?? 'guest';
                }
                if (!empty($item['alternative_title'])) {
                    $newItem['alternative_title'] = $item['alternative_title'];
                }

                $result[] = $newItem;
            }
            return $result;
        };

        return DbSetlist::create([
            'tour_id' => $tour->id,
            'order_no' => 1,
            'row' => 1,
            'setlist' => $toDbSongItems($record->setlist ?? []),
            'encore' => $toDbSongItems($record->encore ?? []),
        ]);
    }

    // 曲名セレクトの選択肢。SlSong（既存のセットリスト楽曲）に加え、DbSongのうち
    // まだどのSlSongとも紐付いていないもの（＝セットリスト楽曲としては未登録）も候補に出す。
    // DbSong由来の候補は"db-{id}"というキーにしてSlSong.idとの衝突を避ける。
    protected static function songOptionsWithUnlinkedDbSongs(): array
    {
        $slSongs = SlSong::query()
            ->leftJoin('artists', 'artists.id', '=', 'sl_songs.artist_id')
            ->select('sl_songs.id', 'sl_songs.title', 'artists.name as artist_name')
            ->get();

        $unlinkedDbSongs = DbSong::query()
            ->whereDoesntHave('slSongs')
            ->leftJoin('artists', 'artists.id', '=', 'db_songs.artist_id')
            ->select('db_songs.id', 'db_songs.title', 'artists.name as artist_name')
            ->get();

        $titleCounts = $slSongs->pluck('title')
            ->merge($unlinkedDbSongs->pluck('title'))
            ->countBy();

        $buildLabel = function ($song) use ($titleCounts) {
            $label = $song->title;
            if ($titleCounts[$song->title] > 1 && $song->artist_name) {
                $label .= ' - ' . $song->artist_name;
            }
            return $label;
        };

        $options = $slSongs->mapWithKeys(fn($song) => [(string) $song->id => $buildLabel($song)])->all();
        $options += $unlinkedDbSongs->mapWithKeys(fn($song) => ['db-' . $song->id => $buildLabel($song) . '（未登録）'])->all();

        return \App\Support\JapaneseNameSorter::sortOptions($options);
    }

    // 曲名セレクトで選択された値を保存用に解決する。"db-{id}"形式（＝DbSong由来の
    // 未登録候補）が選ばれた場合は、その場でSlSongを新規作成し、db_song_idを
    // 紐付けた上でそのSlSong.idを返す。通常のSlSong.idはそのまま返す。
    protected static function resolveSongOptionValue(?string $state): ?string
    {
        if ($state === null || $state === '') {
            return $state;
        }

        if (!str_starts_with($state, 'db-')) {
            return $state;
        }

        $dbSongId = (int) substr($state, 3);
        $dbSong = DbSong::find($dbSongId);
        if (!$dbSong) {
            return $state;
        }

        $slSong = SlSong::firstOrCreate(
            ['title' => $dbSong->title, 'artist_id' => $dbSong->artist_id],
            ['db_song_id' => $dbSong->id]
        );
        if (!$slSong->db_song_id) {
            $slSong->update(['db_song_id' => $dbSong->id]);
        }

        return (string) $slSong->id;
    }
}
