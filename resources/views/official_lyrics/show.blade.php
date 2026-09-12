@extends('layouts.app')

@section('og_title', $lyrics->title . ' - Yuki Official')
@section('og_description', 'Lyrics: ' . $lyrics->title)
@section('og_type', 'article')

@section('content')
<div class="news-detail-wrapper" style="padding-top: 40px;">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-xl-9">
                <div class="element js-fadein">
                    <div class="news-item">
                        <h4>{{ $lyrics->title }}</h4>
                        <hr>
                        <div class="news-text">
                            {!! nl2br(e($lyrics->lyrics)) !!}
                        </div>
                        <div style="text-align: center; padding: 30px 0;">
                            @php
                                $releaseId = $lyrics->album_id ?? $lyrics->single_id;
                            @endphp
                            @if ($releaseId)
                                <a href="{{ route('music.show', $releaseId) }}" style="color: #666; text-decoration: none; font-size: 14px;">← Back to Music</a>
                            @else
                                <a href="#" onclick="window.history.back(); return false;" style="color: #666; text-decoration: none; font-size: 14px;">← Back</a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('page-script')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const fadeTargets = document.querySelectorAll('.js-fadein');
    if (!('IntersectionObserver' in window)) {
        fadeTargets.forEach(el => el.classList.add('is-show'));
        return;
    }
    const io = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-show');
                io.unobserve(entry.target);
            }
        });
    }, { threshold: 0.2 });
    fadeTargets.forEach(el => io.observe(el));
});
</script>
@endsection
