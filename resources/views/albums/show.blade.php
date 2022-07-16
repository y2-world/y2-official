@extends('layouts.app')
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
              @foreach ($albums->tracklist as $data)
                @if(isset($data['disc']))
                  @if($data['disc'] !== 'END')
                      {{ $data['disc'] }}<br>
                      {{ $data['#'] }}. <a href="{{ url('songs', $data['song'])}}">{{ $data['song'] }}</a><br>
                  @else
                      {{ $data['#'] }}. <a href="{{ url('songs', $data['song'])}}">{{ $data['song'] }}</a><br><br>
                  @endif
                @else
                  {{ $data['#'] }}. <a href="{{ url('songs', $data['song'])}}">{{ $data['song'] }}</a><br>
                @endif
              @endforeach
              <br>
            @endif
            @if(!is_null($albums->text))
            {!! nl2br(e($albums->text)) !!}
            @endif
          </div>
          <br>
          <div class="show_button">
            @if(isset($previous))
            <a class="btn btn-outline-dark" href="{{ route('albums.show', $previous->id)}}" rel="prev" role="button"><</a>
            @endif
            @if(isset($next))
            <a class="btn btn-outline-dark" href="{{ route('albums.show', $next->id)}}"rel="next" role="button">></a>
            @endif
          </div> 
        </div>
    </div>       
</div>         
@endsection