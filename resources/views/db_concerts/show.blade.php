@extends('layouts.app')
@section('title', 'Yuki Official - ' . $tours->title)

@section('og_title', $tours->title . ' - Yuki Official')
@section('og_description', 'Tour: ' . $tours->title . ($tours->date1 ? ' (' . date('Y', strtotime($tours->date1)) . ')' : ''))
@section('og_type', 'article')

@section('content')
    @php
        // 関数の重複定義を防ぐためにチェック
        if (!function_exists('getTotalOlCount')) {
            function getTotalOlCount($tourSetlists)
            {
                $totalOlCount = 0;

                foreach ($tourSetlists as $setlist) {
                    if (!empty($setlist)) {
                        foreach ($setlist as $data) {
                            $totalOlCount++;
                            $hasDate = true;
                        }
                    }
                }

                return $totalOlCount;
            }
        }

        // 各セットリストの <ol> 数を集計
        $totalOlCount = $tourSetlists
            ->filter(function ($model) {
                return is_array($model->setlist) && count($model->setlist) > 0;
            })
            ->count();

        // レイアウト用クラスを調整
        $colClass = $totalOlCount <= 2 ? 'col-xl-9' : 'col-xl-12';

        // 全パターンに共通する曲IDを計算
        $commonSongs = null;
        if ($totalOlCount >= 2) {
            foreach ($tourSetlists as $setlistModel) {
                $allSongs = array_merge(
                    is_array($setlistModel->setlist) ? $setlistModel->setlist : [],
                    is_array($setlistModel->encore) ? $setlistModel->encore : []
                );
                $songIds = array_map(fn($s) => $s['song'] ?? '', $allSongs);
                $songIds = array_filter($songIds, fn($s) => $s !== '');
                if ($commonSongs === null) {
                    $commonSongs = array_flip($songIds);
                } else {
                    $commonSongs = array_intersect_key($commonSongs, array_flip($songIds));
                }
            }
            $commonSongs = array_keys($commonSongs ?? []);
        }
    @endphp

    <div class="database-hero database-hero--detail">
        <div class="container" style="position: relative;">
            @include('database._breadcrumb', ['breadcrumbs' => [
            ['label' => 'Database', 'url' => '/database'],
            ['label' => $artist->name, 'url' => route('database.artist', $artist->id)],
            ['label' => 'Live', 'url' => route('database.live', $artist->id)],
            ['label' => $tours->title],
        ]])
            <h1 class="database-title" style="">{{ $tours->title }}</h1>
            <p class="database-subtitle" style="">
                @if (isset($tours->date1) && isset($tours->date2))
                    {{ date('Y.m.d', strtotime($tours->date1)) }} - {{ date('Y.m.d', strtotime($tours->date2)) }}
                @elseif(isset($tours->date1) && !isset($tours->date2))
                    {{ date('Y.m.d', strtotime($tours->date1)) }}
                @endif
                <br>
                {{ $tours->venue }}
            </p>
        </div>
    </div>

    <div class="{{ $totalOlCount >= 3 ? 'container-fluid' : 'container' }} database-year-content">
        <div class="row justify-content-center">
            <div class="{{ $colClass }}">
                <div class="setlist" style="width: 100%;">
                    @if ($tourSetlists->count())
                        <div class="setlist-row">
                            @foreach ($tourSetlists as $setlistModel)
                                @php
                                    $setlist = is_array($setlistModel->setlist) ? $setlistModel->setlist : [];
                                    $encore = is_array($setlistModel->encore) ? $setlistModel->encore : [];
                                    $totalItems = count($setlist) + count($encore);
                                @endphp

                                @if (count($setlist) || count($encore))
                                    <ol class="live-column {{ $totalItems >= 20 ? 'live-column-two-col' : '' }}">
                                        @if (!empty(trim($setlistModel->subtitle ?? '')))
                                            <h5>{{ $setlistModel->subtitle }}</h5>
                                        @endif

                                        @foreach ([$setlist, $encore] as $section)
                                            @if ($loop->index === 1 && count($encore))
                                                <div style="margin: 20px 0 0 0;">
                                                    <span style="color: #999; font-weight: 600; font-size: 0.9rem; letter-spacing: 2px;">ENCORE</span>
                                                </div>
                                            @endif

                                            @foreach ($section as $data)
                                                @php
                                                    $isDaily = isset($data['is_daily']) && $data['is_daily'];
                                                    $dailyNote = isset($data['daily_note']) ? $data['daily_note'] : '';
                                                    $featuring = isset($data['featuring']) ? $data['featuring'] : '';
                                                    $alternativeTitle = isset($data['alternative_title']) ? $data['alternative_title'] : '';
                                                    $isNumericSong = is_numeric($data['song'] ?? '');
                                                    $title = '';
                                                    $link = null;
                                                    $isUnique = $commonSongs !== null && !in_array($data['song'] ?? '', $commonSongs);

                                                    if ($isNumericSong) {
                                                        $songModel = $songs->find($data['song']);
                                                        // 別表記がある場合はそれを使用、なければ元のタイトル
                                                        $title = !empty($alternativeTitle) ? $alternativeTitle : ($songModel->title ?? 'Unknown Song');
                                                        $link = $songModel
                                                            ? url('/database/songs', $data['song'])
                                                            : null;
                                                    } else {
                                                        // 文字列の場合はそのまま表示（別表記があればそれを使用）
                                                        $title = !empty($alternativeTitle) ? $alternativeTitle : $data['song'];
                                                        $link = null;
                                                    }
                                                @endphp

                                                @if ($isDaily)
                                                    -
                                                    @if ($link)
                                                        <a href="{{ $link }}" @if($isUnique) style="font-weight:bold;" @endif>{{ $title }}</a>
                                                    @else
                                                        @if($isUnique)<strong>{{ $title }}</strong>@else{{ $title }}@endif
                                                    @endif
                                                    @if(!empty($featuring))
                                                        <span style="color:#999;font-size:0.75em;">{{ $featuring }}</span>
                                                    @endif
                                                    @if(!empty($dailyNote))
                                                        <span style="color:#999;font-size:0.75em;">{{ $dailyNote }}</span>
                                                    @endif
                                                    <br>
                                                @else
                                                    <li @if($isUnique) style="font-weight:bold;" @endif>
                                                        @if ($link)
                                                            <a href="{{ $link }}">{{ $title }}</a>
                                                        @else
                                                            {{ $title }}
                                                        @endif
                                                        @if(!empty($featuring))
                                                            <span style="color:#999;font-size:0.75em;">{{ $featuring }}</span>
                                                        @endif
                                                        @if(!empty($dailyNote))
                                                            <span style="color:#999;font-size:0.75em;">{{ $dailyNote }}</span>
                                                        @endif
                                                    </li>
                                                @endif
                                            @endforeach
                                        @endforeach
                                    </ol>
                                @endif
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    <div class="container database-year-content" style="padding-top: 0;">
        <div class="row justify-content-center">
            <div class="col-xl-9">
                <div class="schedule-text">
                    <!-- Additional content -->
                    @if (!is_null($tours->schedule))
                        @if ($tourSetlists->count())
                            <hr>
                        @endif
                        <h5>SCHEDULE</h5>
                        {!! nl2br(e($tours->schedule)) !!}
                    @endif
                    @if (!is_null($tours->text))
                        <hr>
                        {!! nl2br(e($tours->text)) !!}
                    @endif
                </div>
                {{-- 前後リンク --}}
                <div style="display: flex; justify-content: space-between; margin-top: 40px; padding-bottom: 40px;">
                    @if (isset($previous))
                        <a href="{{ route('live.show', $previous->id) }}" rel="prev"
                           style="display: inline-flex; align-items: center; padding: 12px 24px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 25px; text-decoration: none; font-weight: 500; transition: all 0.3s ease;">
                            <i class="fa-solid fa-arrow-left" style="margin-right: 8px;"></i>
                            Previous
                        </a>
                    @else
                        <div></div>
                    @endif
                    @if (isset($next))
                        <a href="{{ route('live.show', $next->id) }}" rel="next"
                           style="display: inline-flex; align-items: center; padding: 12px 24px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 25px; text-decoration: none; font-weight: 500; transition: all 0.3s ease;">
                            Next
                            <i class="fa-solid fa-arrow-right" style="margin-left: 8px;"></i>
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>



@section('page-script')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var row = document.querySelector('.setlist-row');
    if (row && row.scrollWidth <= row.clientWidth) {
        row.style.justifyContent = 'center';
    }
});
</script>
@endsection

@endsection
