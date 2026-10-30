<?php

namespace App\Filament\Resources\DbSetlistResource\Pages;

use App\Filament\Concerns\HasDraftAutosave;
use App\Filament\Resources\DbSetlistResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\HtmlString;

class CreateDbSetlist extends CreateRecord
{
    use HasDraftAutosave;

    protected static string $resource = DbSetlistResource::class;

    private ?string $pendingRowTitle = null;
    public ?int $returnToTourId = null;
    // Yuki's Setlists の「パターンを追加」から開いたとき、その参加記録（曲を入れた状態で開き、保存のときにまとめて登録する）
    public ?int $fromSlSetlistId = null;

    public function mount(): void
    {
        $this->returnToTourId = request()->boolean('return_to_tour') && request()->query('tour_id')
            ? (int) request()->query('tour_id')
            : null;

        parent::mount();

        $slSetlist = request()->query('from_sl_setlist') ? \App\Models\SlSetlist::find((int) request()->query('from_sl_setlist')) : null;
        $tour = $slSetlist?->dbConcert;
        if ($slSetlist && $tour) {
            $this->fromSlSetlistId = $slSetlist->id;
            $this->form->fill(array_merge($this->form->getRawState(), DbSetlistResource::formDataFromSlSetlist($slSetlist, $tour->artist_id)));
        }
    }

    protected function getCreateFormAction(): Action
    {
        // 登録ボタンはふつう「フォームを送る」ボタン（submit）で、それだと確認のダイアログが出ずにすぐ登録される。
        // 押したら処理を呼ぶボタン（action）にして、合致しない曲があるときだけダイアログを出す
        return $this->configureUnmatchedConfirmation(parent::getCreateFormAction()->submit(null)->action(fn () => $this->create()));
    }

    protected function getCreateAnotherFormAction(): Action
    {
        return $this->configureUnmatchedConfirmation(parent::getCreateAnotherFormAction()->action(fn () => $this->createAnother()));
    }

    private function configureUnmatchedConfirmation(Action $action): Action
    {
        // 合わない曲があれば、共演者・アーティストを入れた曲（曲名の文字のまま入れる）も含めて確かめる
        $confirm = fn (): bool => DbSetlistResource::shouldConfirmUnmatchedBulkSongs($this->form->getRawState());

        return $action
            ->requiresConfirmation($confirm)
            // 見出し・文を設定するとダイアログ付きのボタンになるので、合致しない曲が無いときはダイアログを出さずにそのまま登録する
            ->modalHidden(fn (): bool => !$confirm())
            ->modalHeading('未一致の楽曲があります')
            ->modalDescription(fn () => $this->unmatchedModalDescription($this->form->getRawState()))
            ->modalSubmitActionLabel('この内容で登録');
    }

    // 「パターンを追加」から開いたときは、新しい曲として登録する曲と、曲名の文字のまま入れる曲（共演者・アーティストを入れた曲）を分けて出す
    private function unmatchedModalDescription(array $state): HtmlString
    {
        if (!$this->fromSlSetlistId) {
            return DbSetlistResource::unmatchedBulkSongsModalDescription($state);
        }
        $create = DbSetlistResource::unmatchedBulkSongs($state, true);
        $asText = array_values(array_diff(DbSetlistResource::unmatchedBulkSongs($state), $create));
        $parts = [];
        if ($create !== []) {
            $parts[] = '以下の楽曲がデータベースの楽曲と合致しませんでした。<br>' . implode('<br>', array_map('e', $create))
                . '<br>新たに楽曲を登録します。<br>新たに楽曲として登録したくない場合は、共演者・アーティストにカバーアーティスト名を入力してください。';
        }
        if ($asText !== []) {
            $parts[] = '以下の楽曲は、共演者・アーティストが入っているため、楽曲として登録せず曲名のまま登録します。<br>' . implode('<br>', array_map('e', $asText));
        }

        return new HtmlString(implode('<br><br>', $parts) . '<br>よろしいですか？');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // 「パターンを追加」から開いたときは、合わなかった曲をここで新しい曲として登録してつなぐ（保存するまで何も登録しない）
        if ($this->fromSlSetlistId && ($tour = \App\Models\DbConcert::find($data['tour_id'] ?? null))) {
            $state = $this->form->getRawState();
            $ids = [];
            foreach (DbSetlistResource::slSetlistSongsToCreate($state) as $title) {
                $ids[$title] = (string) \App\Models\DbSong::firstOrCreate(['artist_id' => $tour->artist_id, 'title' => $title])->id;
            }
            $link = fn (array $items) => array_map(fn ($item) => isset($ids[trim((string) ($item['song'] ?? ''))]) && empty($item['featuring'])
                ? ['song' => $ids[trim((string) $item['song'])]] + $item : $item, $items);
            $data['setlist'] = $link((array) ($data['setlist'] ?? []));
            $data['_encore_groups'] = array_map(fn ($group) => ['songs' => $link((array) ($group['songs'] ?? []))] + (array) $group, (array) ($data['_encore_groups'] ?? []));
        }

        $data = DbSetlistResource::mergeEncoreFormData($data);
        $this->pendingRowTitle = $data['row_title'] ?? null;
        unset($data['row_title']);

        // マイページの人が作るセットリストは、その人のもの
        return $data + \App\Support\AdminData::ownerAttributes('setlist');
    }

    // 「保存して次を作成」の直後だけ、続けて同じツアー・段のパターンを登録しやすいよう
    // 選択内容を引き継ぐ。一覧から改めて新規作成を開いた場合は未選択に戻る。
    public function create(bool $another = false): void
    {
        $state = $another ? $this->form->getRawState() : [];
        $artistId = $state['_artist_id'] ?? null;
        $tourId = $state['tour_id'] ?? null;
        $row = $state['row'] ?? 1;

        parent::create($another);

        if ($another && $artistId && $tourId) {
            $nextOrder = (int) \App\Support\AdminData::query('setlist')->ofTour($tourId)->where('row', $row)->max('order_no') + 1;
            $this->form->fill([
                '_artist_id' => $artistId,
                'tour_id' => $tourId,
                'row' => $row,
                'order_no' => $nextOrder,
            ]);
        }
    }

    protected function afterCreate(): void
    {
        $this->clearDraftAutosave();

        \App\Support\AdminData::setRowTitle($this->record->tour_id, $this->record->row, $this->record->order_no, $this->pendingRowTitle);
    }

    protected function getRedirectUrl(): string
    {
        if ($this->returnToTourId && $this->record) {
            return DbSetlistResource::getUrl('manage-tour', ['tourId' => $this->returnToTourId]);
        }

        return parent::getRedirectUrl();
    }
}
