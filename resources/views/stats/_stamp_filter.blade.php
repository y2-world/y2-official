@if (!empty($stampFilters) && (count($stampFilters['single_song_ids']) || count($stampFilters['albums'])))
    <div class="stamp-filter">
        <select id="stampFilterSelect" class="stamp-filter-select" aria-label="Filter by discography">
            <option value="">All songs</option>
            @if (count($stampFilters['single_song_ids']))
                <option value="single">Singles</option>
            @endif
            @if (count($stampFilters['albums']))
                <optgroup label="Albums">
                    @foreach ($stampFilters['albums'] as $album)
                        <option value="{{ $album['key'] }}">{{ $album['title'] }}</option>
                    @endforeach
                </optgroup>
            @endif
        </select>
    </div>
@endif
