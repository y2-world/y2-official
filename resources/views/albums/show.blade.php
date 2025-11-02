@extends('layouts.app')
@section('title', 'Yuki Official - ' . $albums->title)
@section('content')
    <div class="database-hero database-year-hero">
        <div class="container">
            <p class="database-subtitle" style="margin-bottom: 10px;">
                @if($albums->best == 0)
                # {{$albums->album_id}}
                @else
                Best Album
                @endif
            </p>
            <h1 class="database-title" style="margin-bottom: 20px;">{{$albums->title}}</h1>
            <p class="database-subtitle" style="margin-bottom: 0;">Release: {{ date('Y.m.d', strtotime($albums->date)) }}</p>
        </div>
    </div>

    <div class="container-lg database-year-content">
        @if(isset($albums->tracklist))
        <h3 style="margin-top: 0; margin-bottom: 15px;">Tracklist</h3>
        <div style="background: white; border-radius: 15px; overflow: hidden; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08); padding: 30px;">
            <ol style="margin: 0; padding-left: 25px; font-size: 15px; line-height: 2;">
              @foreach ($albums->tracklist as $data)
                @if(isset($data['disc']))
                  @if($data['disc'] !== 'END')
                    @if(isset($data['exception']))
                      <div style="font-weight: 600; color: #2d3748; margin-top: 15px; margin-bottom: 5px;">{{ $data['disc'] }}</div>
                      <li><a href="{{ url('/database/songs', $data['id'])}}" style="color: #667eea; text-decoration: none; font-weight: 500; transition: color 0.3s ease;">{{ $data['exception'] }}</a></li>
                    @else
                      <div style="font-weight: 600; color: #2d3748; margin-top: 15px; margin-bottom: 5px;">{{ $data['disc'] }}</div>
                      <li><a href="{{ url('/database/songs', $data['id']) }}" style="color: #667eea; text-decoration: none; font-weight: 500; transition: color 0.3s ease;">{{ $songs[$data['id'] - 1]['title'] }}</a></li>
                    @endif
                  @elseif($data['disc'] == 'END')
                    @if(isset($data['exception']))
                    <li><a href="{{ url('/database/songs', $data['id'])}}" style="color: #667eea; text-decoration: none; font-weight: 500; transition: color 0.3s ease;">{{ $data['exception'] }}</a></li>
                    @else
                    <li><a href="{{ url('/database/songs', $data['id']) }}" style="color: #667eea; text-decoration: none; font-weight: 500; transition: color 0.3s ease;">{{ $songs[$data['id'] - 1]['title'] }}</a></li>
                    </ol>
                    <ol style="margin: 0; padding-left: 25px; font-size: 15px; line-height: 2;">
                    @endif
                  @endif
                @else
                  @if(!isset($data['id']))
                  <li style="color: #718096;">{{ $data['exception'] }}</li>
                  @elseif(isset($data['exception']) && isset($data['id']))
                  <li><a href="{{ url('/database/songs', $data['id'])}}" style="color: #667eea; text-decoration: none; font-weight: 500; transition: color 0.3s ease;">{{ $data['exception'] }}</a></li>
                  @else
                  <li><a href="{{ url('/database/songs', $data['id']) }}" style="color: #667eea; text-decoration: none; font-weight: 500; transition: color 0.3s ease;">{{ $songs[$data['id'] - 1]['title'] }}</a></li>
                  @endif
                @endif
              @endforeach
            </ol>
        </div>
        @endif

        {{-- 前後リンク --}}
        <div style="display: flex; justify-content: space-between; margin-top: 40px; padding-bottom: 40px;">
            @if(isset($previous))
                <a href="{{ route('albums.show', $previous->id)}}" rel="prev"
                   style="display: inline-flex; align-items: center; padding: 12px 24px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 25px; text-decoration: none; font-weight: 500; transition: all 0.3s ease;">
                    <i class="fa-solid fa-arrow-left" style="margin-right: 8px;"></i>
                    Previous
                </a>
            @else
                <div></div>
            @endif
            @if(isset($next))
                <a href="{{ route('albums.show', $next->id)}}" rel="next"
                   style="display: inline-flex; align-items: center; padding: 12px 24px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 25px; text-decoration: none; font-weight: 500; transition: all 0.3s ease;">
                    Next
                    <i class="fa-solid fa-arrow-right" style="margin-left: 8px;"></i>
                </a>
            @endif
        </div>
    </div>
@endsection