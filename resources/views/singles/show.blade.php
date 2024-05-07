@extends('layouts.app')
@section('content')
<br>
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
          <div class="setlist">
            @if($singles->download == 0)
            # {{$singles->single_id}}
            @else
            Download Single
            @endif
          </div>
          <h3> {{$singles->title}}</h3>
          <div class="setlist">
            Release Date : {{ date('Y.m.d', strtotime($singles->date)) }}
            <hr>
            @if(isset($singles->tracklist))
            <ol>
              @foreach ($singles->tracklist as $data)
                @if(isset($data['id']))
                <li> <a href="{{ url('/database/songs', $data['id']) }}">{{ $songs[$data['id'] - 1]['title'] }}</a></li>
                  @if(isset($data['exception']))
                  <li> <a href="{{ url('/database/songs', $data['id']) }}">{{ $data['exception']}}</a></li>
                  @endif
                @else
                <li>{{ $data['exception'] }}</li>
                @endif
              @endforeach
                </ol>
            @endif
          </div>
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