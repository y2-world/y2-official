<?php

namespace App\Filament\Resources\SlSetlistResource\Pages;

use App\Filament\Concerns\HasDraftAutosave;
use App\Filament\Resources\SlSetlistResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSlSetlist extends EditRecord
{
    use HasDraftAutosave;

    protected static string $resource = SlSetlistResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $encore = array_values((array) ($data['encore'] ?? []));
        $data['_encore_groups'] = \App\Support\EncoreFormGroups::fromItems($encore);
        $data['_bulk_encore_items'] = $encore;
        $data['_bulk_encore_uuids'] = array_column($encore, '_uuid');
        $data['_bulk_setlist_uuids'] = array_column((array) ($data['setlist'] ?? []), '_uuid');
        // 登録済みの曲を、一括入力にも入れておく（本編・アンコール、フェス）
        $data['_bulk_live_input'] = SlSetlistResource::bulkLiveTextFromItems((array) ($data['setlist'] ?? []), $encore);
        // フェスは、アーティストごとのまとまり（type が block）だけ。1曲ずつの古い形（type が song）は一括入力に出さず、そのまま残す
        $blockUuids = fn ($items) => array_column(array_filter((array) $items, fn ($b) => ($b['type'] ?? 'block') === 'block'), '_uuid');
        $data['_bulk_fes_setlist_uuids'] = $blockUuids($data['fes_setlist'] ?? []);
        $data['_bulk_fes_encore_uuids'] = $blockUuids($data['fes_encore'] ?? []);
        $data['_bulk_fes_input'] = SlSetlistResource::bulkFesTextFromBlocks((array) ($data['fes_setlist'] ?? []), (array) ($data['fes_encore'] ?? []));
        unset($data['encore']);
        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $rawState = $this->form->getRawState();
        if (SlSetlistResource::usedBulkInput($rawState)) {
            return SlSetlistResource::applyBulkRegistration($data, $rawState);
        }

        return SlSetlistResource::mergeEncoreFromForm($data);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        $this->clearDraftAutosave();
    }
}
