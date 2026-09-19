<?php

namespace App\Filament\Resources\DbSongResource\Pages;

use App\Filament\Resources\DbSongResource;
use App\Models\Artist;
use Filament\Actions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables;
use Filament\Tables\Table;

class ManageArtistSongs extends ListRecords
{
    protected static string $resource = DbSongResource::class;

    public int $artistId;

    public function mount(): void
    {
        $this->artistId = (int) request()->route('artistId');

        parent::mount();
    }

    public function getTitle(): string
    {
        return (Artist::find($this->artistId)?->name ?? '') . ' の楽曲管理';
    }

    protected function getHeaderActions(): array
    {
        return [
            // Filament標準のCreateAction（クラス自体）はListRecords::configureCreateAction()が
            // 「resourceにcreateページがあれば必ずそのURLへ遷移させる」よう自動でurl()を上書きしてしまい、
            // モーダルでの独自フォーム作成ができない。そのため汎用のActionを使う。
            Actions\Action::make('addSong')
                ->label('楽曲を追加')
                ->icon('heroicon-o-plus')
                ->form([
                    Forms\Components\TextInput::make('title')
                        ->label('タイトル')
                        ->required()
                        ->maxLength(255),
                    Forms\Components\Textarea::make('text')
                        ->label('説明')
                        ->rows(5),
                    Forms\Components\Select::make('sl_song_id')
                        ->label('セットリスト楽曲との紐付け')
                        ->helperText('この楽曲に対応するセットリスト楽曲。タイトル一致で自動的にも紐付きます')
                        ->options(fn () => \App\Support\JapaneseNameSorter::sortOptions(
                            \App\Models\SlSong::where('artist_id', $this->artistId)->pluck('title', 'id')->all()
                        ))
                        ->searchable()
                        ->preload()
                        ->native(false)
                        ->placeholder('選択してください')
                        ->nullable(),
                ])
                ->action(function (array $data, array $arguments, Form $form, Actions\Action $action) {
                    $slSongId = $data['sl_song_id'] ?? null;

                    $record = \App\Models\DbSong::create([
                        'title' => $data['title'],
                        'text' => $data['text'] ?? null,
                        'artist_id' => $this->artistId,
                        'sort_order' => (\App\Models\DbSong::where('artist_id', $this->artistId)->max('sort_order') ?? -1) + 1,
                    ]);

                    if ($slSongId) {
                        \App\Models\SlSong::where('id', $slSongId)->update(['db_song_id' => $record->id]);
                    }

                    \Filament\Notifications\Notification::make()
                        ->title('楽曲を追加しました')
                        ->success()
                        ->send();

                    if ($arguments['another'] ?? false) {
                        $form->fill();
                        $action->halt();
                    }
                })
                ->modalSubmitActionLabel('作成')
                ->extraModalFooterActions(fn (Actions\Action $action) => [
                    $action->makeModalSubmitAction('createAnother', arguments: ['another' => true])
                        ->label('保存して、続けて作成'),
                ]),
        ];
    }

    public function table(Table $table): Table
    {
        return DbSongResource::table($table)
            ->query(DbSongResource::getEloquentQuery()->where('artist_id', $this->artistId))
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('タイトル')
                    ->searchable(),
                Tables\Columns\IconColumn::make('sl_songs_linked')
                    ->label('セットリスト楽曲紐付け')
                    ->icon(fn ($record) => $record->slSongs->isNotEmpty() ? 'heroicon-o-check-circle' : null)
                    ->color('success')
                    ->getStateUsing(fn ($record) => $record->slSongs->isNotEmpty()),
            ])
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->filters([])
            ->paginated(false);
    }
}
