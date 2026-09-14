@extends('layouts.app')
@section('title', 'Yuki Official - ' . $song->title)

@section('content')
    <div class="database-hero database-hero--detail">
        <div class="container" style="position: relative;">
            @include('database._breadcrumb', ['breadcrumbs' => [
                ['label' => 'My Page', 'url' => route('mypage.index')],
                ['label' => $song->artist->name, 'url' => route('mypage.user_artists.live', $song->user_artist_id)],
                ['label' => $song->title],
            ]])
            <p class="database-subtitle">
                <a href="{{ route('mypage.user_artists.live', $song->user_artist_id) }}">{{ $song->artist->name }}</a>
            </p>
            <h1 class="database-title">{{ $song->title }}</h1>
        </div>
    </div>

    <div class="container-lg database-year-content">
        @if (!$tours->isEmpty())
            <h3 style="margin-top: 0; margin-bottom: 15px;">Live Performances</h3>
            <table class="table table-striped count">
                <thead>
                    <tr>
                        <th class="mobile">#</th>
                        <th class="mobile">開催日</th>
                        <th class="mobile">タイトル</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($tours as $tour)
                        <tr>
                            <td></td>
                            @if ($tour->date1 && $tour->date2)
                                <td class="td_date">{{ date('Y.m.d', strtotime($tour->date1)) }} - {{ date('Y.m.d', strtotime($tour->date2)) }}</td>
                            @elseif ($tour->date1)
                                <td class="td_date">{{ date('Y.m.d', strtotime($tour->date1)) }}</td>
                            @else
                                <td class="td_date"></td>
                            @endif
                            <td class="td_title"><a href="{{ route('mypage.user_concerts.show', $tour->id) }}">{{ $tour->title }}</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        <div style="display: flex; justify-content: space-between; margin-top: 40px; padding-bottom: 40px;">
            @if ($previous)
                <a href="{{ route('mypage.user_songs.show', $previous->id) }}" rel="prev"
                   style="display: inline-flex; align-items: center; padding: 12px 24px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 25px; text-decoration: none; font-weight: 500; transition: all 0.3s ease;">
                    <i class="fa-solid fa-arrow-left" style="margin-right: 8px;"></i>
                    Previous
                </a>
            @else
                <div></div>
            @endif
            @if ($next)
                <a href="{{ route('mypage.user_songs.show', $next->id) }}" rel="next"
                   style="display: inline-flex; align-items: center; padding: 12px 24px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 25px; text-decoration: none; font-weight: 500; transition: all 0.3s ease;">
                    Next
                    <i class="fa-solid fa-arrow-right" style="margin-left: 8px;"></i>
                </a>
            @endif
        </div>
    </div>
@endsection
