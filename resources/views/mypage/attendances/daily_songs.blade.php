@extends('layouts.app')
@section('title', 'Yuki Official - 日替わり曲を選択')

@section('content')
    <div class="database-hero database-hero--detail manage-page">
        <div class="container">
            <p class="database-subtitle" style="text-align: center; margin-bottom: 0;">セットリスト登録</p>
            <h1 class="database-title" style="text-align: center;">日替わり曲を選択</h1>
            <p class="database-subtitle" style="text-align: center;">その日に実際に演奏された曲を選んでください</p>
        </div>
    </div>

    <div class="container database-content">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <form method="POST" action="{{ route('mypage.attendances.daily_songs.confirm', $setlistId) }}">
                    @csrf
                    @foreach ($autoSelected ?? [] as $songUuid)
                        <input type="hidden" name="auto_selected_daily_songs[]" value="{{ $songUuid }}">
                    @endforeach
                    @foreach ($clusters as $clusterIndex => $cluster)
                        @php
                            $clusterLabel = $cluster['section'] === 'encore'
                                ? 'EN' . ($cluster['after_number'] + 1)
                                : $cluster['after_number'] + 1;
                        @endphp
                        <div class="daily-song-cluster">
                            <div class="daily-song-cluster-label">{{ $clusterLabel }}</div>
                            @foreach ($cluster['items'] as $itemIndex => $item)
                                @php
                                    $isNumericSong = is_numeric($item['song'] ?? null);
                                    $songModel = $isNumericSong ? ($songs[$item['song']] ?? null) : null;
                                    $title = !empty($item['alternative_title'])
                                        ? $item['alternative_title']
                                        : ($songModel->title ?? $item['song'] ?? 'Unknown Song');
                                    $radioId = 'daily-' . $clusterIndex . '-' . $itemIndex;
                                @endphp
                                <label for="{{ $radioId }}" class="daily-song-option">
                                    <input type="radio" id="{{ $radioId }}" name="selected_daily_songs[{{ $clusterIndex }}]" value="{{ $item['_uuid'] }}" required>
                                    <span>{{ $title }}</span>
                                </label>
                            @endforeach
                        </div>
                    @endforeach

                    <div class="d-flex gap-2 mt-3">
                        <button type="submit" class="btn btn-outline-dark w-100">次へ</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <style>
    .daily-song-cluster {
        margin-bottom: 20px;
    }
    .daily-song-cluster-label {
        font-weight: 400;
        color: #999;
        font-size: 0.85rem;
        margin-bottom: 6px;
    }
    .daily-song-option {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 10px 12px;
        border: 1px solid #eee;
        border-radius: 10px;
        background: white;
        margin-bottom: 8px;
        cursor: pointer;
    }
    .daily-song-option input[type="radio"] {
        flex-shrink: 0;
    }
    </style>
@endsection
