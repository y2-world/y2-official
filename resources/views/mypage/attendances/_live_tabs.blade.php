{{-- 参加した公演のページのタブ。マイページの参加記録のページ（Setlists / Analyze / Timeline / Comments）と、Yuki's Setlists のセットリストのページ（Setlists / Analyze / Timeline）で使う。
     $liveTabs：[キー => 見出し]（最初のタブを開いた状態で出す）。$liveCommentCount（任意）：Comments のタブに出す件数（「Comments (3)」。コメントを書く・消すと増減する）。
     $liveTabsMarginTop（任意）：タブの上の余白。ヒーロー（紫の帯）からタブまでが、どのページもタブの下と同じ 27px になるように渡す（Database のライブの Setlists のタブの下の空き。参加記録・Yuki's Setlists のページは中身の上に 15px あるので 12px）。
     タブの下は 27px。中身の最初の要素にもともと上の余白がある画面は、その分を中身の側で差し引いて、見えるものがタブの 27px 下に来るようにする。
     $liveMobileDefault（任意）：スマホで最初に開くタブ（URL で ?tab= を決めていないとき。Database のライブのページの Summary）。
     中身は data-live-tab-panel="キー" の要素（最初のタブ以外は hidden）。形は曲のページのタブ（Live Performances / My Live Attendances）と同じで、
     文字は中の曲名（stats の表）に合わせて小さめ（0.8rem） --}}
<div class="song-performance-tabs live-tabs" @if (!empty($liveMobileDefault)) data-mobile-default="{{ $liveMobileDefault }}" @endif style="display: flex; justify-content: center; flex-wrap: wrap; gap: 8px; margin-top: {{ $liveTabsMarginTop ?? '0' }}; margin-bottom: 27px;">
    @foreach ($liveTabs as $key => $label)
        <button type="button" class="live-tab-btn" data-live-tab="{{ $key }}"
            style="padding: 6px 16px; border-radius: 20px; font-size: 0.8rem; font-weight: 500; cursor: pointer; {{ $loop->first ? 'border: none; background: #667eea; color: white;' : 'border: 1px solid #667eea; background: white; color: #667eea;' }}">
            {{ $label }}@if ($key === 'comment' && isset($liveCommentCount)) (<span class="live-tab-count">{{ $liveCommentCount }}</span>)@endif
        </button>
    @endforeach
</div>
@once
<style>
/* 開いていないタブの中身は隠す（.timeline-card などの display の指定より強く） */
[data-live-tab-panel][hidden] { display: none !important; }
/* タブのすぐ下の枠は、上の余白を詰める（タブと見出しの間が空きすぎないよう） */
[data-live-tab-panel] .live-stats > div.stats-section:first-of-type { padding-top: 16px; }
</style>
<script>
(function () {
    function openLiveTab(btn) {
        document.querySelectorAll('.live-tab-btn').forEach(function (other) {
            var active = other === btn;
            other.style.border = active ? 'none' : '1px solid #667eea';
            other.style.background = active ? '#667eea' : 'white';
            other.style.color = active ? 'white' : '#667eea';
        });
        document.querySelectorAll('[data-live-tab-panel]').forEach(function (panel) {
            panel.hidden = panel.getAttribute('data-live-tab-panel') !== btn.getAttribute('data-live-tab');
        });
        // 隠れていた中身は高さが測れていないので、開いたあとに測り直す処理（見出しの折りたたみなど）に知らせる
        document.dispatchEvent(new CustomEvent('live-tab-opened', { detail: btn.getAttribute('data-live-tab') }));
        // Previous / Next で移っても同じタブで開くよう、前後のリンクにも開いているタブを付ける（最初のタブなら外す）
        var isFirst = btn === document.querySelector('.live-tab-btn');
        document.querySelectorAll('a[rel="prev"], a[rel="next"]').forEach(function (link) {
            var href = new URL(link.href, window.location.href);
            if (isFirst) {
                href.searchParams.delete('tab');
            } else {
                href.searchParams.set('tab', btn.getAttribute('data-live-tab'));
            }
            link.href = href.toString();
        });
    }
    // 開いたタブは URL（?tab=analyze など）に残す。更新しても同じタブで開く（最初のタブのときは消す）
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.live-tab-btn');
        if (!btn) return;
        openLiveTab(btn);
        var url = new URL(window.location.href);
        if (btn === document.querySelector('.live-tab-btn')) {
            url.searchParams.delete('tab');
        } else {
            url.searchParams.set('tab', btn.getAttribute('data-live-tab'));
        }
        history.replaceState(history.state, '', url);
    });
    // タブの中身はこの script より後ろにあるので、ページを読み終えてから開く
    document.addEventListener('DOMContentLoaded', function () {
        var wanted = new URLSearchParams(window.location.search).get('tab');
        // URL でタブを決めていなければ、スマホでは決めてあるタブ（Summary）を最初に開く
        var tabs = document.querySelector('.live-tabs[data-mobile-default]');
        if (!wanted && tabs && window.matchMedia('(max-width: 767px)').matches) {
            wanted = tabs.getAttribute('data-mobile-default');
        }
        var wantedBtn = wanted && document.querySelector('.live-tab-btn[data-live-tab="' + wanted.replace(/[^a-z]/g, '') + '"]');
        if (wantedBtn) {
            openLiveTab(wantedBtn);
        }
    });
})();
</script>
@endonce
