<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DbSongResource\Pages;
use App\Filament\Resources\DbSongResource\RelationManagers;
use App\Models\DbSong;
use App\Models\DbAlbum;
use App\Support\AdminData;
use Illuminate\Database\Eloquent\Model;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class DbSongResource extends Resource
{
    protected static ?string $model = DbSong::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Database';

    protected static ?int $navigationSort = 25;

    protected static ?string $navigationLabel = '楽曲';

    protected static ?string $modelLabel = '楽曲';

    // 管理者は公式の曲、マイページの人は自分のアーティストの曲（AdminData）
    public static function getModel(): string
    {
        return AdminData::model('song');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('artist_id')
                    ->label('アーティスト')
                    ->options(fn() => AdminData::artistOptions())
                    ->in(fn () => array_keys(AdminData::artistOptions()))
                    ->required()
                    ->native(false)
                    ->searchable()
                    ->live(),
                Forms\Components\TextInput::make('title')
                    ->label('タイトル')
                    ->required()
                    ->maxLength(255),
                // 曲名にアーティストを入れずに済むように（例：あなたと → 絢香 × コブクロ）。セットリストでは「曲名 / アーティスト」
                Forms\Components\TextInput::make('credit')
                    ->label('アーティスト表記名')
                    ->placeholder('例：絢香 × コブクロ')
                    ->maxLength(255),
                Forms\Components\Textarea::make('text')
                    ->label('説明')
                    ->rows(5)
                    ->columnSpanFull(),

                // 曲一覧・曲詳細ページの「収録アルバム」「収録シングル」表示は、本来は
                // 日付が最も早いリリースを自動選択するが、同じ曲がリミックス盤等の別
                // リリースにも収録されていて自動判定だけでは正しい初出を選べない場合に、
                // ここで明示的に上書き指定できる（未指定なら自動選択のまま）
                Forms\Components\Select::make('album_id')
                    ->label('収録アルバム（手動指定）')
                    ->helperText('未指定の場合、この曲を収録するアルバムのうち発売日が最も早いものが自動的に表示されます')
                    ->options(function (Get $get) {
                        $artistId = $get('artist_id');
                        if (!$artistId) {
                            return [];
                        }
                        return AdminData::query('album')->ofArtist($artistId)->orderBy('date')->pluck('title', 'id')->all();
                    })
                    ->searchable()
                    ->preload()
                    ->native(false)
                    ->placeholder('自動選択')
                    ->nullable(),
                Forms\Components\Select::make('single_id')
                    ->label('収録シングル（手動指定）')
                    ->helperText('未指定の場合、この曲を収録するシングルのうち発売日が最も早いものが自動的に表示されます')
                    ->options(function (Get $get) {
                        $artistId = $get('artist_id');
                        if (!$artistId) {
                            return [];
                        }
                        return AdminData::query('single')->ofArtist($artistId)->orderBy('date')->pluck('title', 'id')->all();
                    })
                    ->searchable()
                    ->preload()
                    ->native(false)
                    ->placeholder('自動選択')
                    ->nullable(),

                // DbSongとSlSongは基本1対1（バージョン違いはセットリスト側の別カラムで表現するため、
                // SlSongのタイトルは常にオリジナルタイトルになる）。DbSong::slSongs()自体はhasMany定義だが、
                // ここでは単一選択のみを許可する。Filament標準のrelationship()保存は
                // HasMany + multiple()に対応していないため、素のフィールドとして持たせ、
                // Pages側のmutateFormDataBeforeSave/afterCreateでsl_song_idキーを取り出して同期する。
                Forms\Components\Select::make('sl_song_id')
                    // Yuki's Setlists の曲との紐付けは公式の曲だけ
                    ->visible(fn () => AdminData::supports('slSong'))
                    ->label('セットリスト楽曲との紐付け')
                    ->helperText('この楽曲に対応するセットリスト楽曲。タイトル一致で自動的にも紐付きます')
                    ->options(function (Get $get) {
                        $artistId = $get('artist_id');
                        if (!$artistId) {
                            return [];
                        }
                        return \App\Support\JapaneseNameSorter::sortOptions(
                            \App\Models\SlSong::where('artist_id', $artistId)->pluck('title', 'id')->all()
                        );
                    })
                    // 編集画面ではEditRecord::fillFormWithDataAndCallHooksが$record->attributesToArray()を
                    // まるごとLivewireのdataにdata_setするため、default()の分岐（$hydratedDefaultStateがnullの
                    // 場合のみ使われる）を通らず、モデル属性に無いsl_song_idは常にnullのまま復元されない。
                    // afterStateHydratedは常に呼ばれるフックなので、ここで確実に現在の紐付けを反映する。
                    ->afterStateHydrated(function (Forms\Components\Select $component, ?Model $record) {
                        if (!AdminData::supports('slSong')) {
                            return;
                        }
                        $component->state($record?->slSongs->first()?->id);
                    })
                    ->dehydrated()
                    ->searchable()
                    ->preload()
                    ->native(false)
                    ->placeholder('選択してください')
                    ->nullable()
                    ->columnSpanFull(),

                // 1対1が前提のため、通常はslSongsが2件以上になることはない。もし2件以上ある場合、
                // 上のSelectはslSongs->first()しか表示・操作できず、2件目以降が画面外に隠れたまま
                // 誰にも気づかれず残ってしまう（実際に過去のデータ不整合で発生した）。
                // ここで隠れている分を明示的に警告表示し、その場で解除できるようにする。
                Forms\Components\Placeholder::make('extra_sl_songs_warning')
                    ->label('')
                    ->columnSpanFull()
                    ->visible(fn (?Model $record) => AdminData::supports('slSong') && $record && $record->slSongs->count() > 1)
                    ->content(function (?Model $record) {
                        if (!$record) {
                            return '';
                        }
                        $extras = $record->slSongs->skip(1);
                        $list = $extras->map(fn ($s) => "「{$s->title}」(id: {$s->id})")->implode('、');
                        return new \Illuminate\Support\HtmlString(
                            '<div class="rounded-lg bg-danger-50 p-3 text-sm text-danger-700 dark:bg-danger-500/10 dark:text-danger-400">'
                            . "この楽曲には上記以外にも {$list} が紐付いたままになっています（本来1対1のはずが異常な状態です）。"
                            . '下の一覧からそれぞれ個別に紐付け解除してください。'
                            . '</div>'
                        );
                    }),

                Forms\Components\Repeater::make('extra_sl_songs')
                    ->label('その他の紐付き（要解除）')
                    ->columnSpanFull()
                    ->visible(fn (?Model $record) => AdminData::supports('slSong') && $record && $record->slSongs->count() > 1)
                    ->schema([
                        Forms\Components\Hidden::make('id'),
                        Forms\Components\TextInput::make('title')
                            ->label('セットリスト楽曲')
                            ->disabled(),
                    ])
                    ->afterStateHydrated(function (Forms\Components\Repeater $component, ?Model $record) {
                        if (!$record || !AdminData::supports('slSong')) {
                            return;
                        }
                        $component->state(
                            $record->slSongs->skip(1)->map(fn ($s) => ['id' => $s->id, 'title' => $s->title])->values()->all()
                        );
                    })
                    ->addable(false)
                    ->reorderable(false)
                    ->deletable(true)
                    ->deleteAction(
                        fn (Forms\Components\Actions\Action $action) => $action
                            ->label('この紐付けを解除')
                            ->requiresConfirmation(),
                    )
                    ->dehydrated(),
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
                    ->searchable(),
                Tables\Columns\IconColumn::make('sl_songs_linked')
                    ->visible(fn () => AdminData::supports('slSong'))
                    ->label('セットリスト楽曲紐付け')
                    ->icon(fn ($record) => $record->slSongs->isNotEmpty() ? 'heroicon-o-check-circle' : null)
                    ->color('success')
                    ->getStateUsing(fn ($record) => $record->slSongs->isNotEmpty()),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('Y.m.d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime('Y.m.d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('artist_id')
                    ->label('アーティスト')
                    ->options(fn() => AdminData::artistOptions())
                    // マイページのテーブルは項目名が違うので、どちらのモデルにもある ofArtists で絞る
                    ->query(fn ($query, array $data) => filled($data['value']) ? $query->ofArtists([$data['value']]) : $query)
                    ->searchable(),
                Tables\Filters\TernaryFilter::make('sl_songs_linked')
                    ->visible(fn () => AdminData::supports('slSong'))
                    ->label('セットリスト楽曲紐付け状態')
                    ->nullable()
                    ->trueLabel('紐付け済み')
                    ->falseLabel('未紐付け')
                    ->queries(
                        true: fn ($query) => $query->whereHas('slSongs'),
                        false: fn ($query) => $query->whereDoesntHave('slSongs'),
                    ),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->before(fn ($action, $record) => static::blockDeleteIfUsed($action, $record)),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->before(fn ($action, $records) => static::blockBulkDeleteIfUsed($action, $records)),
                ]),
            ])
            ->defaultSort('id', 'desc');
    }

    // 使われている曲は消さない（消すとアルバム・シングル・セットリストに番号だけが残り、画面に数字で出るため）
    public static function blockDeleteIfUsed($action, Model $record): void
    {
        // 使われ方の調べ（SongUsage）は公式の曲のテーブルを見る。マイページの曲は、マイページと同じく調べずに消す
        if (AdminData::isMember()) {
            return;
        }
        $usages = \App\Support\SongUsage::forDbSong($record->id);
        if ($usages) {
            \Filament\Notifications\Notification::make()->danger()->title('削除できません')
                ->body(\App\Support\SongUsage::message($record->title, $usages))->persistent()->send();
            $action->cancel();
        }
    }

    public static function blockBulkDeleteIfUsed($action, \Illuminate\Support\Collection $records): void
    {
        if (AdminData::isMember()) {
            return;
        }
        $messages = $records->map(fn ($r) => ($u = \App\Support\SongUsage::forDbSong($r->id)) ? \App\Support\SongUsage::message($r->title, $u) : null)->filter();
        if ($messages->isNotEmpty()) {
            \Filament\Notifications\Notification::make()->danger()->title('削除できません（何も削除していません）')
                ->body($messages->implode("\n\n"))->persistent()->send();
            $action->cancel();
        }
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = AdminData::query('song');

        return AdminData::supports('slSong') ? $query->with('slSongs') : $query;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDbSongs::route('/'),
            'create' => Pages\CreateDbSong::route('/create'),
            'edit' => Pages\EditDbSong::route('/{record}/edit'),
            'manage-artist' => Pages\ManageArtistSongs::route('/manage/{artistId}'),
        ];
    }
}
