/**
 * Infinite Scroll Implementation
 * ページネーション付きのページをインフィニティスクロールに変換
 */

class InfiniteScroll {
    constructor(options) {
        console.log('InfiniteScroll initialized with options:', options);

        // ブラウザの「戻る/進む」時、そのページで最後にいたスクロール位置を自動復元する
        // 標準機能を明示的に有効化しておく（history.replaceStateを使うため、一部ブラウザで
        // デフォルトがmanualに変わってしまう可能性への保険）。
        // これとURLへのpage反映（loadMore内）が揃って初めて、コンテンツの高さが復元先の
        // スクロール位置に足りる状態でページが再読み込みされ、位置復元が機能する
        if ('scrollRestoration' in history) {
            history.scrollRestoration = 'auto';
        }

        this.container = document.querySelector(options.container);
        this.nextPageUrl = options.nextPageUrl;
        this.loading = false;
        this.hasMore = true;
        this.debugMode = false; // デバッグモード無効
        this.debugEl = null;

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
        };

        window.addEventListener('scroll', scrollHandler, { passive: true });
        document.addEventListener('scroll', scrollHandler, { passive: true });

        // ローディングインジケーターを作成
        this.createLoadingIndicator();

        // 初回チェック
        setTimeout(() => this.handleScroll(), 200);

        // モバイル用の定期チェック（1秒ごと）
        setInterval(() => this.handleScroll(), 1000);
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
        this.loadingEl.style.display = 'block';

        try {
            // AJAX判定をヘッダーだけに頼らず、専用クエリパラメータでも明示する。
            // ブラウザの「戻る」操作が過去のfetchリクエスト（Acceptヘッダー等）を
            // そのまま再現してしまうケースがあり、ヘッダーのみの判定だとサーバー側が
            // 通常のページ遷移をAJAXと誤判定してJSONをそのまま表示してしまう事故が起きた。
            // このURLはhistory.replaceStateには使わない（あくまでfetch専用）ため、
            // ブラウザのアドレスバー・履歴にajax=1が残ることはない
            const fetchUrl = new URL(this.nextPageUrl, window.location.href);
            fetchUrl.searchParams.set('ajax', '1');

            const response = await fetch(fetchUrl, {
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

            // 次のページURLを更新（HTTPSに変換）
            if (data.next_page_url && window.location.protocol === 'https:') {
                this.nextPageUrl = data.next_page_url.replace('http://', 'https://');
            } else {
                this.nextPageUrl = data.next_page_url;
            }
            this.hasMore = data.next_page_url !== null;

            // 現在表示されている最終ページ番号をURLに反映しておく（履歴は増やさずreplace）。
            // これにより、詳細ページ等に遷移してブラウザの「戻る」で戻ってきたとき、
            // サーバー側がこのpage番号までの全件をまとめて返すため、読み込み済みだった分が
            // 保持された状態でロードされる（スクロール位置自体はブラウザ標準の挙動に任せる）
            if (data.current_page) {
                const url = new URL(window.location.href);
                url.searchParams.set('page', data.current_page);
                url.searchParams.delete('ajax');
                history.replaceState(history.state, '', url);
            }

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
