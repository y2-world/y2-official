// Filamentのフォーム入力をブラウザのlocalStorageへ定期的に自動保存し、
// 通信断・保存失敗でページを再読み込みした場合でも入力内容を復元できるようにする。
// localStorageへの読み書きはネットワークを一切使わないため、オフラインでも確実に動作する。
document.addEventListener('alpine:init', () => {
    Alpine.data('draftAutosave', (config) => ({
        key: 'filament-draft:' + config.draftKey,
        debounceTimer: null,
        draft: null,

        init() {
            this.draft = this.loadDraft();

            if (this.draft) {
                // 通知トースターで知らせる（画面上部の固定バナーはスクロールで見逃されるため、
                // Filament標準のトースター通知＝目立つ場所に一定時間表示される仕組みを使う）
                this.notifyDraftFound();
            }

            this.$watch('$wire.data', () => {
                clearTimeout(this.debounceTimer);
                this.debounceTimer = setTimeout(() => this.saveDraft(), 1500);
            });

            // 通知アクションのdispatch()はLivewireの$dispatch経由で発火するため、
            // 素のDOM CustomEventではなくLivewire.on()で受け取る必要がある
            this.$wire.$on('draft-autosave-clear', () => this.clearDraft());
            Livewire.on('draft-autosave-restore', () => this.restore());
            Livewire.on('draft-autosave-discard', () => this.clearDraft());
        },

        notifyDraftFound() {
            new window.FilamentNotification()
                .title('保存されていない下書きがあります')
                .body('前回入力中に保存されなかった内容が見つかりました。復元しますか？')
                .warning()
                .persistent()
                .actions([
                    new window.FilamentNotificationAction('restore')
                        .label('復元する')
                        .button()
                        .dispatch('draft-autosave-restore')
                        .close(),
                    new window.FilamentNotificationAction('discard')
                        .label('破棄する')
                        .button()
                        .outlined()
                        .dispatch('draft-autosave-discard')
                        .close(),
                ])
                .send();
        },

        saveDraft() {
            try {
                const payload = {
                    data: this.$wire.data,
                    savedAt: Date.now(),
                };
                localStorage.setItem(this.key, JSON.stringify(payload));
            } catch (e) {
                // localStorageが使えない環境（プライベートモード等）では何もしない
            }
        },

        loadDraft() {
            try {
                const raw = localStorage.getItem(this.key);
                if (!raw) return null;
                return JSON.parse(raw);
            } catch (e) {
                return null;
            }
        },

        async restore() {
            if (!this.draft) return;
            await this.$wire.call('restoreDraftData', this.draft.data);
            this.clearDraft();
        },

        clearDraft() {
            try {
                localStorage.removeItem(this.key);
            } catch (e) {
                // 何もしない
            }
            this.draft = null;
        },
    }));
});
