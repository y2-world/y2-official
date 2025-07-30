@extends('layouts.app')
@section('title', 'Yuki Official - ' . $tours->title)
@section('content')
    <br>
    <div class="container">
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
            $colClass = $totalOlCount <= 2 ? 'col-md-8' : 'col-md-10';
            $flexDirectionClass = $totalOlCount <= 1 ? 'flex-start' : 'space-around';
        @endphp
        <div class="row justify-content-center">
            <div class="{{ $colClass }}">
                <div class="setlist_title">{{ $tours->title }}</div>
                <div class="setlist_info">
                    @if (isset($tours->date1) && isset($tours->date2))
                        {{ date('Y.m.d', strtotime($tours->date1)) }} - {{ date('Y.m.d', strtotime($tours->date2)) }}
                    @elseif(isset($tours->date1) && !isset($tours->date2))
                        {{ date('Y.m.d', strtotime($tours->date1)) }}
                    @endif
                    <br>
                    {{ $tours->venue }}
                </div>
                <div class="setlist">
                    @if ($tourSetlists->count())
                        <hr>
                        <div class="setlist-row justify-content-{{ $flexDirectionClass }}">
                            @foreach ($tourSetlists as $setlistModel)
                                @php
                                    $setlist = is_array($setlistModel->setlist) ? $setlistModel->setlist : [];
                                    $encore = is_array($setlistModel->encore) ? $setlistModel->encore : [];

                                    if (!function_exists('normalizeTitleWithAnnotation')) {
                                        function normalizeTitleWithAnnotation($title)
                                        {
                                            preg_match('/^(.*?)\s*(\[[^\]]+\])?$/u', $title, $matches);
                                            return [
                                                'main' => trim($matches[1] ?? $title),
                                                'annotation' => $matches[2] ?? '', // [9.25] など
                                            ];
                                        }
                                    }
                                @endphp

                                @if (count($setlist) || count($encore))
                                    <ol class="live-column">
                                        @if (!empty(trim($setlistModel->subtitle ?? '')))
                                            <h5>{{ $setlistModel->subtitle }}</h5>
                                        @endif

                                        @foreach ([$setlist, $encore] as $section)
                                            @if ($loop->index === 1 && count($encore))
                                                <hr>
                                            @endif

                                            @foreach ($section as $data)
                                                @php
                                                    $isDaily = !empty($data['is_daily']);
                                                    $isNumericSong = is_numeric($data['song']);
                                                    $title = '';
                                                    $link = null;
                                                    $annotation = '';

                                                    if ($isNumericSong) {
                                                        $songModel = $songs->find($data['song']);
                                                        $title = $songModel->title ?? 'Unknown Song';
                                                        $link = $songModel
                                                            ? url('/database/songs', $data['song'])
                                                            : null;
                                                    } else {
                                                        $original = $data['song'];
                                                        $normalized = normalizeTitleWithAnnotation($original);
                                                        $title = $normalized['main'];
                                                        $annotation = $normalized['annotation'];
                                                        $matchedSong = $songs->firstWhere('title', $title);
                                                        $link = $matchedSong
                                                            ? url('/database/songs', $matchedSong->id)
                                                            : null;
                                                    }
                                                @endphp

                                                @if ($isDaily)
                                                    -
                                                    @if ($link)
                                                        <a href="{{ $link }}">{{ $title }}</a>
                                                        {{ $annotation }}<br>
                                                    @else
                                                        {{ $title }} {{ $annotation }}<br>
                                                    @endif
                                                @else
                                                    <li>
                                                        @if ($link)
                                                            <a href="{{ $link }}">{{ $title }}</a> {{ $annotation }}
                                                        @else
                                                            {{ $title }} {{ $annotation }}
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
                <div class="show_button text-center">
                    <!-- Buttons for navigation -->
                    @if (isset($previous))
                        <a class="btn btn-outline-dark" href="{{ route('live.show', $previous->id) }}" rel="prev"
                            role="button">
                            <i class="fa-solid fa-arrow-left fa-lg"></i></a>
                    @endif
                    @if (isset($next))
                        <a class="btn btn-outline-dark" href="{{ route('live.show', $next->id) }}"rel="next"
                            role="button"><i class="fa-solid fa-arrow-right fa-lg"></i></a>
                    @endif
                </div>
            </div>
        </div>
    </div>
    </div>
@endsection
