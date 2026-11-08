@foreach ($tours as $tour)
    <tr>
        <td>{{ ($tours->currentPage() - 1) * $tours->perPage() + $loop->iteration }}</td>
        @if (isset($tour->date1) && isset($tour->date2))
            <td class="td_date">{{ date('Y.m.d', strtotime($tour->date1)) }} -
                {{ date('Y.m.d', strtotime($tour->date2)) }}</td>
        @elseif(isset($tour->date1) && !isset($tour->date2))
            <td class="td_date">{{ date('Y.m.d', strtotime($tour->date1)) }}</td>
        @endif
        <td class="td_title"><a href="{{ route('live.show', $tour->id) }}{{ !empty($type) ? '?from=type-' . $type : '' }}">{{ $tour->title }}</a>
        </td>
        @if (($type ?? null) !== '6')
        <td class="pc_venue">
            @if ($venueKeyword = \App\Models\DbConcert::linkableVenue($tour->artist_id, $tour->venue))
                <a href="{{ route('database.venues', [$tour->artist_id, 'keyword' => $venueKeyword]) }}">{{ $tour->venue }}</a>
            @else
                {{ $tour->venue }}
            @endif
        </td>
        @endif
    </tr>
@endforeach
