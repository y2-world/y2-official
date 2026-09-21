@foreach ($tours as $tour)
    <tr>
        {{-- 通常のAJAX追記（1ページ分のみ渡される場合）はcurrentPageからのオフセットが必要だが、
             ブラウザの戻る対応で1〜Nページ目をまとめて渡す場合はitems内の通し番号がそのまま正しい --}}
        <td>{{ ($accumulated ?? false) ? $loop->iteration : ($tours->currentPage() - 1) * $tours->perPage() + $loop->iteration }}</td>
        @if (isset($tour->date1) && isset($tour->date2))
            <td class="td_date">{{ date('Y.m.d', strtotime($tour->date1)) }} -
                {{ date('Y.m.d', strtotime($tour->date2)) }}</td>
        @elseif(isset($tour->date1) && !isset($tour->date2))
            <td class="td_date">{{ date('Y.m.d', strtotime($tour->date1)) }}</td>
        @endif
        <td class="td_title"><a href="{{ route('live.show', $tour->id) }}" data-turbo="true">{{ $tour->title }}</a>
        </td>
        <td class="pc_venue">{{ $tour->venue }}</td>
    </tr>
@endforeach
