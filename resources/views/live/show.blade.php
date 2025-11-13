@extends('layouts.app')
@section('title', 'Yuki Official - ' . $tours->title)
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
        $colClass = $totalOlCount <= 2 ? 'col-xl-9' : 'col-xl-10';
        $flexDirectionClass = $totalOlCount <= 1 ? 'flex-start' : 'space-around';
    @endphp

    <div class="database-hero database-year-hero">
        <div class="container">
            <h1 class="database-title" style="margin-bottom: 15px;">{{ $tours->title }}</h1>
            <p class="database-subtitle" style="margin-bottom: 0;">
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

    <div class="container database-year-content">
        <div class="row justify-content-center">
            <div class="{{ $colClass }}">
                <div class="setlist">
                    @if ($tourSetlists->count())
                        <div class="setlist-row justify-content-{{ $flexDirectionClass }}">
                            @foreach ($tourSetlists as $setlistModel)
                                @php
                                    $setlist = is_array($setlistModel->setlist) ? $setlistModel->setlist : [];
                                    $encore = is_array($setlistModel->encore) ? $setlistModel->encore : [];
                                @endphp

                                @if (count($setlist) || count($encore))
                                    <ol class="live-column">
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
                                                        <a href="{{ $link }}">{{ $title }}</a>
                                                    @else
                                                        {{ $title }}
                                                    @endif
                                                    @if(!empty($featuring))
                                                        / {{ $featuring }}
                                                    @endif
                                                    @if(!empty($dailyNote))
                                                        ({{ $dailyNote }})
                                                    @endif
                                                    <br>
                                                @else
                                                    <li>
                                                        @if ($link)
                                                            <a href="{{ $link }}">{{ $title }}</a>
                                                        @else
                                                            {{ $title }}
                                                        @endif
                                                        @if(!empty($featuring))
                                                            / {{ $featuring }}
                                                        @endif
                                                        @if(!empty($dailyNote))
                                                            ({{ $dailyNote }})
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
                <div class="schedule-text">
                    <!-- Additional content -->
                    @if (!is_null($tours->schedule))
                        <hr>
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
@endsection
