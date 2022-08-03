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
                    @if(isset($data['id']) && !isset($data['song']))
                      {{ $data['disc'] }}<br>
                      {{ $data['#'] }}. <a href="{{ url('songs', $data['id'])}}">{{ $data['exception'] }}</a><br>
                    @elseif(isset($data['id']) && !isset($data['exception']))
                      {{ $data['disc'] }}<br>
                      {{ $data['#'] }}. <a href="{{ url('songs', $data['id'])}}">{{ $data['song'] }}</a><br>
                    @endif
                  @elseif($data['disc'] == 'END')
                    @if(isset($data['id']) && !isset($data['song']))
                        {{ $data['#'] }}. <a href="{{ url('songs', $data['id'])}}">{{ $data['exception'] }}</a><br><br>
                    @elseif(isset($data['id']) && !isset($data['exception']))
                        {{ $data['#'] }}. <a href="{{ url('songs', $data['id'])}}">{{ $data['song'] }}</a><br><br>
                    @endif
                  @endif
                @else
                  @if(isset($data['song']))
                  {{ $data['#'] }}. <a href="{{ url('songs', $data['id'])}}">{{ $data['song'] }}</a><br>
                  @elseif(isset($data['id']) && isset($data['exception']))
                  {{ $data['#'] }}. <a href="{{ url('songs', $data['id'])}}">{{ $data['exception'] }}</a><br>
                  @elseif(!isset($data['id']) && isset($data['exception']))
                  {{ $data['#'] }}. {{ $data['exception'] }}<br>
                  @endif
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