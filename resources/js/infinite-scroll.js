/**
 * Infinite Scroll Implementation
 * ページネーション付きのページをインフィニティスクロールに変換
 */

class InfiniteScroll {
    constructor(options) {
        console.log('InfiniteScroll initialized with options:', options);

        this.container = document.querySelector(options.container);
        this.nextPageUrl = options.nextPageUrl;
        this.loading = false;
        this.hasMore = true;
        this.debugMode = true; // デバッグモード
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

        // スクロールイベントをリスン（デスクトップ用）
        window.addEventListener('scroll', () => {
            this.updateDebug('Scroll event fired');
            this.handleScroll();
        }, { passive: true });

        // ドキュメントのスクロールイベントも試す
        document.addEventListener('scroll', () => {
            this.updateDebug('Document scroll event');
            this.handleScroll();
        }, { passive: true });

        // ローディングインジケーターを作成
        this.createLoadingIndicator();

        // 初回チェック（ページが短くてスクロールバーがない場合に対応）
        setTimeout(() => {
            this.updateDebug('Initial check');
            this.handleScroll();
        }, 100);

        // 定期的にチェック（モバイル対応 - 500msごと）
        setInterval(() => this.handleScroll(), 500);
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

            // 次のページURLを更新
            this.nextPageUrl = data.next_page_url;
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
