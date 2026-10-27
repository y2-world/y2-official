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
        // 登録・保存のあとはすぐ別の画面に移るので、上の合図が届かないことがある。
        // 次に開いた画面で、この画面の下書きを消す（filament/hooks/draft-autosave-clear）
        session()->flash('draft_autosave_clear', parse_url(static::getUrl(static::getUrlParameters($this)), PHP_URL_PATH));
    }

    private static function getUrlParameters($page): array
    {
        return method_exists($page, 'getRecord') && $page->getRecord() ? ['record' => $page->getRecord()] : [];
    }
}
