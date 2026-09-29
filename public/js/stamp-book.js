document.addEventListener('DOMContentLoaded', function () {
    const book = document.querySelector('.stamp-book');
    if (!book) {
        return;
    }

    const slots = Array.from(book.querySelectorAll('.stamp-slot'));
    const select = document.getElementById('stampFilterSelect');
    const checkbox = document.getElementById('stampPerformedOnlyCheckbox');
    const doneEl = document.getElementById('stampSummaryDone');
    const totalEl = document.getElementById('stampSummaryTotal');
    const barFillEl = document.getElementById('stampSummaryBarFill');
    const percentageEl = document.getElementById('stampSummaryPercentage');
    const noMatchEl = document.getElementById('stampFilterNoMatch');
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    let swapTimer = null;

    slots.forEach(function (slot) {
        const titleEl = slot.querySelector('.stamp-slot-title');
        slot._titleEl = titleEl;
        slot._defaultTitle = titleEl ? titleEl.textContent : '';
        slot._trackTitles = slot.dataset.trackTitles ? JSON.parse(slot.dataset.trackTitles) : {};
    });

    function applyTitles(key) {
        slots.forEach(function (slot) {
            if (slot._titleEl) {
                slot._titleEl.textContent = slot._trackTitles[key] || slot._defaultTitle;
            }
        });
    }

    function matches(slot, key, performedOnly) {
        if (performedOnly && slot.dataset.neverPerformed === '1') {
            return false;
        }
        return !key || slot.dataset.filterKeys.split(' ').includes(key);
    }

    function updateSummary(visibleSlots) {
        const total = visibleSlots.length;
        const done = visibleSlots.filter(function (slot) { return slot.dataset.done === '1'; }).length;
        const percentage = total > 0 ? Math.round((done / total) * 1000) / 10 : 0;
        doneEl.textContent = done;
        totalEl.textContent = total;
        barFillEl.style.width = percentage + '%';
        percentageEl.textContent = percentage + '% complete';
    }

    function showSlots(visibleSlots, animate, key) {
        applyTitles(key);
        const visibleSet = new Set(visibleSlots);
        let order = 0;
        slots.forEach(function (slot) {
            const show = visibleSet.has(slot);
            slot.hidden = !show;
            slot.classList.remove('is-filter-in');
            if (show && animate) {
                // 件数が多いときに最後の方が出るまで待たされないよう、遅延は頭打ちにする
                slot.style.animationDelay = Math.min(order++, 24) * 18 + 'ms';
                void slot.offsetWidth;
                slot.classList.add('is-filter-in');
            }
        });
        if (noMatchEl) {
            noMatchEl.hidden = visibleSlots.length > 0;
        }
    }

    function apply(animate) {
        const key = select ? select.value : '';
        const performedOnly = checkbox ? checkbox.checked : false;
        const visibleSlots = slots.filter(function (slot) { return matches(slot, key, performedOnly); });
        updateSummary(visibleSlots);

        clearTimeout(swapTimer);
        if (!animate || reduceMotion) {
            book.classList.remove('is-filtering');
            showSlots(visibleSlots, false, key);
            return;
        }

        book.classList.add('is-filtering');
        swapTimer = setTimeout(function () {
            showSlots(visibleSlots, true, key);
            book.classList.remove('is-filtering');
        }, 180);
    }

    if (select) {
        select.addEventListener('change', function () { apply(true); });
    }
    if (checkbox) {
        checkbox.addEventListener('change', function () { apply(true); });
    }

    // 戻る操作でブラウザがセレクト・チェックの状態を復元した場合も表示と集計を合わせる
    apply(false);
});
