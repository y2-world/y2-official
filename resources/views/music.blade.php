@extends('layouts.app')
@section('content')
<div class="mt-4"></div>
<div class="music">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <h2>Music</h2>
                    <ul class="music-menu">
                        <li><a href="{{ url('/music') }}" class="active">Info</a></li>
                        <li><a href="{{ url('/music/single') }}">Single</a></li>
                        <li><a href="{{ url('/music/album') }}">Album</a></li>
                    </ul>
                    <div class="element js-fadein">
                        <hr>
                        <h5>Listen on</h5>
                        <a href="https://music.apple.com/jp/artist/yuki-yoshida/1448865361?itsct=music_box_badge&itscg=30200&ct=artists_yuki_yoshida&app=music&ls=1"><i class="fab fa-apple fa-3x"></i></a>
                        Apple Music&emsp;
                        <a href="https://open.spotify.com/artist/5x6TjqB9xXXjY4Xn5y2oJm"><i class="fab fa-spotify fa-3x"></i></a>
                        Spotify
                        <hr>
                        <h5>Watch on</h5>
                        <div class="container">
                            <div class="movie-wrap">
                                <iframe width="480" height="270" src="https://www.youtube.com/embed/videoseries?list=PLPky7Hthrm_dRbmcTqh6hKOavDpCyKBvt" frameborder="0" allow="autoplay; encrypted-media" allowfullscreen></iframe>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection