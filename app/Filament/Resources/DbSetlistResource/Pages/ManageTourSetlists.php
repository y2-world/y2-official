<?php

namespace App\Filament\Resources\DbSetlistResource\Pages;

use App\Filament\Resources\DbConcertResource;
use App\Filament\Resources\DbSetlistResource;
use App\Support\AdminData;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ManageTourSetlists extends ListRecords
{
    protected static string $resource = DbSetlistResource::class;

    public int $tourId;

    public function mount(): void
    {
        $this->tourId = (int) request()->route('tourId');
        abort_unless(AdminData::query('concert')->whereKey($this->tourId)->exists(), 404);

        parent::mount();
    }

    public function getTitle(): string
    {
        return AdminData::query('concert')->find($this->tourId)?->title ?? '';
    }

    public function getSubheading(): ?string
    {
        $tour = AdminData::query('concert')->with('artist')->find($this->tourId);

        return $tour?->artist?->name;
    }

    protected function getTableQuery(): ?Builder
    {
        return AdminData::query('setlist')->ofTour($this->tourId);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('createConcert')
                ->label('ツアー・ライブ情報を追加')
                ->icon('heroicon-o-plus')
                ->url(fn () => DbConcertResource::getUrl('create', [
                    'artist_id' => AdminData::query('concert')->find($this->tourId)?->artist_id,
                    'continue_to_setlist' => 1,
                ])),
            Actions\Action::make('editTour')
                ->label('ツアー・ライブ情報を編集')
                ->icon('heroicon-o-pencil-square')
                ->url(DbConcertResource::getUrl('edit', ['record' => $this->tourId])),
            Actions\Action::make('createSetlist')
                ->label('セットリストを追加')
                ->icon('heroicon-o-plus')
                ->url(DbSetlistResource::getUrl('create', [
                    'tour_id' => $this->tourId,
                    'return_to_tour' => 1,
                ])),
        ];
    }

    public function table(Table $table): Table
    {
        return DbSetlistResource::table($table)
            ->columns([
                Tables\Columns\TextColumn::make('row')->label('段')->sortable(),
                Tables\Columns\TextColumn::make('order_no')->label('パターン')->sortable(),
                Tables\Columns\TextColumn::make('subtitle')->label('サブタイトル')->searchable(),
                Tables\Columns\TextColumn::make('updated_at')->label('更新日')->dateTime('Y-m-d H:i')->sortable(),
            ])
            ->filters([])
            ->defaultSort(fn ($query) => $query->orderBy('row')->orderBy('order_no'));
    }
}
