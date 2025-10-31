@foreach ($singles as $single)
    <tr>
        <td>{{$single->single_id}}</td>
        <td><a href="{{ route('singles.show', $single->id) }}">{{ $single->title }}</a></td>
        <td>{{ date('Y.m.d', strtotime($single->date)) }}</td>
    </tr>
@endforeach
