{{-- マイページで作ったアーティストの Songs / Singles / Albums の見出し。公式の Database の一覧と同じく、
     左に見出し、右に Discography（Songs / Singles / Albums）と Live を切り替えるセレクト。$current は songs / singles / albums --}}
<div class="setlists-header-row">
    <div style="flex-shrink: 0;">
        <h1 class="database-title" style="white-space: nowrap;">{{ $title }}</h1>
        <p class="database-subtitle" style="margin: 4px 0 0;">{{ $artist->name }} — {{ $subtitle }}</p>
    </div>
    <div class="header-selects" style="display: flex; align-items: center; gap: 10px; flex-wrap: nowrap; overflow-x: auto; max-width: 100%;">
        <select class="year-select" name="select" onChange="location.href=value;">
            <option value="" disabled>Discography</option>
            <option value="{{ route('mypage.user_artists.songs', $artist->id) }}" {{ $current === 'songs' ? 'selected' : '' }}>Songs</option>
            <option value="{{ route('mypage.user_artists.singles', $artist->id) }}" {{ $current === 'singles' ? 'selected' : '' }}>Singles</option>
            <option value="{{ route('mypage.user_artists.albums', $artist->id) }}" {{ $current === 'albums' ? 'selected' : '' }}>Albums</option>
        </select>
        <select class="year-select" name="select" onChange="location.href=value;">
            <option value="" disabled selected>Live</option>
            <option value="{{ route('mypage.user_artists.live', $artist->id) }}">All</option>
            <option value="{{ route('mypage.user_artists.stats', $artist->id) }}">Stats</option>
        </select>
    </div>
</div>
