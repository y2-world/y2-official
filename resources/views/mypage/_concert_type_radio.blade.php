{{-- ツアーの種類（公式のツアーと同じ：0 ツアー / 1 単発ライブ / 2 イベント）。$selected に今の値、$idPrefix に id の頭 --}}
@php $selectedType = (int) ($selected ?? 0); @endphp
<div class="mb-3">
    <label class="form-label">種類</label>
    <div>
        @foreach ([0 => 'ツアー', 1 => '単発ライブ', 2 => 'イベント（フェス・複数アーティスト出演）'] as $value => $label)
            <div class="form-check form-check-inline">
                <input type="radio" class="form-check-input" id="{{ $idPrefix }}_type_{{ $value }}" name="tour_type" value="{{ $value }}" @if ($selectedType === $value) checked @endif>
                <label class="form-check-label" for="{{ $idPrefix }}_type_{{ $value }}">{{ $label }}</label>
            </div>
        @endforeach
    </div>
</div>
