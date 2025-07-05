@extends('layouts.app')
@section('title', 'Yuki Official - ' . $albums->title)
@section('content')
<br>
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
          <div class="setlist">
            @if($albums->best == 0)
            # {{$albums->album_id}}
            @else
            Best Album
            @endif
          </div>
          <h3> {{$albums->title}}</h3>
          <div class="setlist">
            Release Date : {{ date('Y.m.d', strtotime($albums->date)) }}
            <hr>
            @if(isset($albums->tracklist))
            <ol>
              @foreach ($albums->tracklist as $data)
                @if(isset($data['disc']))
                  @if($data['disc'] !== 'END')
                    @if(isset($data['exception']))
                      {{ $data['disc'] }}
                      <li> <a href="{{ url('/database/songs', $data['id'])}}">{{ $data['exception'] }}</a></li>
                    @else
                      {{ $data['disc'] }}<br>
                      <li><a  href="{{ url('/database/songs', $data['id']) }}">{{ $songs[$data['id'] - 1]['title'] }}</a></li>
                    @endif
                  @elseif($data['disc'] == 'END')
                    @if(isset($data['exception']))
                    <li> <a href="{{ url('/database/songs', $data['id'])}}">{{ $data['exception'] }}</a></li>
                    @else
                    <li> <a  href="{{ url('/database/songs', $data['id']) }}">{{ $songs[$data['id'] - 1]['title'] }}</a></li>
                    </ol>
                    <ol>
                    @endif
                  @endif
                @else
                  @if(!isset($data['id']))
                  <li> {{ $data['exception'] }}</li>
                  @elseif(isset($data['exception']) && isset($data['id']))
                  <li>  <a href="{{ url('/database/songs', $data['id'])}}">{{ $data['exception'] }}</a></li>
                  @else
                  <li> <a  href="{{ url('/database/songs', $data['id']) }}">{{ $songs[$data['id'] - 1]['title'] }}</a></li>
                  @endif
                @endif
              @endforeach
            </ol>
            @endif
          </div>
          <div class="show_button">
            @if(isset($previous))
            <a class="btn btn-outline-dark" href="{{ route('albums.show', $previous->id)}}" rel="prev" role="button"><i class="fa-solid fa-arrow-left fa-lg"></i></a>
            @endif
            @if(isset($next))
            <a class="btn btn-outline-dark" href="{{ route('albums.show', $next->id)}}"rel="next" role="button"><i class="fa-solid fa-arrow-right fa-lg"></i></a>
            @endif
          </div> 
        </div>
    </div>       
</div>         
@endsection