{{-- SCHEDULE を日付と会場の2列で出す（日付の長さがちがっても会場の左端がそろう）。
     保存されている文字（「3月14日 (土) 会場」、年の見出し「2026年」）を読むだけで、読めない行はそのまま1行で出す --}}
<table class="schedule-table">
    @foreach (preg_split('/\r\n|\r|\n/', (string) $schedule) as $line)
        @php $line = trim($line); @endphp
        @if ($line === '')
            <tr class="schedule-gap"><td colspan="2"></td></tr>
        @elseif (preg_match('/^\d{4}年$/u', $line))
            <tr><td colspan="2" class="schedule-year">{{ $line }}</td></tr>
        @elseif (preg_match('/^(\d{1,2}月\d{1,2}日\s*\([^)]*\))\s*(.*)$/u', $line, $m))
            <tr><td class="schedule-date">{{ $m[1] }}</td><td>{{ $m[2] }}</td></tr>
        @else
            <tr><td colspan="2">{{ $line }}</td></tr>
        @endif
    @endforeach
</table>
