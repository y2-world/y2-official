@foreach ($tours as $tour)
    <tr>
        @if (request('type') == 1)
            <td>{{ $tour->tour_id }}</td>
        @elseif(request('type') == 2)
            <td>{{ $tour->event_id }}</td>
        @elseif(request('type') == 3)
            <td>{{ $tour->ap_id }}</td>
        @elseif(request('type') == 4)
            <td>{{ $tour->solo_id }}</td>
        @else
            <td>{{ $tour->id }}</td>
        @endif
        @if (isset($tour->date1) && isset($tour->date2))
            <td class="td_date">{{ date('Y.m.d', strtotime($tour->date1)) }} -
                {{ date('Y.m.d', strtotime($tour->date2)) }}</td>
        @elseif(isset($tour->date1) && !isset($tour->date2))
            <td class="td_date">{{ date('Y.m.d', strtotime($tour->date1)) }}</td>
        @endif
        <td class="td_title"><a href="{{ route('live.show', $tour->id) }}">{{ $tour->title }}</a>
        </td>
        <td class="pc_venue"><a href="{{ url('/venue?keyword='.urlencode($tour->venue)) }}">{{ $tour->venue }}</a></td>
    </tr>
@endforeach
