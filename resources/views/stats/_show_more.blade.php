{{-- statsの一覧の「Show More」。最初は10曲だけ出し、残り（data-more の行。データは25曲まで）をこのボタンで開け閉めする。
     必要な変数: $count（一覧の曲数。$limit 曲以下ならボタンを出さない）、$limit（任意。最初に出す曲数。渡さなければ10。参加記録のページの stats は3）
     ボタンのある .stats-section の中の [data-more] を開け閉めする。PCの表の行とスマホの表の1曲ぶん（tbody）の両方。
     一覧を切り替えるチェック（新曲を除く など）があるセクションでは、切り替えた一覧の中だけで開け閉めする（data-kind が隠れているものは出さない） --}}
@if ($count > ($limit ?? 10))
<div class="show-more-container">
    <button type="button" class="show-more-btn" onclick="toggleTopicMore(this)">
        Show More <i class="fas fa-chevron-down"></i>
    </button>
</div>
@once
<script>
function toggleTopicMore(button) {
    var section = button.closest('.stats-section');
    var expanded = !button.classList.contains('expanded');
    section.querySelectorAll('[data-more]').forEach(function (el) {
        // 切り替えで隠れている一覧（data-kind-hidden）の曲は出さない
        el.style.display = expanded && !el.closest('[data-kind-hidden]') && !el.hasAttribute('data-kind-hidden') ? '' : 'none';
    });
    button.classList.toggle('expanded', expanded);
    button.innerHTML = expanded ? 'Show Less <i class="fas fa-chevron-up"></i>' : 'Show More <i class="fas fa-chevron-down"></i>';
}
</script>
@endonce
@endif
