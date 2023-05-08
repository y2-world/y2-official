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

<div class="topics">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-11">
                <!-- <div class="element js-fadein"> -->
                    <h2 class="news-title">Music</h2>
                    <div class="cover-wrapper">
                        @foreach ($discos as $disco)
                        @if (strtotime($disco->date) <= $today)
                        <div class="disc-block">
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
                        <a href="{{ url('/news') }}">View All</a>
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