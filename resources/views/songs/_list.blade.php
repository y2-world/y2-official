@foreach ($songs as $song)
    <tr>
        <td>{{ $song->id }}</td>
        <td><a href="{{ route('songs.show', $song->id) }}">{{ $song->title }}</a></td>
        @if (isset($song->single_id) && isset($song->album_id))
            @if ($song->single->date > $song->album->date)
                <td><a href="{{ route('albums.show', $song->album_id) }}">{{ $song->album->title }}</a>
                </td>
                <td class="pc">{{ date('Y.m.d', strtotime($song->album->date)) }}</td>
            @else
                <td><a
                        href="{{ route('singles.show', $song->single_id) }}">{{ $song->single->title }}</a>
                </td>
                <td class="pc">{{ date('Y.m.d', strtotime($song->single->date)) }}</td>
            @endif
        @elseif(isset($song->album_id))
            <td><a href="{{ route('albums.show', $song->album_id) }}">{{ $song->album->title }}</a>
            </td>
            <td class="pc">{{ date('Y.m.d', strtotime($song->album->date)) }}</td>
        @elseif(isset($song->single_id))
            <td><a href="{{ route('singles.show', $song->single_id) }}">{{ $song->single->title }}</a>
            </td>
            <td class="pc">{{ date('Y.m.d', strtotime($song->single->date)) }}</td>
        @else
            <td></td>
            <td class="pc"></td>
        @endif

    </tr>
@endforeach
