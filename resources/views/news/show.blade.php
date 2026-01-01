@extends('layouts.app')

@section('og_title', $news->title . ' - Yuki Official')
@section('og_description', strip_tags(Str::limit($news->text, 150)))
@section('og_type', 'article')
@if($news->image)
@section('og_image', 'https://res.cloudinary.com/hqrgbxuiv/' . $news->image)
@endif

@section('content')
<div class="news-detail-wrapper" style="padding-top: 100px;">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-xl-9">
                <div class="element js-fadein">
                    <div class="news-detail">
                        <div class="news-detail__header">
                            <h1 class="news-detail__title">{{ $news->title }}</h1>
                            <p class="news-detail__date">
                                {{ $news->published_at ? $news->published_at->format('Y.m.d') : ($news->date ? date('Y.m.d', strtotime($news->date)) : '') }}
                            </p>
                        </div>
                        <hr>
                        @if($news->image)
                        <div class="news-detail__image">
                            <img src="https://res.cloudinary.com/hqrgbxuiv/{{ $news->image }}" alt="{{ $news->title }}" class="img-fluid">
                        </div>
                        @endif
                        <div class="news-detail__content">
                            {!! $news->text !!}
                        </div>
                        <hr>
                        <div class="news-detail__footer">
                            <a href="{{ url('/#news') }}" class="btn-back">← Back to News</a>
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
// Fade-in on observe
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
