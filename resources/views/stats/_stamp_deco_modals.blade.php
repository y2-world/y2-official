{{-- スタンプの枠の色とタイトル（編集できる人だけ）。$canEditDeco・$stampColors・$decoColorsUrl・$decoMarkUrl を渡す --}}
@if ($canEditDeco && $totalCount > 0)
    @php
        // 色はこのパレットから選ぶ
        $stampPalette = ['#e53935', '#fb8c00', '#fdd835', '#7cb342', '#43a047', '#00897b', '#00acc1', '#1e88e5', '#3949ab', '#8e24aa', '#d81b60', '#6d4c41', '#757575', '#212121', '#d4af37', '#a8adb4', '#b0703c'];
    @endphp
    {{-- 色とタイトルの編集。保存すると全部のスタンプ帳に反映。消した色のスタンプは色なしに戻る --}}
    <div class="stamp-deco-modal" id="stampDecoEditModal" hidden>
        <div class="stamp-deco-dialog" role="dialog" aria-modal="true" aria-labelledby="stampDecoEditTitle">
            <button type="button" class="stamp-deco-close js-stamp-deco-close" aria-label="閉じる"><i class="fa-solid fa-xmark"></i></button>
            <h2 class="stamp-deco-heading" id="stampDecoEditTitle">テーマのタイトルとスタンプの枠の色</h2>
            <p class="stamp-deco-note">スタンプを長押しすると枠の色を選べます。</p>
            <form method="POST" action="{{ $decoColorsUrl }}" id="stampDecoEditForm">
                @csrf
                <div class="stamp-deco-rows" id="stampDecoRows">
                    @foreach ($stampColors as $color)
                        <div class="stamp-deco-item">
                            <div class="stamp-deco-row">
                                <input type="hidden" data-field="id" value="{{ $color->id }}">
                                <input type="hidden" data-field="color" value="{{ $color->color }}">
                                <button type="button" class="stamp-deco-current js-stamp-deco-current" data-color="{{ $color->color }}" style="background: {{ $color->color }};" aria-label="色を選ぶ"></button>
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
                                <button type="button" class="stamp-deco-current js-stamp-deco-current" data-color="#e53935" style="background: #e53935;" aria-label="色を選ぶ"></button>
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
                <button type="button" class="stamp-deco-add" id="stampDecoAdd"><i class="fa-solid fa-plus"></i> テーマを追加</button>
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
                        <span class="stamp-deco-swatch" data-color="{{ $color->color }}" style="background: {{ $color->color }};"></span>{{ $color->label }}
                    </button>
                @endforeach
                <button type="button" class="stamp-deco-choice js-stamp-deco-choice" data-color-id="" data-color="" data-label="">
                    <span class="stamp-deco-swatch stamp-deco-swatch--none"></span>なし
                </button>
            </div>
        </div>
    </div>
@endif
