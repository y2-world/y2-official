{{-- 「同ツアーを除く」で切り替える曲の一覧で、順位の下にもう一方の数え方での順位を出す（Database の stats の stats/_other_rank と同じ形）。
     <script> の中で @include する。一覧の曲は song_id で見分ける --}}
@once
// 一覧の順位（{song_id: 順位}）。同じ回数は同じ順位（1, 1, 3…）
function statsRankMap(list) {
    const ranks = {};
    let prev = null, rank = 0;
    list.forEach((song, i) => {
        if (song.count !== prev) { rank = i + 1; prev = song.count; }
        ranks[String(song.song_id)] = rank;
    });
    return ranks;
}
// 今の順位の下に、もう一方での順位をかっこで。こちらが上なら ↑、下なら ↓、同じなら矢印なし、もう一方に無ければ（-）
function statsOtherRankHtml(rank, otherRank) {
    if (!otherRank) return '<div class="rank-other">(-)</div>';
    const arrow = otherRank > rank ? '<span class="rank-other-up">↑</span>' : otherRank < rank ? '<span class="rank-other-down">↓</span>' : '';
    return '<div class="rank-other">(' + arrow + otherRank + ')</div>';
}
// 表（tbody の行が data の並びと同じ）の順位の欄に、otherData での順位を足す
function statsAddOtherRanks(tbody, data, otherData) {
    if (!tbody) return;
    const ranks = statsRankMap(data);
    const otherRanks = statsRankMap(otherData);
    const rows = tbody.querySelectorAll(':scope > tr');
    data.forEach((song, i) => {
        const cell = rows[i]?.querySelector('.rank-col');
        if (!cell) return;
        cell.querySelector('.rank-other')?.remove();
        cell.insertAdjacentHTML('beforeend', statsOtherRankHtml(ranks[String(song.song_id)], otherRanks[String(song.song_id)]));
    });
}
@endonce
