{{-- スタンプ帳のタップ・押す演出・長押しの枠の色選び（Yukiとマイページで共通） --}}
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.js-stamp-tap').forEach(function (frame) {
        // 長押し（0.5秒）で枠の色を選ぶ（編集できる人だけ）。指が動いたらスクロールとみなしてやめる
        let pressTimer = null;
        let pressStart = null;
        let longPressed = false;
        function cancelPress() {
            clearTimeout(pressTimer);
            pressTimer = null;
        }
        if (document.getElementById('stampDecoPickModal')) {
            frame.addEventListener('pointerdown', function (e) {
                longPressed = false;
                pressStart = { x: e.clientX, y: e.clientY };
                cancelPress();
                pressTimer = setTimeout(function () {
                    longPressed = true;
                    pressTimer = null;
                    openStampPicker(frame.closest('.stamp-slot'));
                }, 500);
            });
            frame.addEventListener('pointermove', function (e) {
                if (pressTimer && pressStart && Math.hypot(e.clientX - pressStart.x, e.clientY - pressStart.y) > 10) {
                    cancelPress();
                }
            });
            ['pointerup', 'pointercancel', 'pointerleave'].forEach(function (type) {
                frame.addEventListener(type, cancelPress);
            });
            // 長押しで出るメニュー（コピーなど）は出さない
            frame.addEventListener('contextmenu', function (e) { e.preventDefault(); });
        }
        frame.addEventListener('click', function () {
            // 長押しの後の指を離したときのクリックでは押す演出をしない
            if (longPressed) {
                longPressed = false;
                return;
            }
            frame.classList.remove('is-tapped');
            // リフローを挟んで再度クラスを付与し、同じアニメーションを連打でも毎回再生させる
            void frame.offsetWidth;
            frame.classList.add('is-tapped');
        });
        frame.addEventListener('animationend', function () {
            frame.classList.remove('is-tapped');
        });
    });

    // 済みスタンプは、スクロールして画面に入ってきた瞬間にパコンと押す
    const stampObserver = new IntersectionObserver(function (entries, observer) {
        entries.forEach(function (entry) {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-stamp-in');
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.4 });

    document.querySelectorAll('.stamp-slot.is-stamped .stamp-mark').forEach(function (mark) {
        stampObserver.observe(mark);
    });

    // ---- 枠の色とタイトル（編集できる人だけ） ----
    const editModal = document.getElementById('stampDecoEditModal');
    const pickModal = document.getElementById('stampDecoPickModal');
    let pickingSlot = null;

    // ふわっと出して、ふわっと消す
    // 長押しで開いたとき、指を離したときのクリックが、開いたばかりの画面の指の下の選択肢に当たらないよう、
    // 開いたあとに画面の中で新しく押されたクリックだけを受け付ける
    function openModal(modal) {
        modal._freshPress = false;
        modal.classList.remove('is-closing');
        modal.hidden = false;
        document.body.classList.add('stamp-deco-open');
    }
    function closeModal(modal) {
        if (modal.hidden || modal.classList.contains('is-closing')) {
            return;
        }
        modal.classList.add('is-closing');
        setTimeout(function () {
            modal.hidden = true;
            modal.classList.remove('is-closing');
            document.body.classList.remove('stamp-deco-open');
        }, 180);
    }

    function openStampPicker(slot) {
        if (!pickModal || !slot) {
            return;
        }
        // 色がまだ無いときは、色を作る画面を開く
        if (!pickModal.querySelector('.js-stamp-deco-choice[data-color-id]:not([data-color-id=""])')) {
            openModal(editModal);
            return;
        }
        pickingSlot = slot;
        const title = slot.querySelector('.stamp-slot-title');
        document.getElementById('stampDecoPickTitle').textContent = title ? title.textContent : '';
        pickModal.querySelectorAll('.js-stamp-deco-choice').forEach(function (choice) {
            choice.classList.toggle('is-current', choice.dataset.colorId === slot.dataset.colorId);
        });
        openModal(pickModal);
    }
    window.openStampPicker = openStampPicker;

    [editModal, pickModal].forEach(function (modal) {
        if (!modal) {
            return;
        }
        ['pointerdown', 'keydown'].forEach(function (type) {
            modal.addEventListener(type, function () {
                modal._freshPress = true;
            });
        });
        // 押し始めが開く前のクリックは、ここで止める（選択肢・閉じる・背景のどれにも届かない）
        modal.addEventListener('click', function (e) {
            if (!modal._freshPress) {
                e.stopImmediatePropagation();
                e.preventDefault();
            }
        }, true);
        modal.addEventListener('click', function (e) {
            if (e.target === modal || e.target.closest('.js-stamp-deco-close')) {
                closeModal(modal);
            }
        });
    });
    // ブラウザバックで戻ったとき、ページが開いたときの状態のまま出てくる（bfcache）ことがあるので、ページを離れるときと戻ったときに閉じておく
    function resetModals() {
        [editModal, pickModal].forEach(function (modal) {
            if (modal) {
                modal.hidden = true;
                modal.classList.remove('is-closing');
            }
        });
        document.body.classList.remove('stamp-deco-open');
    }
    window.addEventListener('pagehide', resetModals);
    window.addEventListener('pageshow', function (e) {
        if (e.persisted) {
            resetModals();
        }
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            [editModal, pickModal].forEach(function (modal) { if (modal && !modal.hidden) closeModal(modal); });
        }
    });

    const editButton = document.getElementById('stampDecoEditButton');
    if (editButton && editModal) {
        editButton.addEventListener('click', function () { openModal(editModal); });

        const rows = document.getElementById('stampDecoRows');
        document.getElementById('stampDecoAdd').addEventListener('click', function () {
            rows.appendChild(document.getElementById('stampDecoRowTemplate').content.cloneNode(true));
            rows.lastElementChild.querySelector('[data-field="label"]').focus();
        });
        rows.addEventListener('click', function (e) {
            const remove = e.target.closest('.js-stamp-deco-remove');
            if (remove) {
                remove.closest('.stamp-deco-item').remove();
                return;
            }
            // 色の丸をクリックしたらパレットを出す（もう一度で閉じる）
            const current = e.target.closest('.js-stamp-deco-current');
            if (current) {
                const palette = current.closest('.stamp-deco-item').querySelector('.stamp-deco-palette');
                rows.querySelectorAll('.stamp-deco-palette').forEach(function (other) {
                    if (other !== palette) other.hidden = true;
                });
                palette.hidden = !palette.hidden;
                return;
            }
            const swatch = e.target.closest('.js-stamp-deco-swatch');
            if (swatch) {
                const item = swatch.closest('.stamp-deco-item');
                item.querySelector('[data-field="color"]').value = swatch.dataset.color;
                item.querySelector('.stamp-deco-current').style.background = swatch.dataset.color;
                item.querySelectorAll('.js-stamp-deco-swatch').forEach(function (other) {
                    other.classList.toggle('is-selected', other === swatch);
                });
                swatch.closest('.stamp-deco-palette').hidden = true;
            }
        });
        // 送る直前に行の順に colors[i][...] の名前を付ける（並び順もこの順で保存）
        document.getElementById('stampDecoEditForm').addEventListener('submit', function () {
            rows.querySelectorAll('.stamp-deco-item').forEach(function (row, i) {
                row.querySelectorAll('[data-field]').forEach(function (input) {
                    input.name = 'colors[' + i + '][' + input.dataset.field + ']';
                });
            });
        });
    }

    if (pickModal) {
        pickModal.addEventListener('click', function (e) {
            const choice = e.target.closest('.js-stamp-deco-choice');
            if (!choice || !pickingSlot) {
                return;
            }
            const slot = pickingSlot;
            const colorId = choice.dataset.colorId;
            // 選択中の色付けを、前に選んでいたものから今選んだものへ移す（少し見せてから閉じる）
            const choices = Array.from(pickModal.querySelectorAll('.js-stamp-deco-choice'));
            const previous = choices.find(function (c) { return c.classList.contains('is-current'); });
            choices.forEach(function (c) { c.classList.toggle('is-current', c === choice); });
            const shownAt = Date.now();
            fetch(@json($decoMarkUrl ?? ''), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({ song_id: Number(slot.dataset.songId), color_id: colorId ? Number(colorId) : null }),
            }).then(function (res) {
                if (!res.ok) {
                    throw new Error('save failed');
                }
                slot.dataset.colorId = colorId;
                if (colorId) {
                    slot.style.setProperty('--stamp-deco', choice.dataset.color);
                } else {
                    slot.style.removeProperty('--stamp-deco');
                }
                setTimeout(function () { closeModal(pickModal); }, Math.max(0, 350 - (Date.now() - shownAt)));
                // 色で絞り込み中なら表示を合わせる
                const select = document.getElementById('stampFilterSelect');
                if (select && select.value.indexOf('color:') === 0) {
                    select.dispatchEvent(new Event('change'));
                }
            }).catch(function () {
                choices.forEach(function (c) { c.classList.toggle('is-current', c === previous); });
                alert('保存できませんでした');
            });
        });
    }

});
</script>
