{{-- 曲の情報（アーティスト表記・シングル・アルバムと発売日・説明）。Database の曲のページと Yuki's Setlists の曲のページで使う。$dbSong を渡す --}}
{{-- ほかの人との曲は、曲名の下にアーティスト表記（例：絢香 × コブクロ） --}}
@if ($dbSong->credit)
    <p class="database-subtitle" style="margin-top: 0;">{{ $dbSong->credit }}</p>
@endif

@php
    $single = $dbSong->singleFromTracklist;
    $album = $dbSong->albumFromTracklist;
@endphp
@if ($single)
    <p class="database-subtitle" style="font-weight: 400;"><strong>Single:</strong> <a href="{{ route('singles.show', $single->id) }}" style="color: white; text-decoration: underline;">{{ $single->title }}</a></p>
    @if ($single->date)
        <p class="database-subtitle">Release: {{ date('Y.m.d', strtotime($single->date)) }}</p>
    @endif
@endif
@if ($album)
    <p class="database-subtitle" style="font-weight: 400;"><strong>Album:</strong> <a href="{{ route('albums.show', $album->id) }}" style="color: white; text-decoration: underline;">{{ $dbSong->albumDisplayTitleFromTracklist }}</a></p>
    @if ($album->date)
        <p class="database-subtitle">Release: {{ date('Y.m.d', strtotime($album->date)) }}</p>
    @endif
@elseif ($single)
    <p class="database-subtitle">アルバム未収録</p>
@else
    {{-- シングルにもアルバムにも入っていない曲 --}}
    <p class="database-subtitle">未音源化楽曲</p>
@endif
@if ($dbSong->text)
    <p class="database-subtitle" style="margin-top: 8px;">{{ $dbSong->text }}</p>
@endif
