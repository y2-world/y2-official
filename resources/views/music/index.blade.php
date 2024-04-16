@extends('layouts.app')
@section('content')
<div class="mt-4"></div>
<div class="disco">
    <div class="container-lg">
        {{-- <div class="row justify-content-center"> --}}
            <div class="col-md-12">
                <h2 class="music-header">Music</h2>
                    <ul class="music-menu">
                        <li><a href="{{ url('/music') }}" class="active">All</a></li>
                        <li><a href="{{ url('/single') }}">Single</a></li>
                        <li><a href="{{ url('/album') }}">Album</a></li>
                    </ul>
                    <div class="element js-fadein">
                        <div class="disco-wrapper">
                            <div class="album-wrapper">
                            @foreach ($discos as $disc)
                                <div class="single">
                                    <a href="{{ route('music.show', $disc->id) }}"><img src={{ asset('https://res.cloudinary.com/hqrgbxuiv/'. $disc->image) }} class="single-image"></a>
                                    <div class="topic"><a href="{{ route('music.show', $disc->id) }}">{{$disc->title}}</a></div>
                                    <p class="topic">{{$disc->subtitle}}<br>{{ date('Y.m.d', strtotime($disc->date)) }}</p>
                                </div>
                            @endforeach
                        </div>
                        </div>
                    </div>
                </div>
            </div>
        {{-- </div> --}}
    </div>
</div>
@endsection