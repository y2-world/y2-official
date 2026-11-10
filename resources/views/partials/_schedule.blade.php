{{-- SCHEDULE を日付と会場の2列で出す（日付の長さがちがっても会場の左端がそろう）。
     保存されている文字（「3月14日 (土) 会場」、年の見出し「2026年」）を読むだけで、読めない行はそのまま1行で出す。
     $venueArtistId（アーティストの id）を渡すと、会場名を Database の会場のページへのリンクにする --}}
<table class="schedule-table">
    @foreach (preg_split('/\r\n|\r|\n/', (string) $schedule) as $line)
        @php $line = trim($line); @endphp
        @if ($line === '')
            <tr class="schedule-gap"><td colspan="2"></td></tr>
        @elseif (preg_match('/^\d{4}年$/u', $line))
            <tr><td colspan="2" class="schedule-year">{{ $line }}</td></tr>
        @elseif (preg_match('/^(\d{1,2}月\d{1,2}日\s*\([^)]*\))\s*(.*)$/u', $line, $m))
            @php
                // 会場名は Database の会場のページへのリンク（$venueArtistId があるとき）。うしろの「(公演中止)」などの注記はリンクの外
                // Solo の公演しか無い会場は、会場のページに何も出ないのでリンクにしない
                // リンクにするのは書いてある会場名（注記の前まで）。行き先は今の会場名のページ（前の名前・書き方の違いは VenueAlias でまとめる）
                $venueKeyword = isset($venueArtistId) ? (string) \App\Models\DbConcert::linkableVenue($venueArtistId, $m[2]) : '';
                $venueText = \App\Models\DbConcert::venueKeyword($m[2]);
            @endphp
            @if ($venueKeyword !== '' && $venueText !== '' && str_starts_with($m[2], $venueText))
                <tr><td class="schedule-date">{{ $m[1] }}</td><td><a href="{{ route('database.venues', [$venueArtistId, 'keyword' => $venueKeyword]) }}">{{ $venueText }}</a>{{ substr($m[2], strlen($venueText)) }}</td></tr>
            @else
                <tr><td class="schedule-date">{{ $m[1] }}</td><td>{{ $m[2] }}</td></tr>
            @endif
        @else
            <tr><td colspan="2">{{ $line }}</td></tr>
        @endif
    @endforeach
</table>
