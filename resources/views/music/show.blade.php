@extends('layouts.app')
@section('content')
<div class="mt-4"></div>
    <div class="news">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="element js-fadein">
                        <small class="date">{{ $discos->subtitle}}</small>
                        <h4>{{$discos->title}}</h4>
                        <small class="date">{{ date('Y.m.d', strtotime($discos->date)) }}</small>
                        <hr>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="modal-img">
                                    <img src={{ asset('upload/'. $discos->image) }} class="disco-image" width="100%">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class=track-list>
                                @foreach ($discos->tracklist as $data) 
                                {{ $data['#'] }}. {{ $data['title'] }}<br>
                                @endforeach
                                </div>
                            </div>
                        </div>
                        <div class="show_button">
                            @if(isset($previous))
                            <a class="btn btn-outline-dark" href="{{ route('music.show', $previous->id)}}" rel="prev" role="button"><</a>
                            @endif
                            <a class="btn btn-outline-dark" href="{{ route('music.index')}}" rel="prev" role="button">BACK</a>
                            @if(isset($next))
                            <a class="btn btn-outline-dark" href="{{ route('music.show', $next->id)}}"rel="next" role="button">></a>
                            @endif
                        </div> 
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection