// Filamentのフォーム入力をブラウザのlocalStorageへ定期的に自動保存し、
// 通信断・保存失敗でページを再読み込みした場合でも入力内容を復元できるようにする。
// localStorageへの読み書きはネットワークを一切使わないため、オフラインでも確実に動作する。
document.addEventListener('alpine:init', () => {
    Alpine.data('draftAutosave', (config) => ({
        key: 'filament-draft:' + config.draftKey,
        debounceTimer: null,
        draft: null,

        init() {
            // この機能はあくまで入力保全の補助であり、何らかの理由で失敗しても
            // Filament本来の保存・画面操作を絶対に妨げてはならないため、
            // セットアップ全体を try/catch で囲む。
            try {
                this.draft = this.loadDraft();

                if (this.draft) {
                    // 通知トースターで知らせる（画面上部の固定バナーはスクロールで見逃されるため、
                    // Filament標準のトースター通知＝目立つ場所に一定時間表示される仕組みを使う）。
                    // window.FilamentNotification自体は早期に定義されるが、それを実際に画面へ
                    // 描画するFilamentのNotificationsコンポーネント（Livewire）がまだページ上に
                    // マウントされていない状態で send() すると、CustomEventを誰も拾わず消える。
                    // ページの読み込みが完全に終わるまで送信を遅らせる。
                    this.runWhenPageReady(() => {
                        this.waitForNotificationApi(() => this.notifyDraftFound());
                    });
                }

                // $watch('$wire.data', ...) はオブジェクト参照自体の変化しか検知できず、
                // Livewireはフィールド単位（data.title等）で更新するため参照は変わらず発火しない。
                // JSON化した文字列を監視対象にすることで、ネストしたRepeater配列を含む
                // あらゆるフィールドの変更を確実に検知する。
                this.$watch(
                    () => JSON.stringify(this.$wire.data),
                    () => {
                        clearTimeout(this.debounceTimer);
                        this.debounceTimer = setTimeout(() => this.saveDraft(), 1500);
                    }
                );

                // 通知アクションのdispatch()はLivewireの$dispatch経由で発火するため、
                // 素のDOM CustomEventではなくLivewire.on()で受け取る必要がある
                this.$wire.$on('draft-autosave-clear', () => this.clearDraft());
                if (window.Livewire) {
                    window.Livewire.on('draft-autosave-restore', () => this.restore());
                    window.Livewire.on('draft-autosave-discard', () => this.clearDraft());
                }
            } catch (e) {
                console.error('[draft-autosave] init failed', e);
            }
        },

        runWhenPageReady(callback) {
            if (document.readyState === 'complete') {
                // 既にloadを過ぎている場合でも、Livewireコンポーネントのマウントが
                // 1テンポ遅れることがあるため少し余裕を持たせる
                setTimeout(callback, 300);
                return;
            }
            window.addEventListener('load', () => setTimeout(callback, 300), { once: true });
        },

        waitForNotificationApi(callback, attemptsLeft = 20) {
            if (window.FilamentNotification && window.FilamentNotificationAction) {
                callback();
                return;
            }
            if (attemptsLeft <= 0) {
                console.error('[draft-autosave] FilamentNotification API did not become available');
                return;
            }
            setTimeout(() => this.waitForNotificationApi(callback, attemptsLeft - 1), 100);
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
