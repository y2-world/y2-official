@foreach ($albums as $album)
    <tr>
        <td>
            @if ($album->best)
                Best
            @elseif ($album->mini)
                Mini
            @elseif ($album->album_id)
                # {{ $album->album_id }}
            @endif
        </td>
        <td><a href="{{ route('albums.show', $album->id) }}">{{ $album->title }}</a></td>
        <td>{{ date('Y.m.d', strtotime($album->date)) }}</td>
    </tr>
@endforeach
