@extends('layouts.app')
@section('content')
    <br>
    <div class="container">
        @php
             // 全てのセットリストの <ol> 要素の数をカウントする関数
                function getTotalOlCount($setlists) {
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
                        <div class="setlist-row" style="justify-content:{{ $flexDirectionClass }}">
                            <ol class="live-column">
                                @foreach ($tours->setlist1 as $data)
                                    @if (isset($data['id']))
                                        @if (isset($data['exception']))
                                            @if (!empty($data['#']) && $data['#'] == '-')
                                                {{ $data['#'] }} <a href="{{ url('/database/songs', $data['id']) }}">{{ $data['exception'] }}</a><br>
                                            @else
                                                <li><a href="{{ url('/database/songs', $data['id']) }}">{{ $data['exception'] }}</a></li>
                                            @endif
                                        @else
                                            @if (empty($data['#']) || !empty($data['#']) && $data['#'] != '-')
                                                <li><a href="{{ url('/database/songs', $data['id']) }}">{{ $songs[$data['id'] - 1]['title'] }}</a></li>
                                            @elseif(!empty($data['#']) && $data['#'] == '-')
                                                {{ $data['#'] }} <a href="{{ url('/database/songs', $data['id']) }}">{{ $songs[$data['id'] - 1]['title'] }}</a><br>
                                            @endif
                                        @endif
                                    @else
                                        @if (isset($data['exception']))
                                            @if (isset($data['id']))
                                                @if (!empty($data['#']) && $data['#'] == '-')
                                                    {{ $data['#'] }} <a href="{{ url('/database/songs', $data['id']) }}">{{ $data['exception'] }}</a><br>
                                                @else
                                                    <li><a href="{{ url('/database/songs', $data['id']) }}">{{ $data['exception'] }}</a></li>
                                                @endif
                                            @else
                                                @if (!empty($data['#']) && $data['#'] == '-')
                                                    {{ $data['#'] }} {{ $data['exception'] }}<br>
                                                @else
                                                    <li>{{ $data['exception'] }}</li>
                                                @endif
                                            @endif
                                        @else
                                            @if (!empty($data['#']) && $data['#'] == 'ENCORE')
                                                <hr width="95%">
                                            @elseif($data['#'] == 'hr')
                                                </ol>
                                                <ol class="live-column">
                                            @else
                                                <h5>{{ $data['#'] }}</h5>
                                            @endif
                                        @endif
                                    @endif
                                @endforeach
                            </ol>
                            @endif
                            @if (isset($tours->setlist2))
                            <ol class="live-column">
                                @foreach ($tours->setlist2 as $data)
                                    @if (isset($data['id']))
                                        @if (isset($data['exception']))
                                            @if (!empty($data['#']) && $data['#'] == '-')
                                                {{ $data['#'] }} <a href="{{ url('/database/songs', $data['id']) }}">{{ $data['exception'] }}</a><br>
                                            @else
                                                <li><a href="{{ url('/database/songs', $data['id']) }}">{{ $data['exception'] }}</a></li>
                                            @endif
                                        @else
                                            @if (empty($data['#']) || !empty($data['#']) && $data['#'] != '-')
                                                <li><a href="{{ url('/database/songs', $data['id']) }}">{{ $songs[$data['id'] - 1]['title'] }}</a></li>
                                            @elseif(!empty($data['#']) && $data['#'] == '-')
                                                {{ $data['#'] }} <a href="{{ url('/database/songs', $data['id']) }}">{{ $songs[$data['id'] - 1]['title'] }}</a><br>
                                            @endif
                                        @endif
                                    @else
                                        @if (isset($data['exception']))
                                            @if (isset($data['id']))
                                                @if (!empty($data['#']) && $data['#'] == '-')
                                                    {{ $data['#'] }} <a href="{{ url('/database/songs', $data['id']) }}">{{ $data['exception'] }}</a><br>
                                                @else
                                                    <li><a href="{{ url('/database/songs', $data['id']) }}">{{ $data['exception'] }}</a></li>
                                                @endif
                                            @else
                                                @if (!empty($data['#']) && $data['#'] == '-')
                                                    {{ $data['#'] }} {{ $data['exception'] }}<br>
                                                @else
                                                    <li>{{ $data['exception'] }}</li>
                                                @endif
                                            @endif
                                        @else
                                            @if (!empty($data['#']) && $data['#'] == 'ENCORE')
                                                <hr width="95%">
                                            @elseif($data['#'] == 'hr')
                                                </ol>
                                                <ol class="live-column">
                                            @else
                                                <h5>{{ $data['#'] }}</h5>
                                            @endif
                                        @endif
                                    @endif
                                @endforeach
                            </ol>
                            @endif
                            @if (isset($tours->setlist3))
                            <ol class="live-column">
                                @foreach ($tours->setlist3 as $data)
                                    @if (isset($data['id']))
                                        @if (isset($data['exception']))
                                            @if (!empty($data['#']) && $data['#'] == '-')
                                                {{ $data['#'] }} <a href="{{ url('/database/songs', $data['id']) }}">{{ $data['exception'] }}</a><br>
                                            @else
                                                <li><a href="{{ url('/database/songs', $data['id']) }}">{{ $data['exception'] }}</a></li>
                                            @endif
                                        @else
                                            @if (empty($data['#']) || !empty($data['#']) && $data['#'] != '-')
                                                <li><a href="{{ url('/database/songs', $data['id']) }}">{{ $songs[$data['id'] - 1]['title'] }}</a></li>
                                            @elseif(!empty($data['#']) && $data['#'] == '-')
                                                {{ $data['#'] }} <a href="{{ url('/database/songs', $data['id']) }}">{{ $songs[$data['id'] - 1]['title'] }}</a><br>
                                            @endif
                                        @endif
                                    @else
                                        @if (isset($data['exception']))
                                            @if (isset($data['id']))
                                                @if (!empty($data['#']) && $data['#'] == '-')
                                                    {{ $data['#'] }} <a href="{{ url('/database/songs', $data['id']) }}">{{ $data['exception'] }}</a><br>
                                                @else
                                                    <li><a href="{{ url('/database/songs', $data['id']) }}">{{ $data['exception'] }}</a></li>
                                                @endif
                                            @else
                                                @if (!empty($data['#']) && $data['#'] == '-')
                                                    {{ $data['#'] }} {{ $data['exception'] }}<br>
                                                @else
                                                    <li>{{ $data['exception'] }}</li>
                                                @endif
                                            @endif
                                        @else
                                            @if (!empty($data['#']) && $data['#'] == 'ENCORE')
                                                <hr width="95%">
                                            @elseif($data['#'] == 'hr')
                                                </ol>
                                                <ol class="live-column">
                                            @else
                                                <h5>{{ $data['#'] }}</h5>
                                            @endif
                                        @endif
                                    @endif
                                @endforeach
                            </ol>
                        </div>
                    @endif
                </div>
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
                <div class="show_button text-center">
                    <!-- Buttons for navigation -->
                    @if (isset($previous))
                        <a class="btn btn-outline-dark" href="{{ route('live.show', $previous->id) }}" rel="prev" role="button">
                            <</a>
                    @endif
                    @if (isset($next))
                        <a class="btn btn-outline-dark" href="{{ route('live.show', $next->id) }}"rel="next" role="button">></a>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
