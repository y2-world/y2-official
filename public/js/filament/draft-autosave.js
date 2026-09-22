// Filamentのフォーム入力をブラウザのlocalStorageへ定期的に自動保存し、
// 通信断・保存失敗でページを再読み込みした場合でも入力内容を復元できるようにする。
// localStorageへの読み書きはネットワークを一切使わないため、オフラインでも確実に動作する。
document.addEventListener('alpine:init', () => {
    Alpine.data('draftAutosave', (config) => ({
        key: 'filament-draft:' + config.draftKey,
        debounceTimer: null,
        banner: null,

        init() {
            this.banner = this.loadDraft();

            this.$watch('$wire.data', () => {
                clearTimeout(this.debounceTimer);
                this.debounceTimer = setTimeout(() => this.saveDraft(), 1500);
            });

            this.$wire.$on('draft-autosave-clear', () => this.clearDraft());
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
            if (!this.banner) return;
            await this.$wire.call('restoreDraftData', this.banner.data);
            this.clearDraft();
        },

        clearDraft() {
            try {
                localStorage.removeItem(this.key);
            } catch (e) {
                // 何もしない
            }
            this.banner = null;
        },
    }));
});
