<?php

namespace App\Filament\Concerns;

trait HasDraftAutosave
{
    // ブラウザ側（draft-autosave.js）が localStorage の下書きを復元する際に呼ぶ。
    // 生のwire:model経由の値差し替えではなくform->fill()を通すことで、
    // Repeaterのアイテムキー（UUID）やDOMの再構築をFilament本来の仕組みに任せる。
    public function restoreDraftData(array $data): void
    {
        $this->form->fill($data);
    }

    // 保存が成功しページ遷移しない場合（バリデーションエラー無しの通常保存）に、
    // 役目を終えた下書きをlocalStorageから消すようJS側へ通知する。
    protected function clearDraftAutosave(): void
    {
        $this->dispatch('draft-autosave-clear');
    }
}
