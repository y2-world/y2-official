@extends('layouts.app')
@section('title', 'Yuki Official - 参加記録一覧')

@section('content')
    <div class="database-hero database-hero--nav">
        <div class="container">
            <div class="setlists-header-row">
                <div style="flex-shrink: 0;">
                    <h1 class="database-title" style="white-space: nowrap;">My Live Attendances</h1>
                    <p class="database-subtitle" style="margin: 4px 0 0;">参加したライブ一覧</p>
                </div>
                <div class="header-selects" style="display: flex; align-items: center; gap: 10px; flex-wrap: nowrap; overflow-x: auto; max-width: 100%;">
                    <select class="year-select" name="select" onchange="if (this.value) window.location.href=this.value; else window.location.href='{{ route('mypage.attendances.index') }}';">
                        <option value="" {{ $artistId ? '' : 'selected' }}>All Artists</option>
                        @foreach ($artists as $artist)
                            <option value="{{ route('mypage.attendances.index', ['artist_id' => $artist->id]) }}" {{ (string)$artistId === (string)$artist->id ? 'selected' : '' }}>{{ $artist->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </div>

    <div class="container-lg database-year-content">
        @if ($attendances->isEmpty())
            <p>まだ参加したライブが記録されていません。</p>
        @else
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th class="mobile">#</th>
                        <th class="mobile">参加日</th>
                        <th class="sp">アーティスト / タイトル</th>
                        <th class="pc td_artist">アーティスト</th>
                        <th class="pc">タイトル</th>
                        <th class="pc">会場</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $startNumber = $attendances->total() - ($attendances->currentPage() - 1) * $attendances->perPage();
                    @endphp
                    @foreach ($attendances as $index => $attendance)
                        <tr>
                            <td>{{ $startNumber - $index }}</td>
                            <td>{{ $attendance->attended_date?->format('Y.m.d') ?? '-' }}</td>
                            <td class="sp">
                                @if ($attendance->dbSetlist?->tour?->artist)
                                    <a href="{{ route('mypage.attendances.index', ['artist_id' => $attendance->dbSetlist->tour->artist_id]) }}">{{ $attendance->dbSetlist->tour->artist->name }}</a>
                                    /
                                @endif
                                <a href="{{ route('mypage.attendances.show', $attendance) }}">{{ $attendance->dbSetlist->tour->title ?? '-' }}</a>
                            </td>
                            <td class="pc td_artist">
                                @if ($attendance->dbSetlist?->tour?->artist)
                                    <a href="{{ route('mypage.attendances.index', ['artist_id' => $attendance->dbSetlist->tour->artist_id]) }}">{{ $attendance->dbSetlist->tour->artist->name }}</a>
                                @endif
                            </td>
                            <td class="pc">
                                <a href="{{ route('mypage.attendances.show', $attendance) }}">{{ $attendance->dbSetlist->tour->title ?? '-' }}</a>
                            </td>
                            <td class="pc">{{ $attendance->venue }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            {{ $attendances->links() }}
        @endif
    </div>
@endsection
