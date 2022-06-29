@extends('layouts.app')
@section('content')
<br>
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            @if(isset($songs->song_id))
            # {{$songs->song_id}}
            @endif
            <h3> {{$songs->title}}</h3>
            <hr>
            @if(isset($songs->album->title))
                @if(isset($songs->song_id))
                Album : <a href="{{ route('albums.show', $songs->album_id) }}">{{ $songs->album->title }}</a></td>
                @endif
                <br>
                @if(isset($songs->album->date))
                Release Date : {{ date('Y.m.d', strtotime($songs->album->date)) }}
                @endif
            @else
                アルバム未収録
            @endif
            <br>
            <br>
            <div class="show_button">
                @if(isset($previous))
                <a class="btn btn-outline-dark" href="{{ route('songs.show', $previous->id)}}" rel="prev" role="button"><</a>
                @endif
                @if(isset($next))
                <a class="btn btn-outline-dark" href="{{ route('songs.show', $next->id)}}"rel="next" role="button">></a>
                @endif
            </div> 
      </div>
    </div>       
</div>         
@endsection