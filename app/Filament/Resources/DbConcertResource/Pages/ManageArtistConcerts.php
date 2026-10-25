<?php

namespace App\Filament\Resources\DbConcertResource\Pages;

use App\Filament\Resources\DbConcertResource;
use App\Support\AdminData;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ManageArtistConcerts extends ListRecords
{
    protected static string $resource = DbConcertResource::class;

    public ?int $artistId = null;

    public function mount(): void
    {
        $artistId = request()->route('artistId');
        $this->artistId = filled($artistId) ? (int) $artistId : null;
        abort_if($this->artistId && !AdminData::query('artist')->whereKey($this->artistId)->exists(), 404);

        parent::mount();
    }

    public function getTitle(): string
    {
        $selectedArtistId = $this->selectedArtistId();
        $artistName = $selectedArtistId ? AdminData::query('artist')->find($selectedArtistId)?->name : null;

        return $artistName ? $artistName . ' ツアー・ライブ情報' : 'ツアー・ライブ情報';
    }

    private function selectedArtistId(): ?int
    {
        $filterState = $this->tableFilters ?? [];
        if (array_key_exists('value', $filterState['artist_id'] ?? [])) {
            return filled($filterState['artist_id']['value']) ? (int) $filterState['artist_id']['value'] : null;
        }

        return $this->artistId;
    }

    protected function getTableQuery(): ?Builder
    {
        return AdminData::query('concert');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('createConcert')
                ->label('ツアー・ライブ情報を追加')
                ->icon('heroicon-o-plus')
                ->url(fn () => DbConcertResource::getUrl('create', array_filter([
                    'artist_id' => $this->selectedArtistId(),
                    'continue_to_setlist' => 1,
                ], fn ($value) => $value !== null))),
        ];
    }

    public function table(Table $table): Table
    {
        return DbConcertResource::table($table)
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('ツアー・ライブ情報')
                    ->width('18rem')
                    ->limit(32)
                    ->tooltip(fn ($state) => $state)
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->label('タイプ')
                    ->formatStateUsing(fn ($state) => match ((int) $state) {
                        0 => 'ツアー', 1 => '単発ライブ', 2 => 'イベント', 3 => 'ap bank fes', 4 => 'ソロ', default => '-',
                    }),
                Tables\Columns\TextColumn::make('date1')->label('開始日')->date('Y.m.d')->sortable(),
                Tables\Columns\TextColumn::make('date2')->label('終了日')->date('Y.m.d')->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('artist_id')
                    ->label('アーティスト')
                    ->options(fn () => AdminData::artistOptions())
                    ->default($this->artistId)
                    ->searchable()
                    ->preload()
                    ->query(fn ($query, array $data) => filled($data['value']) ? $query->ofArtist($data['value']) : $query),
                Tables\Filters\SelectFilter::make('type')
                    ->label('種別')
                    ->options(fn () => [0 => 'ツアー', 1 => '単発ライブ', 2 => 'イベント'] + (AdminData::isMember() ? [] : [3 => 'ap bank fes', 4 => 'ソロ'])),
                Tables\Filters\SelectFilter::make('year')
                    ->label('年')
                    ->options(fn () => AdminData::query('concert')
                        ->whereNotNull('date1')
                        ->pluck('date1')
                        ->map(fn ($date) => \Illuminate\Support\Carbon::parse($date)->year)
                        ->unique()
                        ->sortDesc()
                        ->mapWithKeys(fn ($year) => [$year => (string) $year])
                        ->all())
                    ->query(fn ($query, array $data) => filled($data['value']) ? $query->whereYear('date1', $data['value']) : $query),
            ])
            ->actions([
                Tables\Actions\Action::make('manageTour')
                    ->label('セットリストを管理')
                    ->icon('heroicon-o-queue-list')
                    ->button()
                    ->url(fn ($record) => \App\Filament\Resources\DbSetlistResource::tourManagementUrl($record->id)),
                Tables\Actions\DeleteAction::make(),
            ])
            ->defaultSort('updated_at', 'desc');
    }
}
