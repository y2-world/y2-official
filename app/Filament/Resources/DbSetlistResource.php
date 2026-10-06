<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DbSetlistResource\Pages;
use App\Models\DbSetlist;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Illuminate\Validation\Rule;
use Filament\Tables;
use Filament\Tables\Table;

class DbSetlistResource extends Resource
{
    protected static ?string $model = DbSetlist::class;

    protected static ?string $navigationIcon = 'heroicon-o-queue-list';

    // B'zのセットリストでは、稲葉浩志のソロ曲も選択肢に含める
    private const BZ_ARTIST_ID = 3;
    private const INABA_ARTIST_ID = 39;

    protected static function songArtistIds($artistId): array
    {
        if ((int) $artistId === self::BZ_ARTIST_ID) {
            return [self::BZ_ARTIST_ID, self::INABA_ARTIST_ID];
        }

        return [$artistId];
    }

    protected static ?string $navigationLabel = 'セットリスト';

    protected static ?string $modelLabel = 'セットリスト';

    protected static ?string $navigationGroup = 'Database';

    protected static ?int $navigationSort = 21;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('基本情報')
                    ->schema([
                        Forms\Components\Select::make('_artist_id')
                            ->label('アーティスト')
                            ->options(fn() => \App\Models\Artist::where('visible', 1)->orderBy('id')->pluck('name', 'id')->all())
                            ->searchable()
                            ->native(false)
                            ->live()
                            ->afterStateHydrated(function ($state, $set, $record) {
                                if ($record && $record->tour) {
                                    $set('_artist_id', $record->tour->artist_id);
                                }
                            })
                            ->dehydrated(false),

                        Forms\Components\Select::make('tour_id')
                            ->label('ツアー')
                            ->options(fn(Get $get) => \App\Models\DbConcert::when(
                                $get('_artist_id'),
                                fn($q, $artistId) => $q->where('artist_id', $artistId)
                            )->orderBy('date1', 'asc')->pluck('title', 'id'))
                            ->searchable()
                            ->native(false)
                            ->required(),

                        Forms\Components\TextInput::make('row')
                            ->label('段')
                            ->numeric()
                            ->default(1)
                            ->minValue(1)
                            ->required(),

                        Forms\Components\TextInput::make('order_no')
                            ->label('パターン番号')
                            ->numeric()
                            ->required()
                            ->rules(fn(Get $get, $record) => [
                                Rule::unique('db_setlists', 'order_no')
                                    ->where('tour_id', $get('tour_id'))
                                    ->where('row', $get('row') ?? 1)
                                    ->ignore($record?->id),
                            ])
                            ->validationMessages([
                                'unique' => 'この段ではすでに使われているパターン番号です。',
                            ]),

                        Forms\Components\TextInput::make('row_title')
                            ->label('段のグループタイトル')
                            ->placeholder('例: アリーナ公演')
                            ->maxLength(255)
                            ->columnSpanFull()
                            ->afterStateHydrated(function ($set, $record) {
                                if (!$record) {
                                    return;
                                }
                                $title = \App\Models\DbSetlistRow::where('tour_id', $record->tour_id)
                                    ->where('row', $record->row)
                                    ->where('order_no', $record->order_no)
                                    ->value('title');
                                $set('row_title', $title ?? '');
                            }),

                        // ツアーにスケジュールがあるときは、スケジュールから日付（と地名）を選んでタイトルに入れられる。入れた後も手で直せる
                        Forms\Components\Select::make('_schedule_pick')
                            ->label('スケジュールから選ぶ')
                            ->multiple()
                            ->options(fn (Get $get) => static::scheduleOptions($get('tour_id')))
                            ->visible(fn (Get $get) => count(static::scheduleOptions($get('tour_id'))) > 0)
                            ->dehydrated(false)
                            ->live()
                            // 選んだ日は今のタイトルの後ろに足し、選択を外した日はタイトルから取る（手で書いた部分はそのまま）
                            ->afterStateUpdated(function ($state, $old, Get $get, $set) {
                                $state = (array) $state;
                                $old = (array) $old;
                                $subtitle = (string) $get('subtitle');
                                foreach (array_diff($old, $state) as $removed) {
                                    $subtitle = preg_replace('/(^|\s)' . preg_quote($removed, '/') . '(?=\s|$)/u', '', $subtitle);
                                }
                                foreach (array_diff($state, $old) as $added) {
                                    $subtitle = rtrim($subtitle) . ($subtitle !== '' && trim($subtitle) !== '' ? ' ' : '') . $added;
                                }
                                $set('subtitle', trim($subtitle));
                            })
                            ->helperText('選んだ順に「7.17 大阪」のように日付と地名がタイトルの後ろに足されます（単発のライブは日付だけ）。地名が違うときは手で直してください。')
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('subtitle')
                            ->label('タイトル（日付や説明）')
                            ->helperText('例: 「7.17 大阪」のように入力すると、最初の空白より後ろ（地名など）が自動で小さいグレー文字になります。')
                            ->rows(2)
                            ->maxLength(500)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('セットリスト')
                    ->schema([
                        Forms\Components\Repeater::make('setlist')
                            ->live(debounce: 0)
                            ->label('本編')
                            ->schema([
                                Forms\Components\Hidden::make('_uuid')
                                    ->default(fn() => \Illuminate\Support\Str::uuid()->toString()),

                                // 曲名（横いっぱい）
                                Forms\Components\Select::make('song')
                                    ->label('曲名')
                                    ->options(fn(Get $get) => \App\Support\JapaneseNameSorter::sortOptions(
                                        \App\Models\DbSong::when(
                                            $get('../../_artist_id'),
                                            fn($q, $id) => $q->whereIn('artist_id', static::songArtistIds($id))
                                        )->pluck('title', 'id')->all()
                                    ))
                                    ->searchable()
                                    ->native(false)
                                    ->required()
                                    ->allowHtml()
                                    ->live()
                                    ->afterStateUpdated(function ($set) {
                                        // 曲を差し替えたのに別表記（前の曲用のライブバージョン名等）が
                                        // 残ってしまい、差し替え後の曲と噛み合わない表記のまま表示され続ける
                                        // 事故を防ぐため、曲を変更したタイミングで別表記はクリアする
                                        $set('alternative_title', null);
                                    })
                                    ->getSearchResultsUsing(function (string $search, Get $get) {
                                        $options = \App\Models\DbSong::when(
                                            $get('../../_artist_id'),
                                            fn($q, $id) => $q->whereIn('artist_id', static::songArtistIds($id))
                                        )->whereRaw('LOWER(title) LIKE LOWER(?)', ["%{$search}%"])
                                            ->pluck('title', 'id')
                                            ->all();

                                        return array_slice(\App\Support\JapaneseNameSorter::sortOptions($options), 0, 50, true);
                                    })
                                    ->getOptionLabelUsing(function ($value) {
                                        if (is_numeric($value)) {
                                            $song = \App\Models\DbSong::find($value);
                                            return $song ? $song->title : $value;
                                        }
                                        return $value;
                                    })
                                    ->createOptionForm([
                                        Forms\Components\TextInput::make('title')
                                            ->label('曲名')
                                            ->required()
                                            ->maxLength(255),
                                    ])
                                    ->createOptionUsing(function (array $data): string {
                                        return $data['title'];
                                    })
                                    ->columnSpanFull(),

                                // ⬇️ 詳細設定
                                Forms\Components\Section::make('詳細設定')
                                    ->schema([
                                        Forms\Components\Toggle::make('is_daily')
                                            ->label('日替わり')
                                            ->default(false)
                                            ->inline(false)
                                            ->live(),

                                        Forms\Components\Toggle::make('medley')
                                            ->label('メドレー')
                                            ->default(false)
                                            ->inline(false)
                                            ->live(),

                                        Forms\Components\TextInput::make('daily_note')
                                            ->label('日替わり情報')
                                            ->placeholder('例: 1')
                                            ->maxLength(255),

                                        Forms\Components\TextInput::make('featuring')
                                            ->label('共演者・アーティスト')
                                            ->placeholder('例: ゲスト名')
                                            ->maxLength(255),

                                        Forms\Components\Radio::make('featuring_type')
                                            ->label('表示形式')
                                            ->options([
                                                'guest' => '共演者（feat.表記のまま表示）',
                                                'artist' => '別名義アーティスト（「 / 」区切りで表示）',
                                            ])
                                            ->default('guest')
                                            ->inline(),

                                        Forms\Components\TextInput::make('alternative_title')
                                            ->label('別表記')
                                            ->placeholder('例: I\'LL BE')
                                            ->maxLength(255),
                                    ])
                                    ->columns(2)
                                    ->collapsible()
                                    ->collapsed()
                                    ->columnSpanFull(),
                            ])
                            ->columns(1)
                            ->reorderable()
                            ->itemLabel(function (array $state, $component): ?string {
                                $items = $component->getState();
                                if (!is_array($items)) return '1';

                                $currentUuid = $state['_uuid'] ?? null;
                                if (!$currentUuid) return '?';

                                $number = 0;
                                foreach ($items as $item) {
                                    $isSkipped = !empty($item['is_daily']) || !empty($item['medley']);
                                    $uuid = $item['_uuid'] ?? null;

                                    if ($uuid === $currentUuid) {
                                        if ($isSkipped) {
                                            return '';
                                        } else {
                                            $number++;
                                            return (string)$number;
                                        }
                                    }

                                    if (!$isSkipped) {
                                        $number++;
                                    }
                                }

                                return '1';
                            })
                            ->addActionLabel('曲を追加')
                            ->columnSpanFull(),


                        // ──────────────── アンコール ────────────────
                        Forms\Components\Repeater::make('encore')
                            ->label('アンコール')
                            ->schema([
                                Forms\Components\Hidden::make('_uuid')
                                    ->default(fn() => \Illuminate\Support\Str::uuid()->toString()),

                                // 曲名
                                Forms\Components\Select::make('song')
                                    ->label('曲名')
                                    ->options(fn(Get $get) => \App\Support\JapaneseNameSorter::sortOptions(
                                        \App\Models\DbSong::when(
                                            $get('../../_artist_id'),
                                            fn($q, $id) => $q->whereIn('artist_id', static::songArtistIds($id))
                                        )->pluck('title', 'id')->all()
                                    ))
                                    ->searchable()
                                    ->native(false)
                                    ->required()
                                    ->allowHtml()
                                    ->live()
                                    ->afterStateUpdated(function ($set) {
                                        // 曲を差し替えたのに別表記（前の曲用のライブバージョン名等）が
                                        // 残ってしまい、差し替え後の曲と噛み合わない表記のまま表示され続ける
                                        // 事故を防ぐため、曲を変更したタイミングで別表記はクリアする
                                        $set('alternative_title', null);
                                    })
                                    ->getSearchResultsUsing(function (string $search, Get $get) {
                                        $options = \App\Models\DbSong::when(
                                            $get('../../_artist_id'),
                                            fn($q, $id) => $q->whereIn('artist_id', static::songArtistIds($id))
                                        )->whereRaw('LOWER(title) LIKE LOWER(?)', ["%{$search}%"])
                                            ->pluck('title', 'id')
                                            ->all();

                                        return array_slice(\App\Support\JapaneseNameSorter::sortOptions($options), 0, 50, true);
                                    })
                                    ->getOptionLabelUsing(function ($value) {
                                        if (is_numeric($value)) {
                                            $song = \App\Models\DbSong::find($value);
                                            return $song ? $song->title : $value;
                                        }
                                        return $value;
                                    })
                                    ->createOptionForm([
                                        Forms\Components\TextInput::make('title')
                                            ->label('曲名')
                                            ->required()
                                            ->maxLength(255),
                                    ])
                                    ->createOptionUsing(function (array $data): string {
                                        return $data['title'];
                                    })
                                    ->columnSpanFull(),

                                // 詳細設定（折りたたみ）
                                Forms\Components\Section::make('詳細設定')
                                    ->collapsible() // 折りたたみ可能
                                    ->collapsed()   // 初期状態で閉じる
                                    ->schema([
                                        // ダブルアンコールなど、アンコールを複数回に分けるときに、次のアンコールの最初の曲に付ける
                                        Forms\Components\Toggle::make('encore_block_start')
                                            ->label('ここから次のアンコール（ENCORE 2 など）')
                                            ->default(false)
                                            ->inline(false)
                                            ->live()
                                            ->columnSpanFull(),

                                        Forms\Components\Toggle::make('is_daily')
                                            ->label('日替わり')
                                            ->default(false)
                                            ->inline(false)
                                            ->live(),

                                        Forms\Components\Toggle::make('medley')
                                            ->label('メドレー')
                                            ->default(false)
                                            ->inline(false)
                                            ->live(),

                                        Forms\Components\TextInput::make('daily_note')
                                            ->label('日替わり情報')
                                            ->placeholder('例: 1')
                                            ->maxLength(255),

                                        Forms\Components\TextInput::make('featuring')
                                            ->label('共演者・アーティスト')
                                            ->placeholder('例: ゲスト名')
                                            ->maxLength(255),

                                        Forms\Components\Radio::make('featuring_type')
                                            ->label('表示形式')
                                            ->options([
                                                'guest' => '共演者（feat.表記のまま表示）',
                                                'artist' => '別名義アーティスト（「 / 」区切りで表示）',
                                            ])
                                            ->default('guest')
                                            ->inline(),

                                        Forms\Components\TextInput::make('alternative_title')
                                            ->label('別表記')
                                            ->placeholder('例: I\'LL BE')
                                            ->maxLength(255),
                                    ])
                                    ->columns(2)
                                    ->columnSpanFull(),
                            ])
                            ->columns(1)
                            ->defaultItems(0)
                            ->live()
                            ->reorderable()
                            ->itemLabel(function (array $state, $component, Forms\Get $get): ?string {
                                $setlistItems = $get('setlist') ?? [];
                                $setlistCount = 0;
                                foreach ($setlistItems as $item) {
                                    if (empty($item['is_daily']) && empty($item['medley'])) {
                                        $setlistCount++;
                                    }
                                }

                                $items = $component->getState();
                                if (!is_array($items)) return (string)($setlistCount + 1);

                                $currentUuid = $state['_uuid'] ?? null;
                                if (!$currentUuid) return '?';

                                $number = $setlistCount;
                                foreach ($items as $item) {
                                    $isSkipped = !empty($item['is_daily']) || !empty($item['medley']);
                                    $uuid = $item['_uuid'] ?? null;

                                    if ($uuid === $currentUuid) {
                                        // 次のアンコールの始まりの曲には、どのアンコールかを番号の横に出す
                                        $blockLabel = '';
                                        if (!empty($item['encore_block_start'])) {
                                            $block = \App\Support\EncoreBlocks::blockIndexes(array_values($items))[array_search($uuid, array_column(array_values($items), '_uuid'), true)] ?? 0;
                                            $blockLabel = '【ENCORE ' . ($block + 1) . '】 ';
                                        }
                                        if ($isSkipped) {
                                            return trim($blockLabel);
                                        } else {
                                            $number++;
                                            return $blockLabel . $number;
                                        }
                                    }

                                    if (!$isSkipped) {
                                        $number++;
                                    }
                                }

                                return (string)($setlistCount + 1);
                            })
                            ->addActionLabel('曲を追加')
                            ->columnSpanFull(),
                    ])
                    ->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('tour.artist.name')
                    ->label('アーティスト')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('tour.title')
                    ->label('タイトル')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('row')
                    ->label('段')
                    ->sortable(),
                Tables\Columns\TextColumn::make('order_no')
                    ->label('パターン')
                    ->sortable(),
                Tables\Columns\TextColumn::make('subtitle')
                    ->label('サブタイトル')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('作成日')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('artist')
                    ->label('アーティスト')
                    ->options(fn() => \App\Models\Artist::where('visible', 1)->orderBy('id')->pluck('name', 'id')->all())
                    ->query(fn($query, array $data) =>
                        $data['value']
                            ? $query->whereHas('tour', fn($q) => $q->where('artist_id', $data['value']))
                            : $query
                    )
                    ->searchable(),
                Tables\Filters\SelectFilter::make('tour')
                    ->relationship('tour', 'title')
                    ->label('ツアー')
                    ->searchable(),
            ])
            ->actions([
                Tables\Actions\ReplicateAction::make()
                    ->modalHidden()
                    ->beforeReplicaSaved(function ($replica) {
                        $maxOrderNo = (int) \App\Models\DbSetlist::where('tour_id', $replica->tour_id)
                            ->where('row', $replica->row)
                            ->max('order_no');
                        $replica->order_no = $maxOrderNo + 1;
                        $replica->subtitle = null;
                    })
                    ->after(function ($replica) {
                        redirect(static::getUrl('edit', ['record' => $replica]));
                    }),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('updated_at', 'desc');
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
            'index' => Pages\ListDbSetlists::route('/'),
            'create' => Pages\CreateDbSetlist::route('/create'),
            'edit' => Pages\EditDbSetlist::route('/{record}/edit'),
        ];
    }

    // ツアーのスケジュール（「2021/5/1(土) 会場」「06/13 会場」「7月13日 会場」「10.6,7 会場」など）を、
    // パターンのタイトルに使う選択肢にする。表示は「月.日 会場名」、選んだときに入る文字（キー）は「月.日 地名」。
    // 同じ地名が2日以上あれば日付順に「仙台1」「仙台2」、単発のライブは日付だけ
    public static function scheduleOptions($tourId): array
    {
        $concert = $tourId ? \App\Models\DbConcert::find($tourId) : null;
        $dates = [];
        foreach (preg_split('/\r\n|\r|\n/', (string) $concert?->schedule) as $line) {
            // 日付は「8.31,9.1」「1.28,29,31,2.1」のように、月が途中で変わりながら並ぶことがある
            $day = '\d{1,2}日?(?:\s*[(（][^)）]*[)）])?';
            if (!preg_match('/^\s*(?:\d{4}\s*[\/.年]\s*)?(\d{1,2}\s*[\/.月]\s*' . $day . '(?:\s*[,，、・]\s*(?:\d{1,2}\s*[\/.月]\s*)?' . $day . ')*)\s*(.*)$/u', $line, $m)) {
                continue;
            }
            // 会場名から、かっこ書き（延期・振替・県名などの注記）を外す
            $venue = trim(preg_replace('/\s*[(（][^)）]*[)）]|【[^】]*】|《[^》]*》/u', '', $m[2]));
            $month = null;
            foreach (preg_split('/\s*[,，、・]\s*/u', preg_replace('/\s*[(（][^)）]*[)）]|日/u', '', $m[1])) as $item) {
                if (preg_match('/^(\d{1,2})\s*[\/.月]\s*(\d{1,2})$/u', $item, $md)) {
                    $month = (int) $md[1];
                    $item = $md[2];
                }
                $dates[$month . '.' . (int) $item] = $venue;
            }
        }

        // 同じ地名が続く日をひとまとまりにし、まとまりの中で1から番号を付ける（離れて同じ地名に戻ったら1から数え直す）
        $runs = [];
        foreach ($dates as $date => $venue) {
            $place = static::venuePlace($venue);
            $last = count($runs) - 1;
            if ($last >= 0 && $runs[$last]['place'] === $place) {
                $runs[$last]['dates'][$date] = $venue;
            } else {
                $runs[] = ['place' => $place, 'dates' => [$date => $venue]];
            }
        }
        $options = [];
        foreach ($runs as $run) {
            $n = 0;
            foreach ($run['dates'] as $date => $venue) {
                $n++;
                $key = (int) $concert->type === 1 || $run['place'] === ''
                    ? $date
                    : $date . ' ' . $run['place'] . (count($run['dates']) > 1 ? $n : '');
                $options[$key] = $date . ($venue !== '' ? ' ' . $venue : '');
            }
        }
        return $options;
    }

    // 会場名から、パターンタイトルに使う地名を決める。config/venue_places.php（これまでのタイトルから集めた対応）に
    // あればそれ、無ければ会場名の頭の地名（「名古屋国際会議場」→名古屋）や「○○市」、分からなければ会場名のまま
    public static function venuePlace(string $venue): string
    {
        $map = config('venue_places', []);
        // 「※公演中止」のような注記は外す
        $venue = trim(preg_replace('/\s*※.*$/u', '', $venue));
        if ($venue === '' || isset($map[$venue])) {
            return $map[$venue] ?? '';
        }
        // 「盛岡市民文化ホール 大ホール」のように、知っている会場名にホール名などが付いているもの
        $known = array_filter(array_keys($map), fn ($name) => mb_strlen($name) >= 4 && str_starts_with($venue, $name));
        if ($known) {
            usort($known, fn ($a, $b) => mb_strlen($b) <=> mb_strlen($a));
            return $map[$known[0]];
        }
        $prefs = ['北海道', '青森', '岩手', '宮城', '秋田', '山形', '福島', '茨城', '栃木', '群馬', '埼玉', '千葉', '東京', '神奈川', '新潟', '富山', '石川', '福井', '山梨', '長野', '岐阜', '静岡', '愛知', '三重', '滋賀', '京都', '大阪', '兵庫', '奈良', '和歌山', '鳥取', '島根', '岡山', '広島', '山口', '徳島', '香川', '愛媛', '高知', '福岡', '佐賀', '長崎', '熊本', '大分', '宮崎', '鹿児島', '沖縄'];
        $placeNames = array_unique(array_merge(array_values($map), $prefs));
        usort($placeNames, fn ($a, $b) => mb_strlen($b) <=> mb_strlen($a));
        foreach ($placeNames as $place) {
            if ($place !== '' && str_starts_with($venue, $place)) {
                return $place;
            }
        }
        if (preg_match('/^([^\s市]{1,4}?)市/u', $venue, $m)) {
            return $m[1];
        }
        return $venue;
    }
}
