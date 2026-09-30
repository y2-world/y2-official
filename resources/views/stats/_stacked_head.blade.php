{{-- スマホの2段の見出し（1段目：SONG、2段目：TOUR TITLE）。$hasRank・$badgeLabel・$showArtist（曲名の下にアーティストを出すか） --}}
<thead>
    <tr>
        @if ($hasRank)<th class="rank-col" rowspan="2">Rank</th>@endif
        <th>{{ $showArtist ? 'Song / Artist' : 'Song' }}</th>
        @if ($badgeLabel)<th class="count-col" rowspan="2">{{ $badgeLabel }}</th>@endif
    </tr>
    <tr>
        <th>Tour Title</th>
    </tr>
</thead>
