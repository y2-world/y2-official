{{-- 日付の欄（data-typed-date を付けた入力欄）：押すとそのままカレンダーが出て、数字で打つこともできる（flatpickr）。
     「20261025」「2026.10.25」「2026/10/25」「2026-10-25」を読み、欄には「2026.10.25」で入れる --}}
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/flatpickr/4.6.13/flatpickr.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/flatpickr/4.6.13/flatpickr.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/flatpickr/4.6.13/l10n/ja.min.js"></script>
<script>
    (() => {
        const parse = (text) => {
            const s = String(text || '').trim();
            const m = s.match(/^(\d{4})(\d{2})(\d{2})$/) || s.match(/^(\d{4})\s*[.\/\-年]\s*(\d{1,2})\s*[.\/\-月]\s*(\d{1,2})\s*日?$/);
            if (!m) return null;
            const d = new Date(Number(m[1]), Number(m[2]) - 1, Number(m[3]));
            return d.getMonth() === Number(m[2]) - 1 ? d : null;
        };
        const attach = (el) => {
            if (el._typedDate || typeof flatpickr === 'undefined') return;
            el._typedDate = flatpickr(el, {
                locale: flatpickr.l10ns.ja,
                allowInput: true,
                // スマホでもこのカレンダーを使う（スマホ標準の日付の欄に入れ替えると、管理画面の欄に日付が入らないため）
                disableMobile: true,
                dateFormat: 'Y.m.d',
                parseDate: (text) => parse(text),
                // 開くたびに欄の今の日付を読み直す（Filament は、この部品を付けたあとで欄に保存済みの日付を入れるため）。その日のカレンダーを出す
                onOpen: (dates, str, fp) => {
                    const current = parse(el.value);
                    if (current) {
                        fp.setDate(current, false);
                        fp.jumpToDate(current);
                    }
                },
                // 選んだり打ったりした日付を、Livewire（wire:model）に知らせる
                onChange: () => el.dispatchEvent(new Event('input', { bubbles: true })),
                onClose: (dates, str, fp) => {
                    const typed = parse(el.value);
                    if (typed) fp.setDate(typed, true);
                    el.dispatchEvent(new Event('input', { bubbles: true }));
                },
            });
        };
        const scan = () => document.querySelectorAll('input[data-typed-date]').forEach(attach);
        document.addEventListener('DOMContentLoaded', scan);
        document.addEventListener('livewire:navigated', scan);
        new MutationObserver(scan).observe(document.documentElement, { childList: true, subtree: true });
    })();
</script>
