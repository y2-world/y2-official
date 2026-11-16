@extends('layouts.app')

@section('og_title', $news->title . ' - Yuki Official')
@section('og_description', Str::limit(trim(strip_tags($news->text_html)), 150))
@section('og_type', 'article')
@if($news->image)
@section('og_image', 'https://res.cloudinary.com/hqrgbxuiv/' . $news->image)
@endif

@section('content')
<div class="news-detail-wrapper" style="padding-top: 40px;">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-xl-8">
                <div class="element js-fadein">
                    @include('official_news._detail', ['showBackLink' => true])
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
