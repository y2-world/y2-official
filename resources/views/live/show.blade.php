@extends('layouts.app')
@section('content')
    <br>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="setlist_title">{{ $tours->title }}</div>
                <div class="setlist_info">
                    @if (isset($tours->date1) && isset($tours->date2))
                        {{ date('Y.m.d', strtotime($tours->date1)) }} - {{ date('Y.m.d', strtotime($tours->date2)) }}
                    @elseif(isset($tours->date1) && !isset($$tours->date2))
                        {{ date('Y.m.d', strtotime($tours->date1)) }}
                    @endif
                    <br>
                    {{ $tours->venue }}
                </div>
                <div class="setlist">
                    @if (isset($tours->setlist1))
                        <hr>
                    @endif
                    <div class="setlist-row">
                        <div class="column1">
                            @if (isset($tours->setlist1))
                            <ol>
                                @foreach ($tours->setlist1 as $data)
                                    @if (!empty($data['id']))
                                        @if (!empty($data['exception']))
                                            @if (!empty($data['#']) && $data['#'] == '-')
                                                {{ $data['#'] }} <a
                                                    href="{{ url('/database/songs', $data['id']) }}">{{ $data['exception'] }}</a><br>
                                            @else
                                                <li><a
                                                    href="{{ url('/database/songs', $data['id']) }}">{{ $data['exception'] }}</a></li>
                                            @endif
                                        @else
                                            @if (!empty($data['#']) && $data['#'] !== '-')
                                                <li> <a
                                                    href="{{ url('/database/songs', $data['id']) }}">{{ $songs[$data['id'] - 1]['title'] }}</a></li>
                                            @elseif(!empty($data['#']) && $data['#'] == '-')
                                                {{ $data['#'] }} <a
                                                    href="{{ url('/database/songs', $data['id']) }}">{{ $songs[$data['id'] - 1]['title'] }}</a><br>
                                            @endif
                                        @endif
                                    @else
                                        @if (!empty($data['exception']))
                                            @if (!empty($data['id']))
                                                @if (!empty($data['#']) && $data['#'] == '-')
                                                    {{ $data['#'] }} <a
                                                        href="{{ url('/database/songs', $data['id']) }}">{{ $data['exception'] }}</a><br>
                                                @else
                                                <li><a
                                                        href="{{ url('/database/songs', $data['id']) }}">{{ $data['exception'] }}</a><li>
                                                @endif
                                            @else
                                                @if (!empty($data['#']) && $data['#'] == '-')
                                                    {{ $data['#'] }} {{ $data['exception'] }}<br>
                                                @else
                                                    <li> {{ $data['exception'] }}</li>
                                                @endif
                                            @endif
                                        @else
                                            @if (!empty($data['#']) && $data['#'] == 'ENCORE')
                                                <hr width="250">
                                            @elseif(!empty($data['#']) && $data['#'] == 'hr')
                                                </ol>
                                                <hr width="80%">
                                                <ol>
                                            @else
                                                <h5>{{ $data['#'] }} </h5>
                                            @endif
                                        @endif
                                    @endif
                                @endforeach
                            </ol>
                            @endif
                        </div>
                        @if (isset($tours->setlist2))
                            <div class="column2-tour">
                                @if (isset($tours->setlist2))
                                <ol>
                                    @foreach ($tours->setlist2 as $data)
                                        @if (isset($data['id']))
                                            @if (isset($data['exception']))
                                                @if (!empty($data['#']) && $data['#'] == '-')
                                                    {{ $data['#'] }} <a
                                                        href="{{ url('/database/songs', $data['id']) }}">{{ $data['exception'] }}</a><br>
                                                @else
                                                    <li> <a
                                                        href="{{ url('/database/songs', $data['id']) }}">{{ $data['exception'] }}</a></li>
                                                @endif
                                            @else
                                                @if (empty($data['#']))
                                                <li> <a
                                                        href="{{ url('/database/songs', $data['id']) }}">{{ $songs[$data['id'] - 1]['title'] }}</a></li>
                                                @elseif(!empty($data['#']) && $data['#'] == '-')
                                                    {{ $data['#'] }} <a
                                                        href="{{ url('/database/songs', $data['id']) }}">{{ $songs[$data['id'] - 1]['title'] }}</a><br>
                                                @endif
                                            @endif
                                        @else
                                            @if (isset($data['exception']))
                                                @if (isset($data['id']))
                                                    @if (!empty($data['#']) && $data['#'] == '-')
                                                        {{ $data['#'] }} <a
                                                            href="{{ url('/database/songs', $data['id']) }}">{{ $data['exception'] }}</a><br>
                                                    @else
                                                    <li> <a
                                                            href="{{ url('/database/songs', $data['id']) }}">{{ $data['exception'] }}</a></li>
                                                    @endif
                                                @else
                                                    @if (!empty($data['#']) && $data['#'] == '-')
                                                        {{ $data['#'] }} {{ $data['exception'] }}<br>
                                                    @else
                                                    <li> {{ $data['exception'] }}</li>
                                                    @endif
                                                @endif
                                            @else
                                                @if (!empty($data['#']) && $data['#'] == 'ENCORE')
                                                    <hr width="250">
                                                @elseif($data['#'] == 'hr')
                                                    </ol>
                                                    <hr width="80%">
                                                    <ol>
                                                @else
                                                    <h5>{{ $data['#'] }} </h5>
                                                @endif
                                            @endif
                                        @endif
                                    @endforeach
                                </ol>
                                @endif
                            </div>
                        @endif
                    </div>
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
                <div class="show_button">
                    @if (isset($previous))
                        <a class="btn btn-outline-dark" href="{{ route('live.show', $previous->id) }}" rel="prev"
                            role="button">
                            <</a>
                    @endif
                    @if (isset($next))
                        <a class="btn btn-outline-dark" href="{{ route('live.show', $next->id) }}"rel="next"
                            role="button">></a>
                    @endif
                </div>
            </div>
        </div>
    </div>

@endsection
