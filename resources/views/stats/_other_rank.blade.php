{{-- 順位の下に、もう一方の数え方（ツアー・ライブの数 ⇔ 公演の数）での順位をかっこで。
     今の数え方のほうが上なら ↑、下なら ↓、同じなら矢印なし。もう一方で演奏されていなければ（-）。
     表を描き直す JS（stats/database の otherRankHtml）と同じ形 --}}
@if (!empty($song['rank']))
    @if (empty($song['other_rank']))
        <div class="rank-other">(-)</div>
    @else
        <div class="rank-other">(@if ($song['other_rank'] > $song['rank'])<span class="rank-other-up">↑</span>@elseif ($song['other_rank'] < $song['rank'])<span class="rank-other-down">↓</span>@endif{{ $song['other_rank'] }})</div>
    @endif
@endif
