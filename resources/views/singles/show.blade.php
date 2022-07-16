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
              @foreach ($singles->tracklist as $data)
                {{ $data['#'] }}. <a href="{{ url('songs', $data['song'])}}">{{ $data['song'] }}</a><br>
              @endforeach
              <br>
            @endif
            @if(!is_null($singles->text))
            {!! nl2br(e($singles->text)) !!}
            @endif
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