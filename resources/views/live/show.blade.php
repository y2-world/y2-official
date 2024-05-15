@extends('layouts.app')
@section('content')
    <br>
    <div class="container">
        @php
            $hasSetlist2Or3 = !empty($tours->setlist2) || !empty($tours->setlist3);
            $justifyClass = $hasSetlist2Or3 ? 'justify-content-space-around' : 'justify-content-center';
            $hasSetlist3 = !empty($tours->setlist3);
            $colClass = $hasSetlist3 ? 'col-md-10' : 'col-md-8';
            $flexDirectionClass = $hasSetlist2Or3 ? 'justify-content-space-around' : (!empty($tours->setlist1) ? 'justify-content-flex-start' : '');
        @endphp
        <div class="row {{ $justifyClass }}"> 
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
                    @endif
                    <div class="setlist-row {{ $flexDirectionClass }}">
                        <div class="column-live">
                            <!-- Content for setlist1 -->
                            @if (isset($tours->setlist1))
                            <ol>
                                @foreach ($tours->setlist1 as $data)
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
                                            @if (empty($data['#']) || !empty($data['#']) && $data['#'] != '-')
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
                                                <hr width="95%">
                                            @elseif($data['#'] == 'hr')
                                                </ol>
                                                <hr width="95%">
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
                            <div class="column-live">
                                <!-- Content for setlist2 -->
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
                                            @if (empty($data['#']) || !empty($data['#']) && $data['#'] != '-')
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
                                                <hr width="95%">
                                            @elseif($data['#'] == 'hr')
                                                </ol>
                                                <hr width="95%">
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
                        @if (isset($tours->setlist3))
                            <div class="column-live">
                                <!-- Content for setlist3 -->
                                @if (isset($tours->setlist3))
                            <ol>
                                @foreach ($tours->setlist3 as $data)
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
                                            @if (empty($data['#']) || !empty($data['#']) && $data['#'] != '-')
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
                                                <hr width="95%">
                                            @elseif($data['#'] == 'hr')
                                                </ol>
                                                <hr width="95%">
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
                <div class="show_button">
                    <!-- Buttons for navigation -->
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
