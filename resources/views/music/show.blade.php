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
                            <div class="col-xl-6">
                                <div class="modal-img">
                                    <img src={{ asset('https://res.cloudinary.com/hqrgbxuiv/'. $discos->image) }} class="disco-image" width="100%"> 
                                </div>
                            </div>
                            @if(!empty($discos->tracklist))
                            <div class="col-xl-6">
                                <div class=track-list>
                                    <ol>
                                @foreach ($discos->tracklist as $data) 
                                    @if(isset($data['id']))
                                    <li><a href="{{ url('lyrics', $data['id'])}}">{{ $lyrics[$data['id'] - 1]['title'] }}</a></li>
                                    @elseif(isset($data['exception']))
                                    <li>{{ $data['exception'] }}</li>
                                    @endif
                                @endforeach
                                    </ol>
                                <br>
                                </div>
                            </div>
                            @endif
                        </div>
                        <div class="show_button">
                            {{-- @if(isset($previous))
                            <a class="btn btn-outline-dark" href="{{ route('music.show', $previous->id)}}" rel="prev" role="button"><</a>
                            @endif --}}
                            <a class="btn btn-outline-dark"　href="#" onclick="window.history.back(); return false;">BACK</a>
                            {{-- <a class="btn btn-outline-dark" href="{{ route('music.index')}}" rel="prev" role="button">BACK</a> --}}
                            {{-- @if(isset($next))
                            <a class="btn btn-outline-dark" href="{{ route('music.show', $next->id)}}"rel="next" role="button">></a>
                            @endif --}}
                        </div> 
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection