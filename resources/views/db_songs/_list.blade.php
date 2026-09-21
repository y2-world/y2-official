@foreach ($songs as $song)
    <tr>
        {{-- 通常のAJAX追記（1ページ分のみ渡される場合）はcurrentPageからのオフセットが必要だが、
             ブラウザの戻る対応で1〜Nページ目をまとめて渡す場合はitems内の通し番号がそのまま正しい --}}
        <td>{{ ($accumulated ?? false) ? $loop->iteration : ($songs->currentPage() - 1) * $songs->perPage() + $loop->iteration }}</td>
        <td><a href="{{ route('songs.show', $song->id) }}">{{ $song->title }}</a></td>
        @php
            $single = $song->singleFromTracklist;
            $album = $song->albumFromTracklist;
        @endphp
        @if ($single && $album)
            @if ($single->date > $album->date)
                <td><a href="{{ route('albums.show', $album->id) }}">{{ $song->albumDisplayTitleFromTracklist }}</a></td>
                <td class="pc">{{ date('Y.m.d', strtotime($album->date)) }}</td>
            @else
                <td><a href="{{ route('singles.show', $single->id) }}">{{ $single->title }}</a></td>
                <td class="pc">{{ date('Y.m.d', strtotime($single->date)) }}</td>
            @endif
        @elseif($album)
            <td><a href="{{ route('albums.show', $album->id) }}">{{ $song->albumDisplayTitleFromTracklist }}</a></td>
            <td class="pc">{{ date('Y.m.d', strtotime($album->date)) }}</td>
        @elseif($single)
            <td><a href="{{ route('singles.show', $single->id) }}">{{ $single->title }}</a></td>
            <td class="pc">{{ date('Y.m.d', strtotime($single->date)) }}</td>
        @else
            <td></td>
            <td class="pc"></td>
        @endif

    </tr>
@endforeach
