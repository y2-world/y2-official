@extends('layouts.app')

@section('title', $artist->name . ' Stamp Book - My Statistics')
@section('og_title', $artist->name . ' Stamp Book - Yuki Official')

@section('content')
<div class="stats-wrapper">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-xl-10">
                <div class="element js-fadein">
                    <div class="breadcrumb-nav">
                        <a href="{{ route('mypage.index') }}">← Back to My Page</a>
                    </div>

                    <h1 class="stats-title">{{ $artist->name }}</h1>
                    <p class="stats-subtitle">My Live Stamp Book</p>

                    <div class="stamp-summary"
                        data-total="{{ $totalCount }}"
                        data-total-percentage="{{ $percentage }}"
                        data-performed="{{ $performedCount }}"
                        data-performed-percentage="{{ $performedPercentage }}">
                        <div class="stamp-summary-count">
                            <span class="stamp-summary-done">{{ $doneCount }}</span>
                            <span class="stamp-summary-slash">/</span>
                            <span class="stamp-summary-total" id="stampSummaryTotal">{{ $totalCount }}</span>
                            <span class="stamp-summary-unit">songs</span>
                        </div>
                        <div class="stamp-summary-bar">
                            <div class="stamp-summary-bar-fill" id="stampSummaryBarFill" style="width: {{ $percentage }}%;"></div>
                        </div>
                        <div class="stamp-summary-percentage" id="stampSummaryPercentage">{{ $percentage }}% complete</div>

                        <label class="unique-tour-label stamp-summary-toggle">
                            <input type="checkbox" id="stampPerformedOnlyCheckbox" class="unique-tour-checkbox">
                            <span class="unique-tour-text">Count only songs ever performed live</span>
                        </label>
                    </div>

                    @if ($totalCount === 0)
                        <p class="stamp-empty">No songs registered in the database yet.</p>
                    @else
                        <div class="stamp-book">
                            @foreach ($stamps as $stamp)
                                <div class="stamp-slot {{ $stamp['done'] ? 'is-stamped' : '' }} {{ $stamp['never_performed'] ? 'is-never-performed' : '' }} {{ $stamp['fes_only'] ? 'is-fes-only' : '' }}">
                                    <div class="stamp-slot-frame js-stamp-tap" @if ($stamp['never_performed']) title="ライブで演奏されたことがない曲です" @endif>
                                        @if ($stamp['done'])
                                            <div class="stamp-mark">
                                                <span class="stamp-mark-text">{{ $stamp['fes_only'] ? 'FES' : 'LIVE' }}</span>
                                            </div>
                                        @endif
                                    </div>
                                    <a href="{{ route('songs.show', $stamp['song_id']) }}" class="stamp-slot-title">{{ $stamp['title'] }}</a>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('page-script')
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.js-stamp-tap').forEach(function (frame) {
        frame.addEventListener('click', function () {
            frame.classList.remove('is-tapped');
            // リフローを挟んで再度クラスを付与し、同じアニメーションを連打でも毎回再生させる
            void frame.offsetWidth;
            frame.classList.add('is-tapped');
        });
        frame.addEventListener('animationend', function () {
            frame.classList.remove('is-tapped');
        });
    });

    // 済みスタンプは、スクロールして画面に入ってきた瞬間にパコンと押す
    const stampObserver = new IntersectionObserver(function (entries, observer) {
        entries.forEach(function (entry) {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-stamp-in');
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.4 });

    document.querySelectorAll('.stamp-slot.is-stamped .stamp-mark').forEach(function (mark) {
        stampObserver.observe(mark);
    });

    const summary = document.querySelector('.stamp-summary');
    const checkbox = document.getElementById('stampPerformedOnlyCheckbox');
    const totalEl = document.getElementById('stampSummaryTotal');
    const barFillEl = document.getElementById('stampSummaryBarFill');
    const percentageEl = document.getElementById('stampSummaryPercentage');

    if (summary && checkbox) {
        checkbox.addEventListener('change', function () {
            const usePerformedOnly = checkbox.checked;
            totalEl.textContent = usePerformedOnly ? summary.dataset.performed : summary.dataset.total;
            const percentage = usePerformedOnly ? summary.dataset.performedPercentage : summary.dataset.totalPercentage;
            barFillEl.style.width = percentage + '%';
            percentageEl.textContent = percentage + '% complete';
        });
    }
});
</script>
@endsection
