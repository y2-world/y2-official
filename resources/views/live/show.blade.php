@extends('layouts.app')
@section('title', 'Yuki Official - ' . $tours->title)
@section('content')
    <br>
    <div class="container">
        @php
            // 全てのセットリストの <ol> 要素の数をカウントする関数
            function getTotalOlCount($setlists)
            {
                $totalOlCount = 0;
                foreach ($setlists as $setlist) {
                    if (isset($setlist)) {
                        foreach ($setlist as $data) {
                            if (isset($data['#']) && $data['#'] === 'hr') {
                                $totalOlCount++;
                            }
                        }
                        // Each non-empty setlist starts with an <ol>
                        $totalOlCount++;
                    }
                }
                return $totalOlCount;
            }

            // setlist1, setlist2, setlist3のすべての <ol> 要素の数を取得
            $totalOlCount = getTotalOlCount([$tours->setlist1, $tours->setlist2, $tours->setlist3]);

            // クラスを設定
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
                    @if (isset($tours->setlist1))
                        <hr>
                        <div class="setlist-row justify-content-{{ $flexDirectionClass }}">
                            @php
                                $encore_started = false;
                                $isFirst = true;
                            @endphp

                            @foreach ($tours->setlist1 as $data)
                                @php
                                    $hasId = isset($data['id']);
                                    $hasException = isset($data['exception']);
                                    $isDaily = !empty($data['is_daily']);
                                    $isEncore = !empty($data['is_encore']);
                                    $isDate = isset($data['date']);
                                @endphp

                                {{-- アンコール最初の位置にだけ <hr> --}}
                                @if ($isEncore && !$encore_started && !$isFirst)
                                    <hr width="95%">
                                    @php $encore_started = true; @endphp
                                @elseif ($isEncore && !$encore_started)
                                    @php $encore_started = true; @endphp
                                @endif

                                {{-- 日付がある場合は <ol> を閉じて開き直す --}}
                                @if ($isDate)
                                    @unless ($isFirst)
                                        </ol>
                                    @endunless
                                    <ol class="live-column">
                                        <h5>{{ $data['date'] }}</h5>
                                        {{-- ID + 例外あり --}}
                                        @if ($hasId && $hasException)
                                            @if ($isDaily)
                                                - <a
                                                    href="{{ url('/database/songs', $data['id']) }}">{{ $data['exception'] }}</a><br>
                                            @else
                                                <li><a
                                                        href="{{ url('/database/songs', $data['id']) }}">{{ $data['exception'] }}</a>
                                                </li>
                                            @endif

                                            {{-- IDのみ --}}
                                        @elseif ($hasId)
                                            @if ($isDaily)
                                                <li><a
                                                        href="{{ url('/database/songs', $data['id']) }}">{{ $songs[$data['id'] - 1]['title'] ?? '不明' }}</a>
                                                </li>
                                            @else
                                                <li><a
                                                        href="{{ url('/database/songs', $data['id']) }}">{{ $songs[$data['id'] - 1]['title'] ?? '不明' }}</a>
                                                </li>
                                            @endif

                                            {{-- 例外のみ --}}
                                        @elseif ($hasException)
                                            @if ($isDaily)
                                                - {{ $data['exception'] }}<br>
                                            @else
                                                <li>{{ $data['exception'] }}</li>
                                            @endif
                                        @endif
                                        @php
                                            $isFirst = false;
                                            $encore_started = false; // ←★ここでリセット
                                        @endphp
                                        @continue
                                @endif

                                {{-- 最初だけ <ol> を開く --}}
                                @if ($isFirst)
                                    <ol class="live-column">
                                        @php $isFirst = false; @endphp
                                @endif

                                {{-- ID + 例外あり --}}
                                @if ($hasId && $hasException)
                                    @if ($isDaily)
                                        - <a
                                            href="{{ url('/database/songs', $data['id']) }}">{{ $data['exception'] }}</a><br>
                                    @else
                                        <li><a
                                                href="{{ url('/database/songs', $data['id']) }}">{{ $data['exception'] }}</a>
                                        </li>
                                    @endif

                                    {{-- IDのみ --}}
                                @elseif ($hasId)
                                    @if ($isDaily)
                                        <li><a
                                                href="{{ url('/database/songs', $data['id']) }}">{{ $songs[$data['id'] - 1]['title'] ?? '不明' }}</a>
                                        </li>
                                    @else
                                        <li><a
                                                href="{{ url('/database/songs', $data['id']) }}">{{ $songs[$data['id'] - 1]['title'] ?? '不明' }}</a>
                                        </li>
                                    @endif

                                    {{-- 例外のみ --}}
                                @elseif ($hasException)
                                    @if ($isDaily)
                                        - {{ $data['exception'] }}<br>
                                    @else
                                        <li>{{ $data['exception'] }}</li>
                                    @endif
                                @endif
                            @endforeach

                            {{-- 最後に開いている <ol> を閉じる --}}
                            @if (!$isFirst)
                                </ol>
                            @endif
                    @endif
                    @if (isset($tours->setlist2))
                            @php
                                $encore_started = false;
                                $isFirst = true;
                            @endphp

                            @foreach ($tours->setlist2 as $data)
                                @php
                                    $hasId = isset($data['id']);
                                    $hasException = isset($data['exception']);
                                    $isDaily = !empty($data['is_daily']);
                                    $isEncore = !empty($data['is_encore']);
                                    $isDate = isset($data['date']);
                                @endphp

                                {{-- アンコール最初の位置にだけ <hr> --}}
                                @if ($isEncore && !$encore_started && !$isFirst)
                                    <hr width="95%">
                                    @php $encore_started = true; @endphp
                                @elseif ($isEncore && !$encore_started)
                                    @php $encore_started = true; @endphp
                                @endif

                                {{-- 日付がある場合は <ol> を閉じて開き直す --}}
                                @if ($isDate)
                                    @unless ($isFirst)
                                        </ol>
                                    @endunless
                                    <ol class="live-column">
                                        <h5>{{ $data['date'] }}</h5>
                                        {{-- ID + 例外あり --}}
                                        @if ($hasId && $hasException)
                                            @if ($isDaily)
                                                - <a
                                                    href="{{ url('/database/songs', $data['id']) }}">{{ $data['exception'] }}</a><br>
                                            @else
                                                <li><a
                                                        href="{{ url('/database/songs', $data['id']) }}">{{ $data['exception'] }}</a>
                                                </li>
                                            @endif

                                            {{-- IDのみ --}}
                                        @elseif ($hasId)
                                            @if ($isDaily)
                                                <li><a
                                                        href="{{ url('/database/songs', $data['id']) }}">{{ $songs[$data['id'] - 1]['title'] ?? '不明' }}</a>
                                                </li>
                                            @else
                                                <li><a
                                                        href="{{ url('/database/songs', $data['id']) }}">{{ $songs[$data['id'] - 1]['title'] ?? '不明' }}</a>
                                                </li>
                                            @endif

                                            {{-- 例外のみ --}}
                                        @elseif ($hasException)
                                            @if ($isDaily)
                                                - {{ $data['exception'] }}<br>
                                            @else
                                                <li>{{ $data['exception'] }}</li>
                                            @endif
                                        @endif
                                        @php
                                            $isFirst = false;
                                            $encore_started = false; // ←★ここでリセット
                                        @endphp
                                        @continue
                                @endif

                                {{-- 最初だけ <ol> を開く --}}
                                @if ($isFirst)
                                    <ol class="live-column">
                                        @php $isFirst = false; @endphp
                                @endif

                                {{-- ID + 例外あり --}}
                                @if ($hasId && $hasException)
                                    @if ($isDaily)
                                        - <a
                                            href="{{ url('/database/songs', $data['id']) }}">{{ $data['exception'] }}</a><br>
                                    @else
                                        <li><a
                                                href="{{ url('/database/songs', $data['id']) }}">{{ $data['exception'] }}</a>
                                        </li>
                                    @endif

                                    {{-- IDのみ --}}
                                @elseif ($hasId)
                                    @if ($isDaily)
                                        <li><a
                                                href="{{ url('/database/songs', $data['id']) }}">{{ $songs[$data['id'] - 1]['title'] ?? '不明' }}</a>
                                        </li>
                                    @else
                                        <li><a
                                                href="{{ url('/database/songs', $data['id']) }}">{{ $songs[$data['id'] - 1]['title'] ?? '不明' }}</a>
                                        </li>
                                    @endif

                                    {{-- 例外のみ --}}
                                @elseif ($hasException)
                                    @if ($isDaily)
                                        - {{ $data['exception'] }}<br>
                                    @else
                                        <li>{{ $data['exception'] }}</li>
                                    @endif
                                @endif
                            @endforeach

                            {{-- 最後に開いている <ol> を閉じる --}}
                            @if (!$isFirst)
                                </ol>
                            @endif
                    @endif
                    @if (isset($tours->setlist3))
                            @php
                                $encore_started = false;
                                $isFirst = true;
                            @endphp

                            @foreach ($tours->setlist3 as $data)
                                @php
                                    $hasId = isset($data['id']);
                                    $hasException = isset($data['exception']);
                                    $isDaily = !empty($data['is_daily']);
                                    $isEncore = !empty($data['is_encore']);
                                    $isDate = isset($data['date']);
                                @endphp

                                {{-- アンコール最初の位置にだけ <hr> --}}
                                @if ($isEncore && !$encore_started && !$isFirst)
                                    <hr width="95%">
                                    @php $encore_started = true; @endphp
                                @elseif ($isEncore && !$encore_started)
                                    @php $encore_started = true; @endphp
                                @endif

                                {{-- 日付がある場合は <ol> を閉じて開き直す --}}
                                @if ($isDate)
                                    @unless ($isFirst)
                                        </ol>
                                    @endunless
                                    <ol class="live-column">
                                        <h5>{{ $data['date'] }}</h5>
                                        {{-- ID + 例外あり --}}
                                        @if ($hasId && $hasException)
                                            @if ($isDaily)
                                                - <a
                                                    href="{{ url('/database/songs', $data['id']) }}">{{ $data['exception'] }}</a><br>
                                            @else
                                                <li><a
                                                        href="{{ url('/database/songs', $data['id']) }}">{{ $data['exception'] }}</a>
                                                </li>
                                            @endif

                                            {{-- IDのみ --}}
                                        @elseif ($hasId)
                                            @if ($isDaily)
                                                <li><a
                                                        href="{{ url('/database/songs', $data['id']) }}">{{ $songs[$data['id'] - 1]['title'] ?? '不明' }}</a>
                                                </li>
                                            @else
                                                <li><a
                                                        href="{{ url('/database/songs', $data['id']) }}">{{ $songs[$data['id'] - 1]['title'] ?? '不明' }}</a>
                                                </li>
                                            @endif

                                            {{-- 例外のみ --}}
                                        @elseif ($hasException)
                                            @if ($isDaily)
                                                - {{ $data['exception'] }}<br>
                                            @else
                                                <li>{{ $data['exception'] }}</li>
                                            @endif
                                        @endif
                                        @php
                                            $isFirst = false;
                                            $encore_started = false; // ←★ここでリセット
                                        @endphp
                                        @continue
                                @endif

                                {{-- 最初だけ <ol> を開く --}}
                                @if ($isFirst)
                                    <ol class="live-column">
                                        @php $isFirst = false; @endphp
                                @endif

                                {{-- ID + 例外あり --}}
                                @if ($hasId && $hasException)
                                    @if ($isDaily)
                                        - <a
                                            href="{{ url('/database/songs', $data['id']) }}">{{ $data['exception'] }}</a><br>
                                    @else
                                        <li><a
                                                href="{{ url('/database/songs', $data['id']) }}">{{ $data['exception'] }}</a>
                                        </li>
                                    @endif

                                    {{-- IDのみ --}}
                                @elseif ($hasId)
                                    @if ($isDaily)
                                        <li><a
                                                href="{{ url('/database/songs', $data['id']) }}">{{ $songs[$data['id'] - 1]['title'] ?? '不明' }}</a>
                                        </li>
                                    @else
                                        <li><a
                                                href="{{ url('/database/songs', $data['id']) }}">{{ $songs[$data['id'] - 1]['title'] ?? '不明' }}</a>
                                        </li>
                                    @endif

                                    {{-- 例外のみ --}}
                                @elseif ($hasException)
                                    @if ($isDaily)
                                        - {{ $data['exception'] }}<br>
                                    @else
                                        <li>{{ $data['exception'] }}</li>
                                    @endif
                                @endif
                            @endforeach

                            {{-- 最後に開いている <ol> を閉じる --}}
                            @if (!$isFirst)
                                </ol>
                            @endif
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
@endsection
