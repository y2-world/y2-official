@extends('layouts.app')
@section('content')
<br>
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <h3> {{$songs->title}}</h3>
            <hr>
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