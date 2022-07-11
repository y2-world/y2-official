@extends('layouts.app')
@section('content')
<br>
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
          <a class="btn btn-outline-dark btn-sm" href="{{ url('/songs') }}" role="button">Songs</a>
          <a class="btn btn-outline-dark btn-sm" href="{{ url('/singles') }}" role="button">Singles</a>
          <a class="btn btn-outline-dark btn-sm" href="{{ url('/albums') }}" role="button">Albums</a>
          <br><br>
          <div class="setlist">
            @if(!is_null($singles->single_id))
            # {{$singles->single_id}}
            @else
            Download Single
            @endif
          </div>
          <h3> {{$singles->title}}</h3>
          <div class="setlist">
            Release Date : {{ date('Y.m.d', strtotime($singles->date)) }}
            <hr>
            @if(!is_null($singles->text))
            {!! nl2br(e($singles->text)) !!}
            <br><br>
            @endif
            @foreach ($songs as $song)
              @if($singles->id == $song->single_id)
              {{$song->single_trk}}. <td><a href="{{ route('songs.show', $song->id) }}">{{ $song->title }}</a></td>
              <br>
              @endif
            @endforeach
          </div>
          <br>
          <div class="show_button">
            @if(isset($previous))
            <a class="btn btn-outline-dark" href="{{ route('singles.show', $previous->id)}}" rel="prev" role="button"><</a>
            @endif
            @if(isset($next))
            <a class="btn btn-outline-dark" href="{{ route('singles.show', $next->id)}}"rel="next" role="button">></a>
            @endif
          </div> 
        </div>
    </div>       
</div>         
@endsection