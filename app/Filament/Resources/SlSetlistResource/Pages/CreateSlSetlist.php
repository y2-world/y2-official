<?php

namespace App\Filament\Resources\SlSetlistResource\Pages;

use App\Filament\Concerns\HasDraftAutosave;
use App\Filament\Resources\SlSetlistResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;

class CreateSlSetlist extends CreateRecord
{
    use HasDraftAutosave;

    protected static string $resource = SlSetlistResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return SlSetlistResource::applyBulkRegistration($data, $this->form->getRawState());
    }

    protected function getCreateFormAction(): Action
    {
        // 登録ボタンはふつう「フォームを送る」ボタン（submit）で、それだと確認のダイアログが出ずにすぐ登録される。
        // 押したら処理を呼ぶボタン（action）にして、合致しない曲があるときだけダイアログを出す
        return $this->configureUnmatchedBulkConfirmation(parent::getCreateFormAction()->submit(null)->action(fn () => $this->create()), 'この内容で登録');
    }

    protected function getCreateAnotherFormAction(): Action
    {
        return $this->configureUnmatchedBulkConfirmation(parent::getCreateAnotherFormAction()->action(fn () => $this->createAnother()), 'この内容で登録');
    }

    private function configureUnmatchedBulkConfirmation(Action $action, string $submitLabel): Action
    {
        return $action
            ->requiresConfirmation(fn (): bool => SlSetlistResource::shouldConfirmUnmatchedBulkSongs($this->form->getRawState()))
            // 見出し・文を設定するとダイアログ付きのボタンになるので、合致しない曲が無いときはダイアログを出さずにそのまま登録する
            ->modalHidden(fn (): bool => !SlSetlistResource::shouldConfirmUnmatchedBulkSongs($this->form->getRawState()))
            ->modalHeading('未登録の楽曲があります')
            ->modalDescription(fn () => SlSetlistResource::unmatchedBulkSongsModalDescription($this->form->getRawState()))
            ->modalSubmitActionLabel($submitLabel);
    }

    protected function afterCreate(): void
    {
        $this->clearDraftAutosave();
    }
}
