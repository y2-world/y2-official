@foreach ($singles as $single)
    <tr>
        <td>
            @if ($single->single_id)
                {{ $single->single_id }}
            @elseif ($single->ep)
                EP
            @elseif ($single->download)
                配信
            @endif
        </td>
        <td><a href="{{ route('singles.show', $single->id) }}">{{ $single->title }}</a></td>
        <td>{{ date('Y.m.d', strtotime($single->date)) }}</td>
    </tr>
@endforeach
