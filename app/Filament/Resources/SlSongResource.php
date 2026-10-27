<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SlSongResource\Pages;
use App\Models\DbSong;
use App\Models\SlSong;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SlSongResource extends Resource
{
    // 管理者だけの画面
    use \App\Filament\Concerns\AdminOnly;

    protected static ?string $model = SlSong::class;

    protected static ?string $navigationIcon = 'heroicon-o-musical-note';

    protected static ?string $navigationLabel = 'セットリスト楽曲';

    protected static ?string $modelLabel = 'セットリスト楽曲';

    protected static ?string $navigationGroup = "Yuki's Setlists";

    protected static ?int $navigationSort = 11;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('title')
                    ->label('曲名')
                    ->required()
                    ->maxLength(255),

                // 公開していないアーティスト（フェスで見ただけなど）の曲もあるので、全アーティストから選ぶ
                Forms\Components\Select::make('artist_id')
                    ->label('アーティスト')
                    ->relationship('artist', 'name', fn($query) => $query->orderBy('id'))
                    ->searchable()
                    ->preload()
                    ->native(false)
                    ->live()
                    ->nullable(),

                // 曲名にアーティストを入れずに済むように（データベース楽曲と同じ）。グループのメンバーのソロ曲など。
                // アーティストにはしないので、アーティストの候補には出ない。セットリストでは「アーティスト表記あり」の行だけ「曲名 / アーティスト」
                Forms\Components\TextInput::make('credit')
                    ->label('アーティスト表記名')
                    ->placeholder('例：藤ヶ谷太輔')
                    ->maxLength(255),

                Forms\Components\Select::make('db_song_id')
                    ->label('database楽曲との紐付け')
                    ->helperText('スタンプ帳でこの曲がライブ演奏済みと判定されるために必要です')
                    ->options(function (Forms\Get $get) {
                        $artistId = $get('artist_id');
                        if (!$artistId) return [];
                        return \App\Support\JapaneseNameSorter::sortOptions(
                            DbSong::where('artist_id', $artistId)->pluck('title', 'id')->all()
                        );
                    })
                    ->searchable()
                    ->preload()
                    ->native(false)
                    ->nullable(),
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
                    ->label('曲名')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('artist.name')
                    ->label('アーティスト')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\IconColumn::make('db_song_id')
                    ->label('DB紐付け')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->getStateUsing(fn(SlSong $record) => $record->db_song_id !== null),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('作成日')
                    ->dateTime('Y.m.d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('更新日')
                    ->dateTime('Y.m.d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('artist')
                    ->relationship('artist', 'name', fn($query) => $query->where('visible', 1)->orderBy('id'))
                    ->label('アーティスト')
                    ->searchable()
                    // 文字を入れなくても、開いたときに候補を出す
                    ->preload(),
                Tables\Filters\TernaryFilter::make('db_song_id')
                    ->label('DB紐付け状態')
                    ->nullable()
                    ->trueLabel('紐付け済み')
                    ->falseLabel('未紐付け')
                    ->queries(
                        true: fn($query) => $query->whereNotNull('db_song_id'),
                        false: fn($query) => $query->whereNull('db_song_id'),
                    ),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->before(fn ($action, SlSong $record) => static::blockDeleteIfUsed($action, $record)),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->before(fn ($action, $records) => static::blockBulkDeleteIfUsed($action, $records)),
                ]),
            ])
            ->defaultSort('id', 'desc');
    }

    // 使われている曲は消さない（消すとセットリストに番号だけが残り、画面に数字で出るため）
    public static function blockDeleteIfUsed($action, SlSong $record): void
    {
        $usages = \App\Support\SongUsage::forSlSong($record->id);
        if ($usages) {
            \Filament\Notifications\Notification::make()->danger()->title('削除できません')
                ->body(\App\Support\SongUsage::message($record->title, $usages))->persistent()->send();
            $action->cancel();
        }
    }

    public static function blockBulkDeleteIfUsed($action, \Illuminate\Support\Collection $records): void
    {
        $messages = $records->map(fn (SlSong $r) => ($u = \App\Support\SongUsage::forSlSong($r->id)) ? \App\Support\SongUsage::message($r->title, $u) : null)->filter();
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSlSongs::route('/'),
            'create' => Pages\CreateSlSong::route('/create'),
            'edit' => Pages\EditSlSong::route('/{record}/edit'),
        ];
    }
}
