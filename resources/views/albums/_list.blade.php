@foreach ($albums as $album)
    <tr>
        <td>{{$album->album_id}}</td>
        <td><a href="{{ route('albums.show', $album->id) }}">{{ $album->title }}</a></td>
        <td>{{ date('Y.m.d', strtotime($album->date)) }}</td>
    </tr>
@endforeach
