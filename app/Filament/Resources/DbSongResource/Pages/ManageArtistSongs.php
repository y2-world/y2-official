<?php

namespace App\Filament\Resources\DbSongResource\Pages;

use App\Filament\Resources\DbSongResource;
use App\Support\AdminData;
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
        // 読み書きしてよいアーティストだけ（マイページの人は自分のアーティスト）
        abort_unless(AdminData::query('artist')->whereKey($this->artistId)->exists(), 404);

        parent::mount();
    }

    public function getTitle(): string
    {
        return (AdminData::query('artist')->find($this->artistId)?->name ?? '') . ' 楽曲管理';
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
                        ->autofocus()
                        ->maxLength(255),
                    Forms\Components\Textarea::make('text')
                        ->label('説明')
                        ->rows(5),
                    Forms\Components\Select::make('sl_song_id')
                        // Yuki's Setlists の曲との紐付けは公式の曲だけ
                        ->visible(fn () => AdminData::supports('slSong'))
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

                    $model = AdminData::model('song');
                    $record = $model::create([
                        'title' => $data['title'],
                        'text' => $data['text'] ?? null,
                        'artist_id' => $this->artistId,
                        'sort_order' => (AdminData::query('song')->ofArtists([$this->artistId])->max('sort_order') ?? -1) + 1,
                    ]);

                    if ($slSongId && AdminData::supports('slSong')) {
                        \App\Models\SlSong::where('id', $slSongId)->update(['db_song_id' => $record->id]);
                    }

                    \Filament\Notifications\Notification::make()
                        ->title('楽曲を追加しました')
                        ->success()
                        ->send();

                    if ($arguments['another'] ?? false) {
                        $form->fill();
                        // 続けて曲名を打てるよう、ダイアログのタイトルの欄にカーソルを置く（フォームを入れ直したあとに置くため、少し待つ）
                        $this->js("setTimeout(() => document.querySelector('.fi-modal-window input[id$=\".title\"]')?.focus(), 100)");
                        $action->halt();
                    }
                })
                ->modalSubmitActionLabel('作成')
                ->extraModalFooterActions(fn (Actions\Action $action) => [
                    $action->makeModalSubmitAction('createAnother', arguments: ['another' => true])
                        ->label('保存して、続けて作成'),
                ]),
            // 曲名を1行に1曲ずつ書いて、まとめて登録する。並び順は書いた順に今の曲の後ろへ。
            // 空の行は飛ばし、そのアーティストに同じ名前の曲があれば作らない（通知で知らせる）
            Actions\Action::make('addSongsBulk')
                ->label('楽曲を一括追加')
                ->icon('heroicon-o-queue-list')
                ->color('gray')
                ->modalHeading('楽曲を一括追加')
                ->form([
                    Forms\Components\Textarea::make('titles')
                        ->label('タイトル')
                        ->helperText('1行に1曲ずつ入力してください。書いた順に、今の曲の後ろへ並びます。')
                        ->rows(15)
                        ->required()
                        ->autofocus(),
                ])
                ->modalSubmitActionLabel('追加する')
                ->action(function (array $data) {
                    $titles = array_values(array_unique(array_filter(array_map('trim', preg_split('/\R/u', (string) $data['titles'])), fn ($t) => $t !== '')));
                    $existing = AdminData::query('song')->ofArtists([$this->artistId])->pluck('title')->all();
                    $model = AdminData::model('song');
                    $sortOrder = (AdminData::query('song')->ofArtists([$this->artistId])->max('sort_order') ?? -1) + 1;
                    $created = [];
                    $skipped = [];
                    foreach ($titles as $title) {
                        if (in_array($title, $existing, true)) {
                            $skipped[] = $title;
                            continue;
                        }
                        $model::create(['title' => $title, 'artist_id' => $this->artistId, 'sort_order' => $sortOrder++]);
                        $created[] = $title;
                    }

                    \Filament\Notifications\Notification::make()
                        ->title(count($created) . ' 曲を追加しました')
                        ->body($skipped ? '同じ名前の曲があるので追加しなかった曲：' . implode('、', $skipped) : null)
                        ->success()
                        ->send();
                }),
        ];
    }

    public function table(Table $table): Table
    {
        return DbSongResource::table($table)
            ->query(DbSongResource::getEloquentQuery()->ofArtists([$this->artistId]))
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('タイトル')
                    ->searchable(),
                Tables\Columns\IconColumn::make('sl_songs_linked')
                    ->visible(fn () => AdminData::supports('slSong'))
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
