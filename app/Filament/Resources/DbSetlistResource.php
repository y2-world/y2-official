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
                            ->options(fn() => \App\Models\Artist::orderBy('name')->pluck('name', 'id'))
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

                        Forms\Components\TextInput::make('order_no')
                            ->label('パターン番号')
                            ->numeric()
                            ->required()
                            ->rules(fn(Get $get, $record) => [
                                Rule::unique('db_setlists', 'order_no')
                                    ->where('tour_id', $get('tour_id'))
                                    ->ignore($record?->id),
                            ])
                            ->validationMessages([
                                'unique' => 'このツアーではすでに使われているパターン番号です。',
                            ]),

                        Forms\Components\TextInput::make('subtitle')
                            ->label('サブタイトル（日付や説明）')
                            ->maxLength(255)
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
                                    ->options(fn(Get $get) => \App\Models\DbSong::when(
                                        $get('../../_artist_id'),
                                        fn($q, $id) => $q->where('artist_id', $id)
                                    )->orderBy('title')->pluck('title', 'id'))
                                    ->searchable()
                                    ->native(false)
                                    ->required()
                                    ->allowHtml()
                                    ->getSearchResultsUsing(function (string $search, Get $get) {
                                        return \App\Models\DbSong::when(
                                            $get('../../_artist_id'),
                                            fn($q, $id) => $q->where('artist_id', $id)
                                        )->where('title', 'like', "%{$search}%")
                                            ->orderBy('title')
                                            ->limit(50)
                                            ->pluck('title', 'id');
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

                                        Forms\Components\TextInput::make('daily_note')
                                            ->label('日替わり説明')
                                            ->placeholder('例: 9.30')
                                            ->maxLength(255),

                                        Forms\Components\TextInput::make('featuring')
                                            ->label('共演者・アーティスト')
                                            ->placeholder('例: ゲスト名')
                                            ->maxLength(255),

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
                                    $isDaily = !empty($item['is_daily']);
                                    $uuid = $item['_uuid'] ?? null;

                                    if ($uuid === $currentUuid) {
                                        if ($isDaily) {
                                            return '';
                                        } else {
                                            $number++;
                                            return (string)$number;
                                        }
                                    }

                                    if (!$isDaily) {
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
                                    ->options(fn(Get $get) => \App\Models\DbSong::when(
                                        $get('../../_artist_id'),
                                        fn($q, $id) => $q->where('artist_id', $id)
                                    )->orderBy('title')->pluck('title', 'id'))
                                    ->searchable()
                                    ->native(false)
                                    ->required()
                                    ->allowHtml()
                                    ->getSearchResultsUsing(function (string $search, Get $get) {
                                        return \App\Models\DbSong::when(
                                            $get('../../_artist_id'),
                                            fn($q, $id) => $q->where('artist_id', $id)
                                        )->where('title', 'like', "%{$search}%")
                                            ->orderBy('title')
                                            ->limit(50)
                                            ->pluck('title', 'id');
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
                                        Forms\Components\Toggle::make('is_daily')
                                            ->label('日替わり')
                                            ->default(false)
                                            ->inline(false)
                                            ->live(),

                                        Forms\Components\TextInput::make('daily_note')
                                            ->label('日替わり説明')
                                            ->placeholder('例: 9.30')
                                            ->maxLength(255),

                                        Forms\Components\TextInput::make('featuring')
                                            ->label('共演者・アーティスト')
                                            ->placeholder('例: ゲスト名')
                                            ->maxLength(255),

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
                                    if (empty($item['is_daily'])) {
                                        $setlistCount++;
                                    }
                                }

                                $items = $component->getState();
                                if (!is_array($items)) return (string)($setlistCount + 1);

                                $currentUuid = $state['_uuid'] ?? null;
                                if (!$currentUuid) return '?';

                                $number = $setlistCount;
                                foreach ($items as $item) {
                                    $isDaily = !empty($item['is_daily']);
                                    $uuid = $item['_uuid'] ?? null;

                                    if ($uuid === $currentUuid) {
                                        if ($isDaily) {
                                            return '';
                                        } else {
                                            $number++;
                                            return (string)$number;
                                        }
                                    }

                                    if (!$isDaily) {
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
                    ->options(fn() => \App\Models\Artist::orderBy('name')->pluck('name', 'id'))
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
                    ->beforeReplicaSaved(function ($replica) {
                        $replica->order_no = 0;
                        $replica->subtitle = null;
                    })
                    ->redirectTo(fn($replica) => static::getUrl('edit', ['record' => $replica])),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('id', 'desc');
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
}
