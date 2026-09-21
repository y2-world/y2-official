/**
 * Infinite Scroll Implementation
 * ページネーション付きのページをインフィニティスクロールに変換
 *
 * 注意: ローカル環境のnode_modules（webpack5 / babel設定なし）でnpm run productionを
 * 実行すると、public/js/infinite-scroll.jsが本来のES5トランスパイル済みビルドとは
 * 異なる（正しくトランスパイルされない）成果物で上書きされてしまうことを確認済み。
 * そのため、このファイルへの変更（スクロール位置復元機能）は、正しいビルド環境が
 * 整うまでの間、public/js/infinite-scroll-restore.js に同等のロジックを手動で
 * 追記する形で反映している。正しくビルドできる環境が整い次第、このファイルから
 * public/js/infinite-scroll.js を再ビルドし、infinite-scroll-restore.js は削除すること。
 */

class InfiniteScroll {
    constructor(options) {
        console.log('InfiniteScroll initialized with options:', options);

        this.container = document.querySelector(options.container);
        this.nextPageUrl = options.nextPageUrl;
        this.loading = false;
        this.hasMore = true;
        this.debugMode = false; // デバッグモード無効
        this.debugEl = null;

        // 詳細ページ等に遷移してブラウザバックで戻った際、読み込み済みページ数と
        // スクロール位置を復元するためのsessionStorageキー（URLのpathごとに分ける）
        this.storageKey = 'infiniteScroll:' + window.location.pathname;
        this.loadedPages = 1;
        this.restoring = false;

        if (!this.container) {
            console.error('Container not found:', options.container);
            return;
        }

        console.log('Container found:', this.container);
        console.log('Next page URL:', this.nextPageUrl);

        // ページネーションを非表示
        const pagination = document.querySelector('.pagination, #pagination-links');
        if (pagination) {
            console.log('Hiding pagination:', pagination);
            pagination.style.display = 'none';
        } else {
            console.log('Pagination element not found');
        }

        // デバッグ表示を作成
        if (this.debugMode) {
            this.createDebugDisplay();
        }

        this.init();
    }

    init() {
        console.log('Initializing scroll listener...');

        // スクロールイベントをリスン
        const scrollHandler = () => {
            requestAnimationFrame(() => this.handleScroll());
            this.saveState();
        };

        window.addEventListener('scroll', scrollHandler, { passive: true });
        document.addEventListener('scroll', scrollHandler, { passive: true });

        // ローディングインジケーターを作成
        this.createLoadingIndicator();

        // 保存された読み込み状態があれば、そこまで先読みしてからスクロール位置を復元する。
        // なければ通常の初回チェックのみ行う
        this.restoreState().then(() => {
            // 初回チェック
            setTimeout(() => this.handleScroll(), 200);
        });

        // モバイル用の定期チェック（1秒ごと）
        setInterval(() => this.handleScroll(), 1000);

        // ページを離れる直前にも保存しておく（詳細ページへのリンククリック等）
        window.addEventListener('pagehide', () => this.saveState());
    }

    saveState() {
        try {
            sessionStorage.setItem(this.storageKey, JSON.stringify({
                loadedPages: this.loadedPages,
                scrollY: window.pageYOffset || document.documentElement.scrollTop || 0,
            }));
        } catch (e) {
            // プライベートブラウジング等でsessionStorageが使えない場合は諦める
        }
    }

    async restoreState() {
        let saved = null;
        try {
            const raw = sessionStorage.getItem(this.storageKey);
            if (raw) saved = JSON.parse(raw);
        } catch (e) {
            return;
        }

        if (!saved || !saved.loadedPages || saved.loadedPages <= 1) {
            return;
        }

        this.restoring = true;
        const targetPages = saved.loadedPages;
        while (this.loadedPages < targetPages && this.hasMore) {
            await this.loadMore();
        }
        this.restoring = false;

        // コンテンツの高さが確定してからでないと正しい位置にスクロールできないため、
        // 描画を1フレーム待ってからスクロールする
        requestAnimationFrame(() => {
            window.scrollTo(0, saved.scrollY || 0);
        });
    }

    createLoadingIndicator() {
        this.loadingEl = document.createElement('div');
        this.loadingEl.className = 'loading-indicator';
        this.loadingEl.style.cssText = 'text-align: center; padding: 40px 20px; display: none; width: 100%;';
        this.loadingEl.innerHTML = `
            <div class="spinner" style="border: 4px solid #f3f3f3; border-top: 4px solid #333; border-radius: 50%; width: 40px; height: 40px; animation: spin 1s linear infinite; margin: 0 auto;"></div>
            <p style="margin-top: 10px; color: #666;">Loading...</p>
            <style>
                @keyframes spin {
                    0% { transform: rotate(0deg); }
                    100% { transform: rotate(360deg); }
                }
            </style>
        `;
        // テーブルの後に追加するため、親要素（table）の親に追加
        this.container.closest('table').parentElement.appendChild(this.loadingEl);
    }

    createDebugDisplay() {
        this.debugEl = document.createElement('div');
        this.debugEl.style.cssText = 'position: fixed; top: 0; right: 0; background: rgba(0,0,0,0.8); color: #0f0; padding: 10px; font-size: 12px; z-index: 9999; max-width: 300px; font-family: monospace;';
        document.body.appendChild(this.debugEl);
        this.updateDebug('Initialized');
    }

    updateDebug(message) {
        if (!this.debugMode || !this.debugEl) return;
        const time = new Date().toLocaleTimeString();
        this.debugEl.innerHTML = `
            <div>[${time}] ${message}</div>
            <div>Loading: ${this.loading}</div>
            <div>HasMore: ${this.hasMore}</div>
            <div>NextURL: ${this.nextPageUrl ? 'yes' : 'no'}</div>
        `;
    }

    handleScroll() {
        if (this.loading || !this.hasMore) {
            return;
        }

        // モバイル対応：scrollHeight を使用
        const scrollTop = window.pageYOffset || document.documentElement.scrollTop || document.body.scrollTop || 0;
        const windowHeight = window.innerHeight || document.documentElement.clientHeight;
        const documentHeight = Math.max(
            document.body.scrollHeight,
            document.body.offsetHeight,
            document.documentElement.clientHeight,
            document.documentElement.scrollHeight,
            document.documentElement.offsetHeight
        );

        const scrollPosition = scrollTop + windowHeight;
        const threshold = documentHeight - 500;

        this.updateDebug(`Scroll: ${Math.round(scrollPosition)}/${Math.round(threshold)}`);

        if (scrollPosition >= threshold) {
            console.log('Loading more items...');
            this.updateDebug('Threshold reached!');
            this.loadMore();
        }
    }

    async loadMore() {
        if (!this.nextPageUrl || this.loading) return;

        console.log('Loading more from:', this.nextPageUrl);

        this.loading = true;
        // スクロール位置復元のための先読み中は、ローディング表示を出さない
        // （一瞬で何度も表示・非表示が切り替わってチラつくのを避ける）
        if (!this.restoring) {
            this.loadingEl.style.display = 'block';
        }

        try {
            const response = await fetch(this.nextPageUrl, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            });

            console.log('Response status:', response.status);

            if (!response.ok) {
                throw new Error('Network response was not ok');
            }

            const data = await response.json();
            console.log('Received data:', data);

            // HTMLをコンテナに追加
            if (data.html) {
                // tbodyに直接appendするため、insertAdjacentHTMLを使用
                this.container.insertAdjacentHTML('beforeend', data.html);
            }

            this.loadedPages += 1;

            // 次のページURLを更新（HTTPSに変換）
            if (data.next_page_url && window.location.protocol === 'https:') {
                this.nextPageUrl = data.next_page_url.replace('http://', 'https://');
            } else {
                this.nextPageUrl = data.next_page_url;
            }
            this.hasMore = data.next_page_url !== null;

        } catch (error) {
            console.error('Error loading more items:', error);
        } finally {
            this.loading = false;
            this.loadingEl.style.display = 'none';
        }
    }
}

// 使用方法:
// new InfiniteScroll({
//     container: '#items-container',
//     nextPageUrl: 'http://example.com/api/items?page=2'
// });

window.InfiniteScroll = InfiniteScroll;
