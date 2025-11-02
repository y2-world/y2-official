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

    <div class="index-wrapper" id="news" style="padding-top:100px">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-xl-9">
                    <div class="element js-fadein">
                        <h2 class="news-title">News</h2>

                        <!-- ニュースリストを格納するコンテナ -->
                        <div id="news-container">
                            @foreach ($news as $new)
                                @if ($new->visible == 1)
                                    <div class="news-item">
                                        <a href="javascript:void(0);" class="news-link" data-id="{{ $new->id }}">
                                            <div class="news-item__title">
                                                <div class="date">{{ date('Y.m.d', strtotime($new->date)) }}</div>
                                                {{ $new->title }}
                                            </div>
                                        </a>
                                    </div>
                                @endif
                            @endforeach
                        </div>

                        <!-- View Allボタン -->
                        <div class="more">
                            <a href="javascript:void(0);" id="view-all-news-btn">View All</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="overlay" class="overlay"></div>
    <div id="news-popup" class="popup">
        <div class="popup-content">
            <span class="close-btn">&times;</span>
            <div class="news-item">
                <div id="popup-title" style="padding-right: 10px; display: inline-block;"></div> <!-- タイトル -->
                <small class="date" id="popup-date"></small> <!-- 日付 -->
                <hr>
                <div class="text" id="popup-text"></div> <!-- テキスト -->
                <img class="image" width="100%"  style="padding-top: 10px;" id="popup-img"> <!-- 画像 -->
            </div>
        </div>
    </div>

    <div class="index-wrapper" id="music" style="padding-top:100px">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-9">
                    <div class="element js-fadein">
                        <h2 class="music-header">Music</h2>
                        {{-- <ul class="music-menu">
                            <li><a href="{{ url('/music') }}" class="{{ request('type') == '' ? 'active' : '' }}">All</a>
                            </li>
                            <li><a href="{{ url('/music?type=1') }}"
                                    class="{{ request('type') == '1' ? 'active' : '' }}">Single</a>
                            </li>
                            <li><a href="{{ url('/music?type=2') }}"
                                    class="{{ request('type') == '2' ? 'active' : '' }}">Album</a>
                            </li>
                        </ul> --}}
                        <div class="music-wrapper">
                            <div class="album-wrapper album-wrapper-scroll" id="music-container">
                                <div class="album-wrapper-inner">
                                    @foreach ($discos as $disc)
                                        <div class="album-container">
                                            <a href="{{ route('music.show', $disc->id) }}">
                                                <img src="{{ asset('https://res.cloudinary.com/hqrgbxuiv/' . $disc->image) }}"
                                                    class="album-image">
                                            </a>
                                            <div class="music-item__gray">
                                                <a href="{{ route('music.show', $disc->id) }}">{{ $disc->title }}</a>
                                                <p>
                                                    {{ $disc->subtitle }}<br>{{ date('Y.m.d', strtotime($disc->date)) }}
                                                </p>
                                            </div>
                                        </div>
                                    @endforeach
                                    @foreach ($discos as $disc)
                                        <div class="album-container">
                                            <a href="{{ route('music.show', $disc->id) }}">
                                                <img src="{{ asset('https://res.cloudinary.com/hqrgbxuiv/' . $disc->image) }}"
                                                    class="album-image">
                                            </a>
                                            <div class="music-item__gray">
                                                <a href="{{ route('music.show', $disc->id) }}">{{ $disc->title }}</a>
                                                <p>
                                                    {{ $disc->subtitle }}<br>{{ date('Y.m.d', strtotime($disc->date)) }}
                                                </p>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <!-- View Allボタン -->
                        <div class="more">
                            <a href="javascript:void(0);" id="view-all-music-btn">View All</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="index-wrapper" id="profile" style="padding-top:100px">
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
                                    </div>
                                @endforeach
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            <br>
        </div>
    </div>

    <div class="index-wrapper" id="radio" style="padding-top:100px">
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
                        <a href="{{ url('/#news') }}">News</a>・
                        <a href="{{ url('/#music') }}">Music</a>・
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

@section('page-script')
<script src="{{ asset('/js/search.js?time=' . time()) }}"></script>
<script src="{{ asset('/js/top.js?time=' . time()) }}"></script>
@endsection
