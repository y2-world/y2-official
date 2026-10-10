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
        // 開いているタブ（見えているもの）の中で、いちばん上の曲までスクロールする
        var first = links.find(function (a) { return a.offsetParent !== null; });
        if (first) {
            setTimeout(function () { first.scrollIntoView({ behavior: 'smooth', block: 'center' }); }, 300);
        }
        setTimeout(function () {
            links.forEach(function (a) { a.classList.add('is-fading'); });
        }, 3000);
        setTimeout(function () {
            links.forEach(function (a) { a.classList.remove('song-find-highlight', 'is-fading'); });
        }, 4200);
    });
    </script>
@endif
