{{-- 曲ごとのツアー一覧（Live Performances）。Database・Yuki's Setlists・マイページ（参加記録・マイページのアーティスト）の曲のページで共通。
     渡すもの：$tours、$tourUrl（ツアー → リンク先）、$tourTitles（表記ごとの絞り込み用）、$partialPatterns（PartialPatterns::of の結果）、
     福山雅治の弾き語りの列：$isHikigatariArtist・$hikigatariTourIds・$normalTourIds（無ければ列を出さない） --}}
@php
    $partialPatterns = $partialPatterns ?? [];
    $isHikigatariArtist = $isHikigatariArtist ?? false;
    $hikigatariTourIds = $hikigatariTourIds ?? [];
    $normalTourIds = $normalTourIds ?? [];
@endphp
@if ($tours->isEmpty())
    <p style="color: #718096; text-align: center;">演奏記録がありません。</p>
@else
    <table class="table table-striped count fixed-cols">
        <thead>
            <tr>
                <th class="mobile col-no">#</th>
                <th class="mobile col-date-range">開催日</th>
                <th class="mobile">タイトル</th>
                @if ($isHikigatariArtist)<th class="mobile col-icon" title="弾き語り（DOUBLE ENCORE）"><i class="fa-solid fa-guitar"></i></th>@endif
            </tr>
        </thead>
        <tbody>
            @foreach ($tours as $tour)
                <tr data-titles="{{ json_encode($tourTitles[$tour->id] ?? [], JSON_UNESCAPED_UNICODE) }}" @if ($isHikigatariArtist && isset($hikigatariTourIds[$tour->id]) && !isset($normalTourIds[$tour->id])) data-double-encore-only="1" @endif>
                    <td></td>
                    @if ($tour->date1 && $tour->date2)
                        <td class="td_date">{{ date('Y.m.d', strtotime($tour->date1)) }} - {{ date('Y.m.d', strtotime($tour->date2)) }}</td>
                    @elseif ($tour->date1)
                        <td class="td_date">{{ date('Y.m.d', strtotime($tour->date1)) }}</td>
                    @else
                        <td class="td_date"></td>
                    @endif
                    <td class="td_title"><a href="{{ $tourUrl($tour) }}">{{ $tour->title }}</a>
                        {{-- ツアーの全パターンではやっていないときは、やったパターン（段を全部やっていれば段のタイトル） --}}
                        @if (!empty($partialPatterns[$tour->id]))
                            <br><small style="color: #888;">{{ implode(' / ', $partialPatterns[$tour->id]) }}</small>
                        @endif
                    </td>
                    @if ($isHikigatariArtist)@include('songs._hikigatari_cell', ['hikigatari' => isset($hikigatariTourIds[$tour->id]), 'normal' => isset($normalTourIds[$tour->id])])@endif
                </tr>
            @endforeach
        </tbody>
    </table>
@endif
