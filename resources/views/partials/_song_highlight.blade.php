{{-- 曲のページからライブ・セットリストのページに来たとき（?from=song-… / slsong-…）、その曲名を Chrome のページ内検索のように黄色で目立たせる。
     パターンがいくつもあると、どこにあるか気づきにくいため。いちばん上のものまでスクロールし、数秒で消える。
     $highlightPaths：目立たせる曲のリンク先のパス（例：/database/songs/123）。空なら何もしない --}}
@if (!empty($highlightPaths))
    <style>
        a.song-find-highlight {
            background: #ffe95c;
            border-radius: 3px;
            box-shadow: 0 0 0 2px #ffe95c;
            transition: background-color 1s ease, box-shadow 1s ease;
        }
        a.song-find-highlight.is-fading {
            background: transparent;
            box-shadow: 0 0 0 2px transparent;
        }
    </style>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        var paths = @json(array_values($highlightPaths));
        var links = Array.prototype.filter.call(document.querySelectorAll('a[href]'), function (a) {
            try {
                return paths.indexOf(new URL(a.href, location.href).pathname) !== -1;
            } catch (e) {
                return false;
            }
        });
        if (!links.length) return;
        links.forEach(function (a) { a.classList.add('song-find-highlight'); });
        // 開いているタブ（見えているもの）の中で、いちばん最初の曲のパターンまでスクロールする
        var first = links.find(function (a) { return a.offsetParent !== null; });

        // 曲名が、上の固定メニューの下から画面の下まで・横にスクロールする段の見えている幅の中に、まるごと入っているか
        function isOnScreen(el) {
            var rect = el.getBoundingClientRect();
            var header = document.querySelector('nav.fixed-top');
            var headerBottom = header ? header.getBoundingClientRect().bottom : 0;
            if (rect.top < headerBottom || rect.bottom > window.innerHeight) return false;
            var row = el.closest('.setlist-row');
            var left = row ? Math.max(0, row.getBoundingClientRect().left) : 0;
            var right = row ? Math.min(window.innerWidth, row.getBoundingClientRect().right) : window.innerWidth;
            return rect.left >= left && rect.right <= right;
        }

        // 縦のスクロール先：曲のある段（グループ）の頭。スマホで段の見出しが上に固定されるときは、その高さぶん下げる（ライブのページのパターンへのジャンプと同じ）
        function rowTop(el) {
            var header = document.querySelector('nav.fixed-top');
            var headerBottom = header ? header.getBoundingClientRect().bottom : 0;
            var row = el.closest('.setlist-row');
            var groupWrap = el.closest('.setlist-group-wrap');
            var anchor = groupWrap || row || el;
            var stickyTitle = row ? row.previousElementSibling : null;
            var offset = 12;
            if (window.matchMedia('(max-width: 767px)').matches && stickyTitle && stickyTitle.classList.contains('setlist-group-title-sticky')) {
                offset = stickyTitle.offsetHeight + (parseFloat(window.getComputedStyle(stickyTitle).marginTop) || 0) + 4;
            } else if (groupWrap && groupWrap.querySelector('.setlist-group-title')) {
                offset = 30;
            }
            return Math.max(0, window.scrollY + anchor.getBoundingClientRect().top - headerBottom - offset);
        }

        function centerFirst() {
            if (!first) return;
            var isMobile = window.matchMedia('(max-width: 767px)').matches;
            // PC もスマホも、1段のセットリストは1曲目から見えるので、曲の位置ではなく曲のあるパターンの頭から見せる。
            // スマホは、曲がもう画面に見えているときは動かさない（見えていないときだけ動かす）
            if (isMobile && isOnScreen(first)) return;
            var row = first.closest('.setlist-row');
            var wrap = first.closest('.setlist-pattern-wrap') || first;
            // 横：スマホは1画面に1パターン（幅は画面の8割）なので、真ん中に合わせると両隣が少しずつ見えて中途半端になる。
            // 1つ目のパターンを開いたときと同じく、パターンの左端（外側の余白ぶん）を画面の左にそろえる。PC はパターンを段の真ん中に。
            // 横と縦を同時になめらかに動かすと片方が止まることがあるので、横はすぐに動かす
            if (row && row.scrollWidth > row.clientWidth) {
                var rowRect = row.getBoundingClientRect();
                var wrapRect = wrap.getBoundingClientRect();
                var margin = parseFloat(window.getComputedStyle(wrap).marginLeft) || 0;
                row.scrollLeft = isMobile
                    ? row.scrollLeft + wrapRect.left - rowRect.left - margin
                    : row.scrollLeft + wrapRect.left - rowRect.left - (rowRect.width - wrapRect.width) / 2;
            }
            // 縦：その段の頭まで
            window.scrollTo({ top: rowTop(first), behavior: 'smooth' });
        }

        // 画像・フォントが読み込まれて位置が決まってから（パターンへのジャンプと同じく load とフォントのあと2フレーム待つ）
        var loaded = new Promise(function (resolve) {
            if (document.readyState === 'complete') resolve(); else window.addEventListener('load', resolve, { once: true });
        });
        var fontsReady = document.fonts && document.fonts.ready ? document.fonts.ready : Promise.resolve();
        Promise.all([loaded, fontsReady]).then(function () {
            requestAnimationFrame(function () { requestAnimationFrame(centerFirst); });
            // 黄色は見えるようになってから数秒で消す
            setTimeout(function () {
                links.forEach(function (a) { a.classList.add('is-fading'); });
            }, 3500);
            setTimeout(function () {
                links.forEach(function (a) { a.classList.remove('song-find-highlight', 'is-fading'); });
            }, 4700);
        });
    });
    </script>
@endif
