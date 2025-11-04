@php
    // 現在ページでの開始番号を計算（逆順用）
    $startNumber = $totalCount - ($setlists->currentPage() - 1) * $setlists->perPage();
@endphp
@foreach ($setlists as $index => $setlist)
    <tr>
        <td>{{ $startNumber - $index }}</td>
        <td>{{ date('Y.m.d', strtotime($setlist->date)) }}</td>
        @if (request('type') != 2)
            @if (isset($setlist->artist_id) && $setlist->artist)
                <td class="sp">
                    <a href="{{ url('/setlists/artists', $setlist->artist_id) }}">{{ $setlist->artist->name }}</a>
                    /
                    <a href="{{ route('setlists.show', $setlist->id) }}">{{ $setlist->title }}</a>
                </td>
                <td class="pc">
                    <a href="{{ url('/setlists/artists', $setlist->artist_id) }}">{{ $setlist->artist->name }}</a>
                </td>
            @else
                <td class="sp">
                    <a href="{{ route('setlists.show', $setlist->id) }}">{{ $setlist->title }}</a>
                </td>
                <td class="pc"></td>
            @endif
        @endif
        @if (request('type') == 2)
            <td class="pc"></td>
            <td class="sp">
                <a href="{{ route('setlists.show', $setlist->id) }}">{{ $setlist->title }}</a>
            </td>
            <td class="pc">
                <a href="{{ route('setlists.show', $setlist->id) }}">{{ $setlist->title }}</a>
            </td>
        @else
            <td class="pc">
                <a href="{{ route('setlists.show', $setlist->id) }}">{{ $setlist->title }}</a>
            </td>
        @endif
        <td class="pc">
            <a href="{{ url('/venue?keyword=' . urlencode($setlist->venue)) }}">{{ $setlist->venue }}</a>
        </td>
    </tr>
@endforeach
