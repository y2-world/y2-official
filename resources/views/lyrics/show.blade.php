@extends('layouts.app')
@section('content')
<div class="mt-4"></div>
    <div class="news">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="element js-fadein">
                        <h4>{{$lyrics->title}}</h4>
                        <hr>
                        @if(isset($lyrics->lyrics))
                            <p class="text"> {!! nl2br(e($lyrics->lyrics)) !!}</p>
                        @endif
                        <div class="show_button">
                            {{-- @if(isset($previous))
                            <a class="btn btn-outline-dark" href="{{ route('lyrics.show', $previous->id)}}" rel="prev" role="button"><</a>
                            @endif --}}
                            <a class="btn btn-outline-dark"　href="#" onclick="window.history.back(); return false;">BACK</a>
                            {{-- <a class="btn btn-outline-dark" href="{{ route('lyrics.index')}}" rel="prev" role="button">BACK</a> --}}
                            {{-- @if(isset($next))
                            <a class="btn btn-outline-dark" href="{{ route('lyrics.show', $next->id)}}"rel="next" role="button">></a>
                            @endif --}}
                        </div> 
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection