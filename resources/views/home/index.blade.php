@extends('layouts.app')
@section('content')
<div class="cover">
    <div class="element js-fadein">
        <img src={{ asset('images/top_image.jpg') }}>
        <p><span>Yuki Yoshida</span> <span>Official Website</span></p>
    </div>
</div>

<div class="topics">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-11">
                <h2 class="news-title">New Release</h2>
                <div class="cover-wrapper">
                    @foreach ($discos as $disco)
                    @if(date('Y-m-d', strtotime($disco->date)) < 2023-05-08)
                    <div class="disc-block">
                        <img src={{ asset('https://res.cloudinary.com/hqrgbxuiv/'. $disco->image) }} class="top-image">
                        <br><br>
                        <div class="topic"><a href="{{ route('music.show', $disco->id) }}">{{$disco->title}}</a></div>
                        <p class="topic">{{ date('Y.m.d', strtotime($disco->date)) }} - {{$disco->subtitle}}</p>
                    </div>
                    @endif
                    @endforeach
                </div>
            </div>
            <br>
        </div>
        <!-- <ul class="topic-menu">
                <li><a href="#" class="active" data-id="menu-topics">TOPICS</a></li>
                <li><a href="#" data-id="menu-release">NEW RELEASE</a></li>
                <li><a href="#" data-id="menu-radio">RADIO</a></li>
            </ul>
            <hr>
            <div class="topic-container">
                <section class="content active" id="menu-topics">
                    @foreach ($news as $new)
                    <div class="topic-list">
                        <div class="date">{{ date('Y.m.d', strtotime($new->date)) }}</div>
                        <div class="topic"><a href="{{ route('news.show', $new->id) }}">{{$new->title}}</a></div>
                    </div>
                    @endforeach
                    <div class="topic-more">
                        <a href="{{ url('/news') }}">MORE</a>
                    </div>
                </section>
                <section class="content" id="menu-release">
                    @foreach ($discos as $disco)
                    <div class="topic-list">
                        <div class="date">{{ date('Y.m.d', strtotime($disco->date)) }}</div>
                        <div class="topic"><a href="{{ route('music.show', $disco->id) }}">{{$disco->title}}</a> - {{$disco->subtitle}}
                            <span class="topic-text">{{$disco->text}}</span>
                        </div>
                    </div>
                    @endforeach
                    <div class="topic-more">
                        <a href="{{ url('/music') }}">MORE</a>
                    </div>
                </section>
                <section class="content" id="menu-radio">
                    @foreach ($radios as $radio)
                    <div class="topic-list">
                        <div class="date">{{ date('Y.m.d', strtotime($radio->date)) }}</div>
                        <div class="home-radio">
                            <div class="topic">{{$radio->title}}</div>
                        </div>
                    </div>
                    @endforeach
                    <div class="topic-more">
                        <a href="{{ url('/radio') }}">MORE</a>
                    </div>
                </section>
            </div> -->
    </div>
</div>

<div class="topics">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-11">
                <div class="element js-fadein">
                    <h2 class="news-title">Topics</h2>
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
                        <a href="{{ url('/news') }}">MORE</a>
                    </div>
                </div>
            </div>
            <br>
        </div>
    </div>
</div>

<div class="topics" id="#profile">
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
@endsection