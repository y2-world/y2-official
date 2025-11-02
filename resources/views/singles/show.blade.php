@extends('layouts.app')
@section('title', 'Yuki Official - ' . $singles->title)
@section('content')
    <div class="database-hero database-year-hero">
        <div class="container">
            <p class="database-subtitle" style="margin-bottom: 10px;">
                @if($singles->download == 0)
                # {{$singles->single_id}}
                @else
                Download Single
                @endif
            </p>
            <h1 class="database-title" style="margin-bottom: 20px;">{{$singles->title}}</h1>
            <p class="database-subtitle" style="margin-bottom: 0;">Release: {{ date('Y.m.d', strtotime($singles->date)) }}</p>
        </div>
    </div>

    <div class="container-lg database-year-content">
        @if(isset($singles->tracklist))
        <h3 style="margin-top: 0; margin-bottom: 15px;">Tracklist</h3>
        <div style="background: white; border-radius: 15px; overflow: hidden; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08); padding: 30px;">
            <ol style="margin: 0; padding-left: 25px; font-size: 15px; line-height: 2;">
              @foreach ($singles->tracklist as $data)
                @if(isset($data['id']))
                  @if(isset($data['exception']))
                  <li><a href="{{ url('/database/songs', $data['id']) }}" style="color: #667eea; text-decoration: none; font-weight: 500; transition: color 0.3s ease;">{{ $data['exception']}}</a></li>
                  @else
                  <li><a href="{{ url('/database/songs', $data['id']) }}" style="color: #667eea; text-decoration: none; font-weight: 500; transition: color 0.3s ease;">{{ $songs[$data['id'] - 1]['title'] }}</a></li>
                  @endif
                @else
                  <li style="color: #718096;">{{ $data['exception'] }}</li>
                @endif
              @endforeach
            </ol>
        </div>
        @endif

        {{-- 前後リンク --}}
        <div style="display: flex; justify-content: space-between; margin-top: 40px; padding-bottom: 40px;">
            @if(isset($previous))
                <a href="{{ route('singles.show', $previous->id)}}" rel="prev"
                   style="display: inline-flex; align-items: center; padding: 12px 24px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 25px; text-decoration: none; font-weight: 500; transition: all 0.3s ease;">
                    <i class="fa-solid fa-arrow-left" style="margin-right: 8px;"></i>
                    Previous
                </a>
            @else
                <div></div>
            @endif
            @if(isset($next))
                <a href="{{ route('singles.show', $next->id)}}" rel="next"
                   style="display: inline-flex; align-items: center; padding: 12px 24px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 25px; text-decoration: none; font-weight: 500; transition: all 0.3s ease;">
                    Next
                    <i class="fa-solid fa-arrow-right" style="margin-left: 8px;"></i>
                </a>
            @endif
        </div>
    </div>
@endsection