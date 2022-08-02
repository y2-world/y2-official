@extends('layouts.app')
@section('content')
<div class="mt-4"></div>
    <div class="news">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="element js-fadein">
                        <small class="date">{{ date('Y.m.d', strtotime($news->date)) }}</small>
                        <h4>{{$news->title}}</h4>
                        <hr>
                        @if(isset($news->image))
                        <div class="row">
                            <div class="col-md-6">
                                <p class="text"> {!! nl2br(e($news->text)) !!}</p>
                            </div>
                            <div class="col-md-6">
                                <div class="modal-img">
                                    <img src={{ asset('upload/'. $news->image) }} class="disco-image" width="100%">
                                </div>
                            </div>
                        </div>
                        @else
                        <p class="text"> {!! nl2br(e($news->text)) !!}</p>
                        @endif
                        <div class="show_button">
                            @if(isset($previous))
                            <a class="btn btn-outline-dark" href="{{ route('news.show', $previous->id)}}" rel="prev" role="button"><</a>
                            @endif
                            <a class="btn btn-outline-dark" href="{{ route('news.index')}}" rel="prev" role="button">BACK</a>
                            @if(isset($next))
                            <a class="btn btn-outline-dark" href="{{ route('news.show', $next->id)}}"rel="next" role="button">></a>
                            @endif
                        </div> 
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection