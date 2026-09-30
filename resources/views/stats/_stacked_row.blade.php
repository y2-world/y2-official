{{-- スマホのstatsの表で、1曲を2〜3段に分けて出す行。1段目：曲名（下にアーティスト名）／2段目以降：ツアー名（前後2つのツアーがあるものは3段）。
     必要な変数: $rank（順位のHTML。順位の列が無い表では null）、$song（曲名のHTML）、$artist（曲名の下に出すアーティストのHTML。出さないときは null）、
     $lines（[['tour' => ツアー名のHTML], ...]）、$badge（右端のバッジの文字。無いときは null）、$rightText（任意。バッジの代わりに右端に小さく出す文字）、$kind（任意。表の中で切り替える一覧の種類）、$hidden（任意。最初は隠しておく） --}}
@php $span = count($lines) + 1; @endphp
<tbody class="stacked-item" @isset($kind) data-kind="{{ $kind }}" @endisset @if (!empty($hidden)) style="display: none;" @endif>
    <tr>
        @if (!is_null($rank))<td class="rank-col" rowspan="{{ $span }}">{!! $rank !!}</td>@endif
        <td class="song-title">{!! $song !!}@if (!empty($artist))<span class="stats-sub">{!! $artist !!}</span>@endif</td>
        @if (!is_null($badge))<td class="count-col" rowspan="{{ $span }}"><span class="count-badge">{{ $badge }}</span></td>
        @elseif (!empty($rightText))<td class="stacked-right" rowspan="{{ $span }}">{{ $rightText }}</td>@endif
    </tr>
    @foreach ($lines as $line)
    <tr class="stacked-sub">
        <td class="topic-tour">{!! $line['tour'] !!}</td>
    </tr>
    @endforeach
</tbody>
