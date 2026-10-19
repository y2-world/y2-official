{{-- 楽曲の並べ替えは行全体をつかめるため、行を触ると並べ替えが始まって画面をスクロールできない。左の「≡」だけでつかめるようにする --}}
<style>
    .fi-ta-row.cursor-move { cursor: auto; }
</style>
<script>
    (() => {
        const handle = '.fi-icon-btn.cursor-move';
        const fix = () => document.querySelectorAll('.fi-ta [x-sortable]').forEach((el) => {
            if (el.sortable && el.sortable.options.handle !== handle) el.sortable.option('handle', handle);
        });
        // 並べ替えを始めると表が作り直されるので、触った瞬間（Sortable より先）にも直す
        document.addEventListener('pointerdown', fix, true);
        document.addEventListener('touchstart', fix, { capture: true, passive: true });
        new MutationObserver(fix).observe(document.body, { childList: true, subtree: true });
    })();
</script>
