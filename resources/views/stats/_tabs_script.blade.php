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
var initialStatsTab = new URL(window.location.href).searchParams.get('stats_tab');
if (initialStatsTab && document.querySelector('button.stats-tab-btn[data-stats-tab="' + initialStatsTab + '"]')) {
    showStatsTab(initialStatsTab);
}

</script>
