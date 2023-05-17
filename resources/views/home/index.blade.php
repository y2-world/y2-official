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

<div class="index-wrapper">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-11">
                <!-- <div class="element js-fadein"> -->
                <h2 class="news-title">New Release</h2>
                <div class="cover-wrapper">
                    @foreach ($discos as $disco)
                    @if (strtotime($disco->date) <= $today) <div class="disc-block">
                        <img src={{ asset('https://res.cloudinary.com/hqrgbxuiv/'. $disco->image) }} class="top-image">
                        <br><br>
                        <div class="topic"><a href="{{ route('music.show', $disco->id) }}">{{$disco->title}}</a></div>
                        <p class="topic">{{ date('Y.m.d', strtotime($disco->date)) }} - {{$disco->subtitle}}</p>
                </div>
                @endif
                @endforeach
            </div>
            <div class="topic-more">
                <a href="{{ url('/music') }}">View All</a>
            </div>
            <!-- </div> -->
        </div>
        <br>
    </div>
</div>
</div>

<div class="index-wrapper">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-11">
                <div class="element js-fadein">
                    <h2 class="news-title">News</h2>
                    @foreach ($news as $new)
                        <a href="{{ route('news.show', $new->id) }}">
                            <div class="topic-title"> 
                            <hr>
                                <div class="date">{{ date('Y.m.d', strtotime($new->date)) }}</div>
                                {{$new->title}}
                            </div>
                        </a>
                    @endforeach
                    <hr>
                    <div class="topic-more">
                        <a href="{{ url('/news') }}">View All</a>
                    </div>
                </div>
            </div>
            <br>
        </div>
    </div>
</div>

<div class="index-wrapper" id="profile">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-11">
                <div class="element js-fadein">
                    <h2>Profile</h2>
                    <div class="row">
                        @if($profiles)
                        @foreach ($profiles as $profile)
                        <div class="col-md-6 my-auto">
                            <div class="prof_img">
                                <img src={{ asset('https://res.cloudinary.com/hqrgbxuiv/'. $profile->image) }} class="image" width="80%">
                            </div>
                        </div>
                        <div class="col-md-5 my-auto">
                            <br>
                            <h2>{{$profile->name}}</h2>
                            <p class="profile-info">{{$profile->info}}</p>
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

<div class="index-wrapper" id="radio">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-11">
                <div class="element js-fadein">
                    <h2>Radio</h2>
                    <div class="radio-wrapper">
                        <iframe src="https://embed.podcasts.apple.com/us/podcast/y2-radio/id1555086566?itsct=podcast_box_player&amp;itscg=30200&amp;ls=1&amp;theme=light" height="450px" frameborder="0" sandbox="allow-forms allow-popups allow-same-origin allow-scripts allow-top-navigation-by-user-activation" allow="autoplay *; encrypted-media *; clipboard-write" style="width: 100%; max-width: 1080px; overflow: hidden; border-radius: 10px; background: transparent;"></iframe>
                    </div>
                </div>
            </div>
            <br>
        </div>
    </div>
</div>

<div class="app-wrapper">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-11">
                <div class="element js-fadein">
                    <h2>Yuki Yoshida Official Fanclub『NAPO』</h2>
                    <h5>NAPO = Neo Artist Personal Fanclub</h5>
                </div>
                <br>
                <div class="element js-fadein">
                    Yuki Yoshida Official Fanclubが7月に発足されることが決定しました！<br>
                    年会費はもちろん無料です。<br>
                    スペシャルコンテンツをご用意する予定です！<br>
                    乞うご期待！
                    <br><br>
                    <h5>LIVE×YOU</h5>
                    このアプリは、自分が参加したライブのセットリストを見返したり、フォロワーが参加したライブの感想を読んだり、直接メッセージを送ることもできます。<br>
                    ライブを愛する人のためのアプリです！<br>
                    7月公開予定<br>
                    <br>
                    <h5>Mr.Children Database</h5>
                    Mr.Childrenのバイオグラフィー、ディスコグラフィー、ライブのセットリストなど全ての情報がここに！
                    <br>
                    <br>
                    <h5>Special Blog 『NAPO』</h5>
                    Official Blogでも投稿できない限定記事をここだけに公開！
                    <br>
                    <br>
                    <h5>Napostagram</h5>
                    Official Instagramでも投稿されない限定写真をここだけに公開！
                    <br>
                </div>
            </div>
            <br>
        </div>
    </div>
</div>

<div class="footer-wrapper">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-11">
                <div class="footer">
                    <div class="footer-title">
                        Yuki Yoshida Official Website
                    </div>
                    <a href="{{ url('/news') }}">News</a>・
                    <a href="{{ url('/music') }}">Music</a>・
                    <a href="{{ url('/home/#profile') }}">Profile</a>・
                    <a href="{{ url('/home/#radio') }}">Radio</a>・
                    <a href="{{ url('/login') }}">Fanclub</a>・
                    <a href="{{ url('https://ameblo.jp/y2-world') }}" target="_blank">Blog</a>
                    <br>
                    <div class="footer-copyright">©2023 y2_record inc.</div>
                </div>
            </div>
            <br>
        </div>
    </div>
</div>

<!-- <script>
{
    $(function () {
        $('a[href^="#"]').not('.remove-class').click(function () {
            var speed = 500;
            var href = $(this).attr("href");
            var target = $(href == "#" || href == "" ? "html" : href);
            var position = target.offset().top;
            $("html, body").animate({ scrollTop: position }, speed, "swing");
            return false;
        });
    });
}
</script> -->
@endsection