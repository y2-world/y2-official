<div
    x-data="draftAutosave({ draftKey: window.location.pathname })"
    x-cloak
>
    <div
        x-show="banner"
        x-transition
        style="position: sticky; top: 0; z-index: 40; margin-bottom: 1rem; padding: 12px 16px; background: #fef3c7; border: 1px solid #f59e0b; border-radius: 8px; display: flex; align-items: center; justify-content: space-between; gap: 12px;"
    >
        <span style="color: #78350f; font-size: 14px;">
            保存されていない下書きが見つかりました。復元しますか？
        </span>
        <div style="display: flex; gap: 8px; flex-shrink: 0;">
            <button
                type="button"
                x-on:click="restore()"
                style="padding: 6px 14px; background: #f59e0b; color: white; border: none; border-radius: 6px; font-size: 13px; cursor: pointer;"
            >復元する</button>
            <button
                type="button"
                x-on:click="clearDraft()"
                style="padding: 6px 14px; background: transparent; color: #78350f; border: 1px solid #f59e0b; border-radius: 6px; font-size: 13px; cursor: pointer;"
            >破棄する</button>
        </div>
    </div>
</div>
