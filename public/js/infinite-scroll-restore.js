(function () {
    'use strict';

    // infinite-scroll.js のビルド成果物（Babel/webpackでトランスパイル済み）を
    // 直接編集せずに、スクロール位置復元機能を後付けするパッチ。
    // 詳細ページ等に遷移してブラウザバックで戻った際、読み込み済みページ数と
    // スクロール位置をsessionStorageに保存・復元することで、先頭に戻ってしまう問題を防ぐ。
    if (typeof window.InfiniteScroll !== 'function') {
        return;
    }

    var proto = window.InfiniteScroll.prototype;
    var originalInit = proto.init;
    var originalLoadMore = proto.loadMore;
    var originalCreateLoadingIndicator = proto.createLoadingIndicator;

    proto.createLoadingIndicator = function () {
        if (this.loadingEl) {
            return;
        }
        originalCreateLoadingIndicator.call(this);
    };

    function storageKeyFor() {
        return 'infiniteScroll:' + window.location.pathname;
    }

    function saveState(instance) {
        try {
            sessionStorage.setItem(storageKeyFor(), JSON.stringify({
                loadedPages: instance.loadedPages || 1,
                scrollY: window.pageYOffset || document.documentElement.scrollTop || 0
            }));
        } catch (e) {
            // プライベートブラウジング等でsessionStorageが使えない場合は諦める
        }
    }

    function restoreState(instance) {
        var saved = null;
        try {
            var raw = sessionStorage.getItem(storageKeyFor());
            if (raw) saved = JSON.parse(raw);
        } catch (e) {
            return Promise.resolve();
        }

        if (!saved || !saved.loadedPages || saved.loadedPages <= 1) {
            return Promise.resolve();
        }

        instance.restoring = true;
        var targetPages = saved.loadedPages;

        function step() {
            if (instance.loadedPages >= targetPages || !instance.hasMore) {
                instance.restoring = false;
                requestAnimationFrame(function () {
                    window.scrollTo(0, saved.scrollY || 0);
                });
                return Promise.resolve();
            }
            return instance.loadMore().then(step);
        }

        return step();
    }

    proto.init = function () {
        this.loadedPages = this.loadedPages || 1;
        this.restoring = false;

        var self = this;
        var scrollHandler = function () {
            saveState(self);
        };
        window.addEventListener('scroll', scrollHandler, { passive: true });
        window.addEventListener('pagehide', scrollHandler);

        // restoreState()内でloadMore()を呼ぶ可能性があり、loadMore()はthis.loadingElを
        // 参照するため、元のinit()が呼ぶより前に自前で先に作っておく必要がある
        if (!this.loadingEl) {
            this.createLoadingIndicator();
        }

        // 元のinit()はローディングインジケーター作成・スクロール監視・初回チェックを行う。
        // 復元すべき状態がある場合は、先読みが終わってから元のinit()を呼ぶことで
        // 「先頭表示→復元」のチラつきを防ぐ
        restoreState(this).then(function () {
            originalInit.call(self);
        });
    };

    proto.loadMore = function () {
        var self = this;
        return originalLoadMore.apply(this, arguments).then(function () {
            self.loadedPages = (self.loadedPages || 1) + 1;
        });
    };
})();
