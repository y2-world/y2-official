@foreach ($tours as $tour)
    <tr>
        <td>{{ ($tours->currentPage() - 1) * $tours->perPage() + $loop->iteration }}</td>
        @if (isset($tour->date1) && isset($tour->date2))
            <td class="td_date">{{ date('Y.m.d', strtotime($tour->date1)) }} -
                {{ date('Y.m.d', strtotime($tour->date2)) }}</td>
        @elseif(isset($tour->date1) && !isset($tour->date2))
            <td class="td_date">{{ date('Y.m.d', strtotime($tour->date1)) }}</td>
        @endif
        <td class="td_title"><a href="{{ route('live.show', $tour->id) }}">{{ $tour->title }}</a>
        </td>
        <td><a href="{{ url('/venue?keyword='.urlencode($tour->venue)) }}">{{ $tour->venue }}</a></td>
    </tr>
@endforeach
