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
use App\Support\AdminData;
use Illuminate\Database\Eloquent\Model;

class DbSetlistResource extends Resource
{
    protected static ?string $model = DbSetlist::class;

    protected static ?string $navigationIcon = 'heroicon-o-queue-list';

    // B'zのセットリストでは、稲葉浩志のソロ曲も選択肢に含める
    private const BZ_ARTIST_ID = 3;
    private const INABA_ARTIST_ID = 39;

    protected static function songArtistIds($artistId): array
    {
        // 公式の B'z のときだけ（マイページのアーティストの id とは別物）
        if (!AdminData::isMember() && (int) $artistId === self::BZ_ARTIST_ID) {
            return [self::BZ_ARTIST_ID, self::INABA_ARTIST_ID];
        }

        return [$artistId];
    }

    // 曲の選択肢の表示。アーティスト欄（credit）がある曲は「曲名 - アーティスト」にする（コラボ曲だと分かるように。SlSetlist の選択肢と同じ書き方）
    protected static function songOptionLabel(Model $song): string
    {
        $credit = trim((string) $song->credit);

        return $credit === '' ? $song->title : $song->title . ' - ' . e($credit);
    }

    protected static function bulkSongValue(string $title, $artistId): string|int
    {
        if (!$artistId) {
            return $title;
        }

        $matches = static::songQuery($artistId)
            ->whereRaw('LOWER(TRIM(title)) = LOWER(?)', [trim($title)])
            ->limit(2)
            ->pluck('id');

        return $matches->count() === 1 ? (int) $matches->first() : $title;
    }

    protected static function syncBulkSongs($state, string $target, string $generatedState, $artistId, Forms\Get $get, Forms\Set $set): void
    {
        $chunks = is_array($state) ? $state : [$state];
        $titles = [];
        $blockStarts = [];
        foreach ($chunks as $chunkIndex => $chunk) {
            $chunkTitles = array_values(array_filter(array_map(
                fn ($line) => trim(preg_replace('/^\\s*(?:\\d+\\s*[.)．、]\\s*|[-*・]\\s*)/u', '', trim($line))),
                preg_split('/\\R/u', (string) $chunk) ?: []
            ), fn ($title) => $title !== ''));
            if ($chunkTitles && $chunkIndex > 0) {
                $blockStarts[] = count($titles);
            }
            $titles = array_merge($titles, $chunkTitles);
        }

        $generatedUuids = (array) $get('data.' . $generatedState, true);
        $currentItems = array_values((array) $get('data.' . $target, true));
        $generatedItems = array_values(array_filter(
            $currentItems,
            fn ($item) => in_array($item['_uuid'] ?? null, $generatedUuids, true)
        ));
        $baseItems = array_values(array_filter(
            $currentItems,
            fn ($item) => !in_array($item['_uuid'] ?? null, $generatedUuids, true)
                && (
                    trim((string) ($item['song'] ?? '')) !== ''
                    || trim((string) ($item['alternative_title'] ?? '')) !== ''
                    || trim((string) ($item['featuring'] ?? '')) !== ''
                    || trim((string) ($item['medley_title'] ?? '')) !== ''
                    || trim((string) ($item['daily_note'] ?? '')) !== ''
                    || !empty($item['is_daily'])
                    || !empty($item['medley'])
                    || !empty($item['encore_block_start'])
                )
        ));

        // 一括入力を編集しても、変更されていない行の詳細設定は保つ。
        $preservedByTitle = [];
        foreach ($generatedItems as $item) {
            $songValue = $item['song'] ?? '';
            $title = is_numeric($songValue)
                ? (string) (static::songQuery($artistId)->whereKey($songValue)->value('title') ?? $songValue)
                : (string) $songValue;
            $preservedByTitle[$title][] = $item;
        }

        $newItems = [];
        foreach ($titles as $index => $title) {
            $item = !empty($preservedByTitle[$title])
                ? array_shift($preservedByTitle[$title])
                : ['_uuid' => (string) \Illuminate\Support\Str::uuid(), 'song' => static::bulkSongValue($title, $artistId)];
            if (in_array($index, $blockStarts, true)) {
                $item['encore_block_start'] = true;
            } else {
                unset($item['encore_block_start']);
            }
            $newItems[] = $item;
        }

        $set('data.' . $target, array_merge($baseItems, $newItems), true);
        $set('data.' . $generatedState, array_column($newItems, '_uuid'), true);
    }

    protected static function syncBulkSetlist($state, Forms\Get $get, Forms\Set $set): void
    {
        $sections = preg_split('/\\R[ \\t]*\\R/u', trim((string) $state)) ?: [];
        $artistId = $get('data._artist_id', true);
        static::syncBulkSongs(array_slice($sections, 0, 1), 'setlist', '_bulk_setlist_uuids', $artistId, $get, $set);
        $set('data._bulk_encore_items', \App\Support\EncoreFormGroups::toItems((array) $get('data._encore_groups', true)), true);
        static::syncBulkSongs(array_slice($sections, 1), '_bulk_encore_items', '_bulk_encore_uuids', $artistId, $get, $set);
        $set('data._encore_groups', \App\Support\EncoreFormGroups::fromItems((array) $get('data._bulk_encore_items', true)), true);
    }

    public static function splitEncoreForFormData(array $data): array
    {
        $items = array_values((array) ($data['encore'] ?? []));
        $data['_encore_groups'] = \App\Support\EncoreFormGroups::fromItems($items);
        $data['_bulk_encore_items'] = $items;
        $data['_bulk_encore_uuids'] = array_column($items, '_uuid');
        $data['_bulk_setlist_uuids'] = array_column((array) ($data['setlist'] ?? []), '_uuid');
        unset($data['encore']);
        return $data;
    }

    public static function mergeEncoreFormData(array $data): array
    {
        $data['encore'] = \App\Support\EncoreFormGroups::toItems((array) ($data['_encore_groups'] ?? []));
        unset($data['_encore_groups'], $data['_bulk_encore_items'], $data['_bulk_encore_uuids']);
        return $data;
    }

    protected static function encoreGroupsField(): Forms\Components\Repeater
    {
        return Forms\Components\Repeater::make('_encore_groups')
            ->label('アンコール')
            ->extraAttributes(['class' => 'setlist-encore-groups'])
            ->schema([
                Forms\Components\Hidden::make('_uuid')->default(fn() => (string) \Illuminate\Support\Str::uuid()),
                Forms\Components\Repeater::make('songs')->label('曲')->extraAttributes(['class' => 'setlist-encore-songs'])->schema([
                    Forms\Components\Hidden::make('_uuid')->default(fn() => (string) \Illuminate\Support\Str::uuid()),
                    Forms\Components\Select::make('song')->label('曲名')->required()
                        ->options(fn(Forms\Get $get) => \App\Support\JapaneseNameSorter::sortOptions(
                            static::songQuery($get('../../../../_artist_id'))->get()->mapWithKeys(fn($song) => [$song->id => static::songOptionLabel($song)])->all()
                        ))
                        ->searchable()->native(false)->allowHtml()->live()
                        ->afterStateUpdated(fn($state, Forms\Set $set) => $set('alternative_title', null))
                        ->getOptionLabelUsing(function ($value) {
                            if (!is_numeric($value)) return $value;
                            $song = AdminData::query('song')->find($value);
                            return $song ? static::songOptionLabel($song) : $value;
                        })
                        ->createOptionForm([Forms\Components\TextInput::make('title')->label('曲名')->required()->maxLength(255)])
                        ->createOptionUsing(fn(array $data) => $data['title'])
                        ->columnSpanFull(),
                    Forms\Components\Section::make('詳細設定')->schema([
                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\Toggle::make('medley')->label('メドレー')->default(false)->live(),
                            Forms\Components\Toggle::make('is_daily')->label('日替わり')->default(false)->live(),
                            Forms\Components\TextInput::make('medley_title')->label('メドレーのタイトル')->visible(fn(Forms\Get $get) => (bool) $get('medley'))->maxLength(255),
                            Forms\Components\TextInput::make('daily_note')->label('日替わり情報')->maxLength(255),
                        ]),
                        Forms\Components\Radio::make('featuring_type')->label('表示形式')->options([
                            'guest' => '共演者（feat.表記のまま表示）', 'artist' => '別名義アーティスト（「 / 」区切りで表示）', 'credit' => 'アーティスト表記あり',
                        ])->default('guest')->inline(),
                        Forms\Components\TextInput::make('featuring')->label('共演者・アーティスト')->maxLength(255),
                        Forms\Components\TextInput::make('alternative_title')->label('別表記')->maxLength(255),
                    ])->collapsible()->collapsed()->columnSpanFull(),
                ])->defaultItems(0)->live()->reorderable()
                    ->itemLabel(function (array $state, $component, Forms\Get $get): ?string {
                        $items = (array) $get('data.setlist', true);
                        foreach ((array) $get('data._encore_groups', true) as $group) $items = array_merge($items, (array) ($group['songs'] ?? []));
                        $number = 0;
                        foreach ($items as $item) {
                            $skipped = !empty($item['is_daily']) || !empty($item['medley']);
                            if (($item['_uuid'] ?? null) === ($state['_uuid'] ?? null)) return $skipped ? '' : (string) ($number + 1);
                            if (!$skipped) $number++;
                        }
                        return null;
                    })
                    ->addActionLabel('曲を追加')->columnSpanFull(),
            ])
            ->defaultItems(1)->live()->reorderable()
            ->itemLabel(function (array $state, $component): ?string {
                $position = 0;
                $groups = (array) $component->getState();
                if (count($groups) <= 1) return 'アンコール';
                foreach ($groups as $group) {
                    $position++;
                    if (($group['_uuid'] ?? null) === ($state['_uuid'] ?? null)) return 'アンコール' . $position;
                }
                return null;
            })
            ->addActionLabel('アンコールを追加')->columnSpanFull();
    }

    protected static ?string $navigationLabel = 'セットリスト';

    protected static ?string $modelLabel = 'セットリスト';

    protected static ?string $navigationGroup = 'Database';

    protected static ?int $navigationSort = 21;

    // 管理者は公式のセットリスト、マイページの人は自分のセットリスト（AdminData）
    public static function getModel(): string
    {
        return AdminData::model('setlist');
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return AdminData::query('setlist');
    }

    public static function tourManagementUrl($tourId): string
    {
        $setlists = AdminData::query('setlist')->ofTour($tourId)->limit(2)->get(['id']);

        if ($setlists->count() === 1) {
            return static::getUrl('edit', ['record' => $setlists->first()->id]);
        }

        return static::getUrl('manage-tour', ['tourId' => $tourId]);
    }

    // そのアーティストの曲（セットリストの曲の選択肢）
    protected static function songQuery($artistId)
    {
        return AdminData::query('song')->when($artistId, fn ($q, $id) => $q->ofArtists(static::songArtistIds($id)));
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('基本情報')
                    ->schema([
                        Forms\Components\Select::make('_artist_id')
                            ->label('アーティスト')
                            ->default(fn () => request()->query('artist_id')
                                ?? AdminData::query('concert')->find(request()->query('tour_id'))?->artist_id)
                            ->options(fn() => AdminData::artistOptions())
                            ->searchable()
                            ->native(false)
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                // アーティストを切り替えたら、今のツアー選択とスケジュール候補をクリアする。
                                // さもないと、以前選んでいた別アーティストの tour_id が残り、
                                // 例のように同名・近いタイトルのツアーが並ぶケースで「選んだのに変わらない」状態になる。
                                $set('tour_id', null);
                                $set('_schedule_pick', []);
                            })
                            ->afterStateHydrated(function ($state, $set, $record) {
                                if ($record && $record->tour) {
                                    $set('_artist_id', $record->tour->artist_id);
                                }
                            })
                            ->dehydrated(false),

                        Forms\Components\Select::make('tour_id')
                            ->label('ツアー')
                            ->default(fn () => request()->query('tour_id'))
                            ->options(fn(Get $get) => AdminData::query('concert')->when(
                                $get('_artist_id'),
                                fn($q, $artistId) => $q->ofArtist($artistId)
                            )->orderBy('date1', 'asc')->pluck('title', 'id'))
                            ->live()
                            // 読み書きしてよいツアーだけ
                            ->in(fn () => AdminData::query('concert')->pluck('id')->map(fn ($id) => (string) $id)->all())
                            ->searchable()
                            ->native(false)
                            ->afterStateUpdated(function ($state, Forms\Set $set, Get $get): void {
                                if (!$state || filled($get('order_no'))) return;

                                $row = (int) ($get('row') ?: 1);
                                $set('order_no', (int) AdminData::query('setlist')->ofTour($state)->where('row', $row)->max('order_no') + 1);
                            })
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
                            ->default(fn (Get $get) => $get('tour_id')
                                ? (int) AdminData::query('setlist')->ofTour($get('tour_id'))->where('row', (int) ($get('row') ?: 1))->max('order_no') + 1
                                : null)
                            ->required()
                            ->rules(fn(Get $get, $record) => [
                                Rule::unique(AdminData::table('setlist'), 'order_no')
                                    ->where(AdminData::column('setlist', 'tour_id'), $get('tour_id'))
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
                                $title = AdminData::rowTitle($record->tour_id, $record->row, $record->order_no);
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

                Forms\Components\Tabs::make('setlist_input_mode')
                    ->contained(false)
                    ->extraAttributes(['class' => 'setlist-input-tabs'])
                    ->tabs([
                        Forms\Components\Tabs\Tab::make('通常入力')
                            ->schema([
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
                                        static::songQuery($get('../../_artist_id'))->get()->mapWithKeys(fn($song) => [$song->id => static::songOptionLabel($song)])->all()
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
                                        $options = static::songQuery($get('../../_artist_id'))
                                            ->whereRaw('LOWER(title) LIKE LOWER(?)', ["%{$search}%"])
                                            ->get()
                                            ->mapWithKeys(fn($song) => [$song->id => static::songOptionLabel($song)])
                                            ->all();

                                        return array_slice(\App\Support\JapaneseNameSorter::sortOptions($options), 0, 50, true);
                                    })
                                    ->getOptionLabelUsing(function ($value) {
                                        if (is_numeric($value)) {
                                            $song = AdminData::query('song')->find($value);
                                            return $song ? static::songOptionLabel($song) : $value;
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
                                            ->label('日替わり (2曲目以降)')
                                            ->default(false)
                                            ->inline(false)
                                            ->live(),

                                        Forms\Components\Toggle::make('medley')
                                            ->label('メドレー (2曲目以降)')
                                            ->default(false)
                                            ->live()
                                            ->inline(false)
                                            ->live(),
                                        // メドレーにチェックしたときだけ出す（タイトルのあるメドレーは少ない）。メドレーのどの曲に入れても、メドレーの曲の上にタイトルの行を出し、曲番はその行に付ける
                                        Forms\Components\TextInput::make('medley_title')
                                            ->label('メドレーのタイトル')
                                            ->visible(fn (Forms\Get $get) => (bool) $get('medley'))
                                            ->maxLength(255),

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
                                                // 共演したときだけ、曲のアーティスト欄（credit。例：絢香 × コブクロ）を「曲名 / アーティスト」で出す（ふだんは出さない）
                                                'credit' => 'アーティスト表記あり',
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
                        static::encoreGroupsField(),
                    ])
                    ->columns(1),
                            ]),
                        Forms\Components\Tabs\Tab::make('一括入力')
                            ->schema([
                                Forms\Components\Textarea::make('_bulk_input')
                                    ->label('本編・アンコール')
                                    ->rows(30)
                                    ->placeholder("曲名を1行ずつ入力\n\n")
                                    ->helperText('本編とアンコールの間に空行を1行入れてください。')
                                    ->dehydrated(false)
                                    ->live(debounce: 500)
                                    ->afterStateUpdated(fn ($state, Forms\Get $get, Forms\Set $set) => static::syncBulkSetlist($state, $get, $set))
                                    ->columnSpanFull(),
                                Forms\Components\Hidden::make('_bulk_setlist_uuids')->default([])->dehydrated(false),
                                Forms\Components\Hidden::make('_bulk_encore_uuids')->default([])->dehydrated(false),
                                Forms\Components\Hidden::make('_bulk_encore_items')->default([])->dehydrated(false),
                            ]),
                    ])
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
                    ->options(fn() => AdminData::artistOptions())
                    ->query(fn($query, array $data) =>
                        $data['value']
                            ? $query->whereHas('tour', fn($q) => $q->ofArtist($data['value']))
                            : $query
                    )
                    ->searchable(),
                Tables\Filters\SelectFilter::make('tour')
                    // 選択肢は読み書きしてよいツアーだけ（マイページの人は自分のツアー）
                    ->options(fn () => AdminData::query('concert')->orderBy('date1', 'desc')->pluck('title', 'id')->all())
                    ->query(fn ($query, array $data) => filled($data['value']) ? $query->ofTour($data['value']) : $query)
                    ->label('ツアー')
                    ->searchable()
                    // 文字を入れなくても、開いたときに候補を出す
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\ReplicateAction::make()
                    ->modalHidden()
                    ->beforeReplicaSaved(function ($replica) {
                        $maxOrderNo = (int) AdminData::query('setlist')->ofTour($replica->tour_id)
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
            'manage-tour' => Pages\ManageTourSetlists::route('/tour/{tourId}'),
            'create' => Pages\CreateDbSetlist::route('/create'),
            'edit' => Pages\EditDbSetlist::route('/{record}/edit'),
        ];
    }

    // ツアーのスケジュール（「2021/5/1(土) 会場」「06/13 会場」「7月13日 会場」「10.6,7 会場」など）を、
    // パターンのタイトルに使う選択肢にする。表示は「月.日 会場名」、選んだときに入る文字（キー）は「月.日 地名」。
    // 同じ地名が2日以上あれば日付順に「仙台1」「仙台2」、単発のライブは日付だけ
    public static function scheduleOptions($tourId): array
    {
        $concert = $tourId ? AdminData::query('concert')->find($tourId) : null;
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
