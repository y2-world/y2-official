{{-- 絞り込み: All songs / Singles / アルバム / Incompleted（まだ聴いていない曲だけ）/ Unperformed（ライブで一度も演奏されていない曲だけ） --}}
<div class="stamp-filter">
    <select id="stampFilterSelect" class="stamp-filter-select" aria-label="Filter by discography">
        <option value="">All songs</option>
        @if (!empty($stampFilters) && count($stampFilters['single_song_ids']))
            <option value="single">Singles</option>
        @endif
        @if (!empty($stampFilters) && count($stampFilters['albums']))
            <optgroup label="Albums">
                @foreach ($stampFilters['albums'] as $album)
                    <option value="{{ $album['key'] }}">{{ $album['title'] }}</option>
                @endforeach
            </optgroup>
        @endif
        {{-- マイページのスタンプ帳：自分で付けた色（タイトル）で絞り込む --}}
        @if (!empty($stampColors) && count($stampColors))
            <optgroup label="Themes">
                @foreach ($stampColors as $color)
                    <option value="color:{{ $color->id }}" data-color="{{ $color->color }}">{{ $color->label }}</option>
                @endforeach
            </optgroup>
        @endif
        {{-- アルバムとの間に仕切り（見出し付きのグループ） --}}
        <optgroup label="Status">
            <option value="incompleted">Incompleted</option>
            @if ($hasUnperformed ?? true)
                <option value="unperformed">Unperformed</option>
            @endif
        </optgroup>
    </select>
</div>
