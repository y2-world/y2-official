{{-- 曲の行（.setlist-song-row）をドラッグで並べ替える。曲の並べ替え（manage/songs）と同じ動き：
     「≡」をつかんだときだけ動かし（スマホは少し長押ししてから）、画面の端まで持っていくと自動でスクロールする。
     動かした行は枠の色を変えて残す。makeSongRowsSortable(並べる箱, 並びが変わったときの処理) で使う。
     曲の行のほか、セットリストのパターンのカード（.manage-row）も、draggable / handle を渡して同じ動きで並べ替える --}}
<style>
.manage-drag-handle {
    flex-shrink: 0;
    color: #bbb;
    cursor: grab;
    padding: 6px 8px 6px 2px;
    touch-action: none;
}
.manage-drag-handle:active { cursor: grabbing; }
/* 長押しでつかむので、「≡」ではスマホの長押しメニュー（コピーなど）や文字の選択を出さない */
.manage-drag-handle,
.manage-drag-handle * { -webkit-touch-callout: none; user-select: none; -webkit-user-select: none; }
/* 動かし済みの行 */
.setlist-song-row.is-moved, .manage-row.is-moved { border: 2px solid #a99cf0 !important; background: #faf8ff; }
/* 移動先：うすい紫の背景と点線の枠（中身は薄く） */
.setlist-song-row.is-drag-ghost, .manage-row.is-drag-ghost { background: #f3f0ff !important; border: 2px dashed #667eea !important; }
.setlist-song-row.is-drag-ghost > *, .manage-row.is-drag-ghost > * { opacity: 0.35; }
/* 動かしている行 */
.setlist-song-row.is-drag-chosen, .manage-row.is-drag-chosen { box-shadow: 0 6px 18px rgba(102, 126, 234, 0.3); border: 2px solid #667eea !important; }
/* 指（マウス）について動く行 */
.setlist-song-row.is-drag-fallback, .manage-row.is-drag-fallback { opacity: 0.95 !important; box-shadow: 0 10px 24px rgba(102, 126, 234, 0.35); border: 2px solid #667eea !important; transition: none !important; }
/* ドラッグ中は、ページの端での引っ張り（引っ張って更新など）・文字の選択・長押しメニューを止め、ボタンや入力欄を押せなくする */
html.is-song-dragging, body.is-song-dragging { overscroll-behavior: none; }
body.is-song-dragging, body.is-song-dragging * { user-select: none !important; -webkit-user-select: none !important; -webkit-touch-callout: none !important; }
body.is-song-dragging button, body.is-song-dragging input, body.is-song-dragging a { pointer-events: none !important; }
</style>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.2/Sortable.min.js"></script>
<script>
(function () {
    const isDragging = () => document.body.classList.contains('is-song-dragging');
    ['copy', 'cut', 'contextmenu', 'selectstart'].forEach((type) => {
        document.addEventListener(type, (e) => { if (isDragging()) e.preventDefault(); });
    });
    document.addEventListener('touchmove', (e) => { if (isDragging()) e.preventDefault(); }, { passive: false });

    // 同じ箱に何度呼んでもよい（行を足すたびに呼べる）
    window.makeSongRowsSortable = function (container, onChange, options = {}) {
        const handle = options.handle || '.manage-drag-handle';
        if (!container || container.dataset.sortable === '1' || !window.Sortable) return;
        container.dataset.sortable = '1';
        // 「≡」を押した瞬間に、ブラウザの標準の動き（文字の選択・長押しメニュー・スクロール）を止める
        container.addEventListener('touchstart', (e) => {
            if (e.target.closest(handle)) e.preventDefault();
        }, { passive: false });
        container.addEventListener('contextmenu', (e) => {
            if (e.target.closest(handle)) e.preventDefault();
        });
        Sortable.create(container, {
            handle,
            draggable: options.draggable || '.setlist-song-row',
            animation: 150,
            delay: 150,
            delayOnTouchOnly: true,
            ghostClass: 'is-drag-ghost',
            chosenClass: 'is-drag-chosen',
            forceFallback: true,
            fallbackOnBody: false,
            fallbackClass: 'is-drag-fallback',
            fallbackTolerance: 3,
            scroll: true,
            forceAutoScrollFallback: true,
            scrollSensitivity: 80,
            scrollSpeed: 12,
            onStart: () => {
                document.documentElement.classList.add('is-song-dragging');
                document.body.classList.add('is-song-dragging');
            },
            onEnd: (e) => {
                document.documentElement.classList.remove('is-song-dragging');
                document.body.classList.remove('is-song-dragging');
                window.getSelection()?.removeAllRanges();
                if (e.oldIndex !== e.newIndex) {
                    e.item.classList.add('is-moved');
                    onChange && onChange();
                }
            },
        });
    };
})();
</script>
