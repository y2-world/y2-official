@extends('layouts.app')
@section('title', 'Yuki Official - ' . $artist->name . ' Live')

@section('content')
    <div class="database-hero database-hero--nav">
        <div class="container" style="position: relative;">
            @include('database._breadcrumb', ['breadcrumbs' => [
                ['label' => 'My Page', 'url' => route('mypage.index')],
                ['label' => $artist->name],
            ]])
            <h1 class="database-title" style="text-align: center;">Live</h1>
            <p class="database-subtitle" style="text-align: center;">{{ $artist->name }} — すべてのツアー・ライブ情報</p>
        </div>
    </div>

    <div class="container database-year-content">
        {{-- 公式のライブ一覧と同じく、開催日の列は期間が1行で入る幅にする --}}
        <table class="table table-striped fixed-cols">
            <thead>
                <tr>
                    <th class="mobile col-no">#</th>
                    <th class="mobile col-date-range">開催日</th>
                    <th class="mobile">タイトル</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($tours as $index => $tour)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>
                            @if ($tour->date1 && $tour->date2)
                                {{ date('Y.m.d', strtotime($tour->date1)) }} - {{ date('Y.m.d', strtotime($tour->date2)) }}
                            @elseif ($tour->date1)
                                {{ date('Y.m.d', strtotime($tour->date1)) }}
                            @endif
                        </td>
                        <td><a href="{{ route('mypage.user_concerts.show', $tour->id) }}">{{ $tour->title }}</a></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="text-center">ライブ情報がありません</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
