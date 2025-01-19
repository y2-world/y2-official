@extends('layouts.app')
@section('content')
    <?php
    $today = strtotime(date('Y-m-d'));
    ?>
    <div class="cover">
        <div class="element js-fadein">
            <img src={{ asset('images/top_image.jpg') }}>
            <p><span>Yuki Yoshida</span> <span>Official Website</span></p>
        </div>
    </div>

    <div class="home-disco-wrapper">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-xl-9">
                    <div class="element js-fadein">
                        <h2 class="news-title">Music</h2>
                        <div class="cover-wrapper">
                            @foreach ($discos as $disco)
                                <div class="disc-block">
                                    @if (!empty($disco->url))
                                        <a href="{{ $disco->url }}"><img
                                                src={{ asset('https://res.cloudinary.com/hqrgbxuiv/' . $disco->image) }}
                                                class="top-image"></a>
                                    @else
                                        <a href="{{ route('music.show', $disco->id) }}"><img
                                                src={{ asset('https://res.cloudinary.com/hqrgbxuiv/' . $disco->image) }}
                                                class="top-image"></a>
                                    @endif
                                    <br><br>
                                    <div class="topic"><a
                                            href="{{ route('music.show', $disco->id) }}">{{ $disco->title }}</a>
                                    </div>
                                    <p class="topic">{{ date('Y.m.d', strtotime($disco->date)) }} - {{ $disco->subtitle }}
                                    </p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="topic-more">
                        <a href="{{ url('/music') }}">View All</a>
                    </div>
                </div>
            </div>
            <br>
        </div>
    </div>
    </div>

    <div class="index-wrapper" id="news" style="padding-top:55px">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-xl-9">
                    <div class="element js-fadein">
                        <h2 class="news-title">News</h2>
                        
                        <!-- ニュースリストを格納するコンテナ -->
                        <div id="news-container">
                            @foreach ($news as $new)
                                @if ($new->visible != 1)
                                    <div class="news-item">
                                        <a href="{{ route('news.show', $new->id) }}" class="news-link">
                                            <div class="topic-title">
                                                <div class="date">{{ date('Y.m.d', strtotime($new->date)) }}</div>
                                                {{ $new->title }}
                                            </div>
                                        </a>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                        
                        <!-- View Allボタン -->
                        <div class="topic-more">
                            <a href="javascript:void(0);" id="view-all-btn">View All</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="index-wrapper" id="profile" style="padding-top:55px">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-xl-9">
                    <div class="element js-fadein">
                        <h2>Profile</h2>
                        <div class="row">
                            @if ($profiles)
                                @foreach ($profiles as $profile)
                                    <div class="col-lg-6 my-auto">
                                        <div class="prof_img">
                                            <img src={{ asset('https://res.cloudinary.com/hqrgbxuiv/' . $profile->image) }}
                                                class="image" width="80%">
                                        </div>
                                    </div>
                                    <div class="col-lg-5 my-auto">
                                        <br>
                                        <h2>{{ $profile->name }}</h2>
                                        <p class="profile-info">{{ $profile->info }}</p>
                                        <hr>
                                        <p class="profile-text">{!! nl2br(e($profile->text)) !!}</p>
                                        <hr>
                                @endforeach
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            <br>
        </div>
    </div>
    </div>

    <div class="index-wrapper" id="radio" style="padding-top:70px">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-xl-9">
                    <div class="element js-fadein">
                        <h2>Radio</h2>
                        <div class="radio-wrapper">
                            <iframe style="border-radius:12px"
                                src="https://open.spotify.com/embed/show/5uQQnvpk9DSuY4rBwptQkZ?utm_source=generator&theme=0"
                                width="100%" height="352" frameBorder="0" allowfullscreen=""
                                allow="autoplay; clipboard-write; encrypted-media; fullscreen; picture-in-picture"
                                loading="lazy"></iframe>
                        </div>
                    </div>
                </div>
                <br>
            </div>
        </div>
    </div>

    <div class="footer-wrapper">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-xl-9">
                    <div class="footer">
                        <div class="footer-title">
                            Yuki Yoshida Official Website
                        </div>
                        <a href="{{ url('/music') }}">Music</a>・
                        <a href="{{ url('/#news') }}">News</a>・
                        <a href="{{ url('/#profile') }}">Profile</a>・
                        <a href="{{ url('/#radio') }}">Radio</a>・
                        <a href="{{ url('https://ameblo.jp/y2-world') }}" target="_blank">Blog</a>・
                        <a href="{{ url('/admin') }}" target="_blank">Admin</a>
                        <br>
                        <div class="footer-copyright">©2024 y2 records inc.</div>
                    </div>
                </div>
                <br>
            </div>
        </div>
    </div>
@endsection
