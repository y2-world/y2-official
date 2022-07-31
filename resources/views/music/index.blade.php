@extends('layouts.app')
@section('content')
<div class="mt-4"></div>
<div class="disco">
    {{-- <div class="container"> --}}
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div id="mask" class="hide"></div>
                <h2 class="music-header">Music</h2>
                    <ul class="music-menu">
                        <li><a href="{{ url('/music/single') }}">Single</a></li>
                        <li><a href="{{ url('/music/album') }}" class="active">Album</a></li>
                    </ul>
                    <hr>
                    <div class="element js-fadein">
                        <div class="disco-wrapper">
                            @foreach ($discos as $disc)
                                <div class="album-wrapper">
                                    <div class="single">
                                        <img src={{ asset('{{$disc->image}}') }} class="single-image">
                                        <div class="topic"><a href="{{ route('music.show', $disc->id) }}">{{$disc->title}}</a></div>
                                        <p class="text">{{$disc->subtitle}}<br>{{ date('Y.m.d', strtotime($disc->date)) }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    {{-- </div> --}}
</div>
@endsection