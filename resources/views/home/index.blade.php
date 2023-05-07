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
                <div class="element js-fadein">
                    <h2 class="news-title">Topics</h2>
                    @foreach ($news as $new)
                    <div class="topic">
                        <hr>
                        <a href="{{ route('news.show', $new->id) }}">
                            <div class="date">{{ date('Y.m.d', strtotime($new->date)) }}</div>
                            {{$new->title}}
                        </a>
                    </div>
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
                            <p>{{$profile->info}}
                            <p>
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