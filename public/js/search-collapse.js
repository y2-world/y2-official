// 見出しの右上の検索窓（.song-search-top-right）が、タイトルなどの文字に重なるときは、
// スマホと同じ虫眼鏡アイコン（見出しの右下）に切り替える。押すと検索窓が開く
(function () {
    function textRects(el) {
        var range = document.createRange();
        range.selectNodeContents(el);
        return Array.prototype.slice.call(range.getClientRects());
    }

    // くっついて見えるのも重なりとみなす（8px 以内）
    var GAP = 8;
    function overlaps(a, b) {
        return a.left < b.right + GAP && b.left - GAP < a.right && a.top < b.bottom + GAP && b.top - GAP < a.bottom;
    }

    function setup(search) {
        var box = search.parentElement;
        var form = box.querySelector(':scope > [id^="spSearchForm"]');
        var button = box.querySelector(':scope > button[id^="spSearchButton"]');
        var created = false;
        if (!form) return;
        if (!button) {
            // アイコンが無いページ（アルバム・シングル）は、ほかのページと同じアイコンを作る
            button = document.createElement('button');
            button.type = 'button';
            button.innerHTML = '<i class="fa-solid fa-magnifying-glass" style="font-size: 14px;"></i>';
            button.style.cssText = 'position: absolute; bottom: -14px; right: 8px; background: rgba(255, 255, 255, 0.2); border: 1px solid rgba(255, 255, 255, 0.3); color: white; padding: 8px; border-radius: 50%; cursor: pointer; width: 36px; height: 36px; display: none; align-items: center; justify-content: center;';
            button.addEventListener('click', function () {
                form.style.display = form.style.display === 'block' ? 'none' : 'block';
            });
            box.appendChild(button);
            created = true;
        }
        if (getComputedStyle(box).position === 'static') box.style.position = 'relative';
        var collapsed = false;

        function update() {
            // スマホ（アイコンがもともと出る幅）は何もしない
            if (window.innerWidth < 992) {
                if (collapsed) expand();
                return;
            }
            if (collapsed) {
                search.style.display = '';
            }
            var rect = search.getBoundingClientRect();
            var hit = false;
            Array.prototype.forEach.call(box.children, function (child) {
                if (hit || child === search || child === form || child === button || !child.offsetParent) return;
                hit = textRects(child).some(function (r) { return r.width > 0 && overlaps(r, rect); });
            });
            if (hit) collapse(); else if (collapsed) expand();
            else search.style.display = '';
        }

        function collapse() {
            collapsed = true;
            search.style.display = 'none';
            form.classList.remove('sp');
            button.classList.remove('sp');
            if (created || form.style.display !== 'block') button.style.display = 'flex';
        }

        function expand() {
            collapsed = false;
            search.style.display = '';
            form.classList.add('sp');
            form.style.display = 'none';
            if (created) {
                button.style.display = 'none';
            } else {
                button.classList.add('sp');
                button.style.display = 'flex';
            }
        }

        update();
        window.addEventListener('resize', update);
        if (document.fonts && document.fonts.ready) document.fonts.ready.then(update);
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.song-search-top-right').forEach(setup);
    });
})();
