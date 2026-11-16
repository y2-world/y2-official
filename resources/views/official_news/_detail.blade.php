{{-- ニュースの本文。URL直打ちのページ（official_news.show）とトップのポップアップ（JSONの html）で同じものを出す。
     $showBackLink（任意）：「Back to News」を出す（URL直打ちのページだけ） --}}
<div class="news-item">
    <h4>{{ $news->title }}</h4>
    <small class="date">
        {{ $news->published_at ? $news->published_at->format('Y.m.d') : ($news->date ? date('Y.m.d', strtotime($news->date)) : '') }}
    </small>
    <hr>
    @if($news->image)
    <div class="row">
        <div class="col-md-7">
            <div class="news-text">
                {!! $news->text_html !!}
            </div>
        </div>
        <div class="col-md-5">
            <img src="https://res.cloudinary.com/hqrgbxuiv/{{ $news->image }}" alt="{{ $news->title }}" class="image" style="width: 100%; height: auto;">
        </div>
    </div>
    @else
    <div class="news-text">
        {!! $news->text_html !!}
    </div>
    @endif
    @if($showBackLink ?? false)
    <div style="text-align: center; padding: 30px 0;">
        <a href="{{ url('/#news') }}" style="color: #666; text-decoration: none; font-size: 14px;">← Back to News</a>
    </div>
    @endif
</div>
