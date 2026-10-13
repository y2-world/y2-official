@extends('layouts.app')

@section('title', $artist->name . ' Stamp Book - My Statistics')
@section('og_title', $artist->name . ' Stamp Book - Yuki Official')

@section('content')
<div class="stats-wrapper">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-xl-10">
                <div class="element js-fadein">
                    <h1 class="stats-title">{{ $artist->name }}</h1>
                    <p class="stats-subtitle">
                        @if ($isOwner)
                            My Live Stamp Book
                        @else
                            {{ $externalUser->name ?: 'ゲスト' }}'s Live Stamp Book
                        @endif
                    </p>

                    <div class="stamp-summary"
                        data-total="{{ $totalCount }}"
                        data-total-percentage="{{ $percentage }}"
                        data-performed="{{ $performedCount }}"
                        data-performed-percentage="{{ $performedPercentage }}">
                        <div class="stamp-summary-count">
                            <span class="stamp-summary-done" id="stampSummaryDone">{{ $doneCount }}</span>
                            <span class="stamp-summary-slash">/</span>
                            <span class="stamp-summary-total" id="stampSummaryTotal">{{ $totalCount }}</span>
                            <span class="stamp-summary-unit">songs</span>
                        </div>
                        <div class="stamp-summary-bar">
                            <div class="stamp-summary-bar-fill" id="stampSummaryBarFill" style="width: {{ $percentage }}%;"></div>
                        </div>
                        <div class="stamp-summary-percentage" id="stampSummaryPercentage">{{ $percentage }}% complete</div>

                        @include('stats._stamp_filter')

                        <label class="unique-tour-label stamp-summary-toggle">
                            <input type="checkbox" id="stampPerformedOnlyCheckbox" class="unique-tour-checkbox">
                            <span class="unique-tour-text">演奏曲のみ</span>
                        </label>
                    </div>

                    @if ($totalCount === 0)
                        <p class="stamp-empty">No songs registered in the database yet.</p>
                    @else
                        <div class="stamp-book {{ $isOwner ? 'is-owner' : '' }}">
                            {{-- 色で絞り込んだときに、その色のタイトルを出す --}}
                            <p class="stamp-book-filter-title" id="stampBookFilterTitle" hidden></p>
                            @if ($isOwner)
                                {{-- 枠の色とタイトルの編集（全部のスタンプ帳で共通） --}}
                                <button type="button" class="stamp-deco-edit-button" id="stampDecoEditButton" title="スタンプの枠の色とタイトルを編集" aria-label="スタンプの枠の色とタイトルを編集">
                                    <i class="fa-solid fa-palette"></i>
                                </button>
                            @endif
                            @foreach ($stamps as $stamp)
                                <div class="stamp-slot {{ $stamp['done'] ? 'is-stamped' : '' }} {{ $stamp['never_performed'] ? 'is-never-performed' : '' }} {{ $stamp['fes_only'] ? 'is-fes-only' : '' }} {{ !empty($stamp['hikigatari_only']) ? 'is-hikigatari-only' : '' }}"
                                    data-filter-keys="{{ implode(' ', $stamp['filter_keys'] ?? []) }}"
                                    data-done="{{ $stamp['done'] ? 1 : 0 }}"
                                    data-never-performed="{{ $stamp['never_performed'] ? 1 : 0 }}"
                                    data-song-id="{{ $stamp['song_id'] }}"
                                    data-color-id="{{ $stamp['color_id'] ?? '' }}"
                                    @if (!empty($stamp['color_id']) && ($deco = $stampColors->firstWhere('id', $stamp['color_id']))) style="--stamp-deco: {{ $deco->color }};" @endif
                                    @if (!empty($stamp['track_titles'])) data-track-titles="{{ json_encode($stamp['track_titles'], JSON_UNESCAPED_UNICODE) }}" @endif
                                    @if (!empty($stamp['track_orders'])) data-track-orders="{{ json_encode($stamp['track_orders']) }}" @endif>
                                    <div class="stamp-slot-frame js-stamp-tap" @if ($stamp['never_performed']) title="ライブで演奏されたことがない曲です" @endif>
                                        {{-- 自分で選んだ色のタイトル（枠の飾り） --}}
                                        <span class="stamp-slot-deco-label">{{ !empty($stamp['color_id']) ? optional($stampColors->firstWhere('id', $stamp['color_id']))->label : '' }}</span>
                                        @if ($stamp['done'])
                                            <div class="stamp-mark">
                                                @if (!empty($stamp['hikigatari_only']))
                                                    {{-- 福山雅治のDOUBLE ENCORE（弾き語り）でしか聴いていない曲 --}}
                                                    <span class="stamp-mark-text" title="弾き語り（DOUBLE ENCORE）でのみ"><i class="fa-solid fa-guitar"></i></span>
                                                @else
                                                    <span class="stamp-mark-text">{{ $stamp['fes_only'] ? 'FES' : 'LIVE' }}</span>
                                                @endif
                                            </div>
                                        @endif
                                    </div>
                                    @if ($stamp['song_url'])
                                        <a href="{{ $stamp['song_url'] }}" class="stamp-slot-title">{{ $stamp['title'] }}</a>@if (!empty($stamp['credit']))<span class="stamp-slot-credit">{{ $stamp['credit'] }}</span>@endif
                                    @else
                                        <span class="stamp-slot-title">{{ $stamp['title'] }}</span>@if (!empty($stamp['credit']))<span class="stamp-slot-credit">{{ $stamp['credit'] }}</span>@endif
                                    @endif
                                </div>
                            @endforeach
                            {{-- 絞り込みで1曲も無いときのメッセージ（台紙の中に出す） --}}
                            <p class="stamp-empty stamp-empty-in-book" id="stampFilterNoMatch" hidden>No songs match this filter.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@if ($isOwner && $totalCount > 0)
    @php
        // 色はこのパレットから選ぶ
        $stampPalette = ['#e53935', '#fb8c00', '#fdd835', '#7cb342', '#43a047', '#00897b', '#00acc1', '#1e88e5', '#3949ab', '#8e24aa', '#d81b60', '#6d4c41', '#757575', '#212121'];
    @endphp
    {{-- 色とタイトルの編集。保存すると全部のスタンプ帳に反映。消した色のスタンプは色なしに戻る --}}
    <div class="stamp-deco-modal" id="stampDecoEditModal" hidden>
        <div class="stamp-deco-dialog" role="dialog" aria-modal="true" aria-labelledby="stampDecoEditTitle">
            <button type="button" class="stamp-deco-close js-stamp-deco-close" aria-label="閉じる"><i class="fa-solid fa-xmark"></i></button>
            <h2 class="stamp-deco-heading" id="stampDecoEditTitle">スタンプの枠の色とタイトル</h2>
            <p class="stamp-deco-note">スタンプを長押しすると枠の色を選べます。</p>
            <form method="POST" action="{{ route('mypage.stats.stamps.colors', $artistRef) }}" id="stampDecoEditForm">
                @csrf
                <div class="stamp-deco-rows" id="stampDecoRows">
                    @foreach ($stampColors as $color)
                        <div class="stamp-deco-item">
                            <div class="stamp-deco-row">
                                <input type="hidden" data-field="id" value="{{ $color->id }}">
                                <input type="hidden" data-field="color" value="{{ $color->color }}">
                                <button type="button" class="stamp-deco-current js-stamp-deco-current" style="background: {{ $color->color }};" aria-label="色を選ぶ"></button>
                                <input type="text" data-field="label" value="{{ $color->label }}" maxlength="50" class="stamp-deco-label-input" placeholder="タイトル" required>
                                <button type="button" class="stamp-deco-remove js-stamp-deco-remove" aria-label="この色を削除"><i class="fa-solid fa-trash-can"></i></button>
                            </div>
                            <div class="stamp-deco-palette" hidden>
                                @foreach ($stampPalette as $paletteColor)
                                    <button type="button" class="stamp-deco-palette-swatch js-stamp-deco-swatch {{ $paletteColor === $color->color ? 'is-selected' : '' }}" data-color="{{ $paletteColor }}" style="background: {{ $paletteColor }};" aria-label="{{ $paletteColor }}"></button>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
                <template id="stampDecoRowTemplate">
                    <div class="stamp-deco-item">
                            <div class="stamp-deco-row">
                                <input type="hidden" data-field="id" value="">
                                <input type="hidden" data-field="color" value="#e53935">
                                <button type="button" class="stamp-deco-current js-stamp-deco-current" style="background: #e53935;" aria-label="色を選ぶ"></button>
                                <input type="text" data-field="label" value="" maxlength="50" class="stamp-deco-label-input" placeholder="タイトル" required>
                                <button type="button" class="stamp-deco-remove js-stamp-deco-remove" aria-label="この色を削除"><i class="fa-solid fa-trash-can"></i></button>
                            </div>
                            <div class="stamp-deco-palette" hidden>
                                @foreach ($stampPalette as $paletteColor)
                                    <button type="button" class="stamp-deco-palette-swatch js-stamp-deco-swatch {{ $paletteColor === '#e53935' ? 'is-selected' : '' }}" data-color="{{ $paletteColor }}" style="background: {{ $paletteColor }};" aria-label="{{ $paletteColor }}"></button>
                                @endforeach
                            </div>
                        </div>
                </template>
                <button type="button" class="stamp-deco-add" id="stampDecoAdd"><i class="fa-solid fa-plus"></i> 色を追加</button>
                <div class="stamp-deco-actions">
                    <button type="submit" class="btn-pill">保存</button>
                </div>
            </form>
        </div>
    </div>

    {{-- スタンプを長押ししたときの枠の色選び --}}
    <div class="stamp-deco-modal" id="stampDecoPickModal" hidden>
        <div class="stamp-deco-dialog" role="dialog" aria-modal="true" aria-labelledby="stampDecoPickTitle">
            <button type="button" class="stamp-deco-close js-stamp-deco-close" aria-label="閉じる"><i class="fa-solid fa-xmark"></i></button>
            <h2 class="stamp-deco-heading" id="stampDecoPickTitle"></h2>
            <div class="stamp-deco-choices">
                @foreach ($stampColors as $color)
                    <button type="button" class="stamp-deco-choice js-stamp-deco-choice" data-color-id="{{ $color->id }}" data-color="{{ $color->color }}" data-label="{{ $color->label }}">
                        <span class="stamp-deco-swatch" style="background: {{ $color->color }};"></span>{{ $color->label }}
                    </button>
                @endforeach
                <button type="button" class="stamp-deco-choice js-stamp-deco-choice" data-color-id="" data-color="" data-label="">
                    <span class="stamp-deco-swatch stamp-deco-swatch--none"></span>なし
                </button>
            </div>
        </div>
    </div>
@endif
@endsection

@section('page-script')
<script src="{{ asset('/js/stamp-book.js?v=20261013d') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.js-stamp-tap').forEach(function (frame) {
        // 長押し（0.5秒）で枠の色を選ぶ（本人だけ）。指が動いたらスクロールとみなしてやめる
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

    // ---- 枠の色とタイトル（本人だけ） ----
    const editModal = document.getElementById('stampDecoEditModal');
    const pickModal = document.getElementById('stampDecoPickModal');
    let pickingSlot = null;

    function openModal(modal) { modal.hidden = false; document.body.classList.add('stamp-deco-open'); }
    function closeModal(modal) { modal.hidden = true; document.body.classList.remove('stamp-deco-open'); }

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
        modal.addEventListener('click', function (e) {
            if (e.target === modal || e.target.closest('.js-stamp-deco-close')) {
                closeModal(modal);
            }
        });
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
            fetch(@json(route('mypage.stats.stamps.mark', $artistRef ?? '')), {
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
                slot.querySelector('.stamp-slot-deco-label').textContent = choice.dataset.label;
                closeModal(pickModal);
                // 色で絞り込み中なら表示を合わせる
                const select = document.getElementById('stampFilterSelect');
                if (select && select.value.indexOf('color:') === 0) {
                    select.dispatchEvent(new Event('change'));
                }
            }).catch(function () {
                alert('保存できませんでした');
            });
        });
    }

});
</script>
@endsection
