@extends('layouts.app')
@section('title', 'Yuki Official - ' . $singles->title)
@section('content')
    <div class="database-hero database-hero--detail">
        <div class="container" style="position: relative;">
            @include('database._breadcrumb', ['breadcrumbs' => [
            ['label' => 'Database', 'url' => '/database'],
            ['label' => $artist->name, 'url' => route('database.artist', $artist->id)],
            ['label' => 'Singles', 'url' => route('database.singles', $artist->id)],
            ['label' => $singles->title],
        ]])
            <p class="database-subtitle" style="">
                @if($singles->download == 0 && $singles->single_id)
                    {{ ordinal($singles->single_id) }} Single
                @elseif($singles->download)
                    Digital Exclusive Single
                @endif
            </p>
            <h1 class="database-title" style="">{{$singles->title}}</h1>
            <p class="database-subtitle" style="">Release: {{ date('Y.m.d', strtotime($singles->date)) }}</p>

            {{-- 検索フォーム（SP表示） --}}
            <div class="sp" id="spSearchFormSingle" style="margin-top: 10px; display: none;">
                @livewire('database-song-search', ['artistId' => $artist->id])
                <div style="text-align: center; margin-top: 15px;">
                    <button type="button" onclick="document.getElementById('spSearchFormSingle').style.display='none';" style="background: rgba(255, 255, 255, 0.2); border: 1px solid rgba(255, 255, 255, 0.3); color: white; padding: 8px; border-radius: 50%; cursor: pointer; width: 36px; height: 36px; display: inline-flex; align-items: center; justify-content: center;">
                        <i class="fa-solid fa-xmark" style="font-size: 16px;"></i>
                    </button>
                </div>
            </div>

            {{-- 検索フォーム（PC表示のみ） --}}
            <div class="database-search pc song-search-top-right">
                @livewire('database-song-search', ['artistId' => $artist->id])
            </div>
        </div>
    </div>

    <div class="container-lg database-year-content">
        @if(isset($singles->tracklist))
        <div style="background: white; border-radius: 15px; overflow: hidden; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08); padding: 30px;">
            <ol style="margin: 0; padding-left: 25px; font-size: 15px; line-height: 2;">
              @foreach ($singles->tracklist as $data)
                @if(isset($data['id']))
                  @if(isset($data['exception']))
                  <li><a href="{{ url('/database/songs', $data['id']) }}" style="color: #667eea; text-decoration: none; font-weight: 500; transition: color 0.3s ease;">{{ $data['exception']}}</a></li>
                  @else
                  <li><a href="{{ url('/database/songs', $data['id']) }}" style="color: #667eea; text-decoration: none; font-weight: 500; transition: color 0.3s ease;">{{ $songs[$data['id']]->title ?? '' }}</a></li>
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