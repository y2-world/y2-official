{{-- statsのタブ（.stats-tab-btn / .stats-tab-panel）の切り替え。開いているタブは ?stats_tab= でURLに残す（最初のタブは付けない） --}}
<script>
document.querySelectorAll('button.stats-tab-btn').forEach(function (btn) {
    btn.addEventListener('click', function () { showStatsTab(btn.dataset.statsTab); });
});
function showStatsTab(name) {
    document.querySelectorAll('button.stats-tab-btn').forEach(function (b) { b.classList.toggle('is-active', b.dataset.statsTab === name); });
    document.querySelectorAll('.stats-tab-panel').forEach(function (p) { p.style.display = p.dataset.statsPanel === name ? '' : 'none'; });
    var url = new URL(window.location.href);
    if (name === document.querySelector('button.stats-tab-btn').dataset.statsTab) { url.searchParams.delete('stats_tab'); } else { url.searchParams.set('stats_tab', name); }
    history.replaceState(null, '', url);
}
// タブのアーティストの選択：開いているタブ（?stats_tab=）と、Database の stats の公演数統計（?count=shows）のまま、ほかのアーティストへ。
// 行き先にそのタブが無ければ、行き先のページで最初のタブになる（下の initialStatsTab）
function statsGoToArtist(select) {
    if (!select.value) return;
    var current = new URL(window.location.href);
    var next = new URL(select.value, window.location.origin);
    ['stats_tab', 'count'].forEach(function (key) {
        var value = current.searchParams.get(key);
        if (value) { next.searchParams.set(key, value); } else { next.searchParams.delete(key); }
    });
    window.location.href = next.toString();
}
var initialStatsTab = new URL(window.location.href).searchParams.get('stats_tab');
// My Statistics の「Setlists」タブは「Data」に名前を変えたので、古いリンク（?stats_tab=setlists）も Data で開く
if (initialStatsTab === 'setlists') { initialStatsTab = 'data'; }
if (initialStatsTab && document.querySelector('button.stats-tab-btn[data-stats-tab="' + initialStatsTab + '"]')) {
    showStatsTab(initialStatsTab);
}

</script>
