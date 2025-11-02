@extends('layouts.app')
@section('title', 'Yuki Official - ' . $discos->title)
@section('content')
    <div class="mt-4"></div>
    <div class="album-detail">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="element js-fadein">
                        <small class="date">{{ $discos->subtitle }}</small>
                        <h4>{{ $discos->title }}</h4>
                        <small class="date">{{ date('Y.m.d', strtotime($discos->date)) }}</small>
                        <hr>
                        <div class="row">
                            <div class="col-xl-6">
                                <div class="modal-img">
                                    <img src="https://res.cloudinary.com/hqrgbxuiv/{{ $discos->image }}"
                                        style="width: 100%;">
                                </div>
                            </div>
                            @if (!empty($discos->tracklist))
                                <div class="col-xl-6">
                                    <div class=track-list id="music-container">
                                        <ol>
                                            @foreach ($discos->tracklist as $data)
                                                @if (isset($data['id']) && isset($data['exception']))
                                                    <li>
                                                        <a href="javascript:void(0);" class="music-link"
                                                            data-id="{{ $data['id'] }}">{{ $data['exception'] }}</a>
                                                    </li>
                                                @elseif(isset($data['id']))
                                                    <li>
                                                        <a href="javascript:void(0);" class="music-link"
                                                            data-id="{{ $data['id'] }}">{{ $lyrics[$data['id'] - 1]['title'] }}</a>
                                                    </li>
                                                @elseif(isset($data['exception']))
                                                    <li>{{ $data['exception'] }}</li>
                                                @endif
                                            @endforeach
                                        </ol>
                                        @if (!empty($discos->url))
                                            <div class="show_button">
                                                <a class="btn btn-outline-dark" href="{{ $discos->url }}">Streaming</a>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endif
                        </div>
                        <div class="show_button">
                            {{-- @if (isset($previous))
                            <a class="btn btn-outline-dark" href="{{ route('music.show', $previous->id)}}" rel="prev" role="button"><i class="fa-solid fa-arrow-left fa-lg"></i></a>
                            @endif --}}
                            <a href="#" onclick="window.history.back(); return false;"> <i class="fa-solid fa-arrow-left fa-lg"></i></a>
                            {{-- <a class="btn btn-outline-dark" href="{{ route('music.index')}}" rel="prev" role="button">BACK</a> --}}
                            {{-- @if (isset($next))
                            <a class="btn btn-outline-dark" href="{{ route('music.show', $next->id)}}"rel="next" role="button"><i class="fa-solid fa-arrow-right fa-lg"></i></a>
                            @endif --}}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="overlay" class="overlay"></div>
    <div id="lyrics-popup" class="popup">
        <div class="popup-content">
            <span class="close-btn">&times;</span>
            <div class="lyrics-item">
                <h4 id="popup-title" style="padding-right: 10px;"></h4> <!-- タグを修正 -->
                <hr>
                <p class="text" id="popup-lyrics"></p> <!-- テキストを表示 -->
            </div>
        </div>
    </div>
@endsection

@section('page-script')
<script src="{{ asset('/js/music.js?time=' . time()) }}"></script>
@endsection