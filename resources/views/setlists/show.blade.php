@extends('layouts.app')
@section('content')
    <br>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="setlist_artist">
                    @if ($setlists->fes == false)
                        <a href="{{ url('artists', $setlists->artist_id) }}">{{ $setlists->artist->name }}</a>
                    @endif
                </div>
                <div class="setlist_title">{{ $setlists->title }}</div>
                <div class="setlist_info">
                    {{ date('Y.m.d', strtotime($setlists->date)) }}
                    <br>
                    {{ $setlists->venue }}
                </div>
                <hr>
                    @if ($setlists->fes == 0)
                    <ol class="setlist">
                        @foreach ($setlists->setlist as $data)
                            @if (isset($data['#']))
                                @if ($data['#'] === '-')
                                    {{ $data['#'] }} <a href="{{ url('/search?artist_id='.$setlist->artist_id.'&keyword='.$data['song']) }}">{{ $data['song'] }}</a><br>
                                @else
                                <li><a href="{{ url('/search?artist_id='.$setlist->artist_id.'&keyword='.$data['song']) }}">{{ $data['song'] }}</a></li>
                                @endif
                            @else
                                <li><a href="{{ url('/search?artist_id='.$setlist->artist_id.'&keyword='.$data['song']) }}">{{ $data['song'] }}</a></li>
                            @endif
                        @endforeach
                        @if (isset($setlists->encore))
                            <hr width="250">
                            @foreach ((array) $setlists->encore as $data)
                            <li><a href="{{ url('/search?artist_id='.$setlist->artist_id.'&keyword='.$data['song']) }}">{{ $data['song'] }}</a></li>
                            @endforeach
                        @endif
                    </ol>
                    @elseif($setlists->fes == 1)
                        @foreach ((array) $setlists->fes_setlist as $key => $data)
                            @if (isset($data['artist']))
                                @if ($key != 0)
                                    @if ($setlists->fes_setlist[$key]['artist'] != $setlists->fes_setlist[$key - 1]['artist'])
                                    </ol>
                                    <ol class="setlist">
                                        {{ $artists[$data['artist'] - 1]['name'] }}<br>
                                        <li> {{ $data['song'] }}</li>
                                    @else
                                        <li> {{ $data['song'] }}</li>
                                    @endif
                                @else
                                <ol class="setlist">
                                    {{ $artists[$data['artist'] - 1]['name'] }}<br>
                                    <li> {{ $data['song'] }}</li>
                                @endif
                            @else
                                <li> {{ $data['song'] }}</li>
                                </ol>
                            @endif
                        @endforeach
                        </ol>
                    
                        @if (isset($setlists->fes_encore))
                            <hr width="250">
                            @foreach ((array) $setlists->fes_encore as $key => $data)
                                @if (isset($data['artist']))
                                    @if ($key != 0)
                                        @if ($setlists->fes_encore[$key]['artist'] != $setlists->fes_encore[$key - 1]['artist'])
                                            </ol>
                                            <ol class="setlist">
                                            {{ $artists[$data['artist'] - 1]['name'] }}<br>
                                            <li> {{ $data['song'] }}</li>
                                        @else
                                            <li> {{ $data['song'] }}</li>
                                        @endif
                                    @else
                                        <ol class="setlist">
                                        {{ $artists[$data['artist'] - 1]['name'] }}<br>
                                        <li> {{ $data['song'] }}</li>
                                    @endif
                                @else
                                    <li> {{ $data['song'] }}</li>
                                    </ol>
                                @endif
                            @endforeach
                        @endif
                        @elseif($setlists->fes == 2)
                        <ol class="setlist">
                        @foreach ((array) $setlists->fes_setlist as $key => $data)
                            @if (isset($data['corner']))
                                @if ($key != 0)
                                    @if ($setlists->fes_setlist[$key]['corner'])
                                        <br>{{ $data['corner'] }}<br>
                                        <li> {{ $data['song'] }}</li>
                                    @else
                                        <li> {{ $data['song'] }}</li>
                                    @endif
                                @else
                                    {{ $artists[$data['artist'] - 1]['corner'] }}<br>
                                    <li> {{ $data['song'] }}</li>
                                @endif
                            @elseif (isset($data['artist']))
                                @if ($key != 0)
                                    @if ($setlists->fes_setlist[$key]['artist'] != $setlists->fes_setlist[$key - 1]['artist'])
                                        <br>{{ $artists[$data['artist'] - 1]['name'] }}<br>
                                        <li> {{ $data['song'] }}</li>
                                    @else
                                        <li> {{ $data['song'] }}</li>
                                    @endif
                                @else
                                    {{ $artists[$data['artist'] - 1]['name'] }}<br>
                                    <li> {{ $data['song'] }}</li>
                                @endif
                            @else
                                <li> {{ $data['song'] }}</li>
                            @endif
                        @endforeach
                        @if (isset($setlists->fes_encore))
                            <hr width="250">
                            @foreach ((array) $setlists->fes_encore as $key => $data)
                                @if (isset($data['corner']))
                                    @if ($key != 0)
                                        @if ($setlists->fes_encore[$key]['corner'] != $setlists->fes_encore[$key - 1]['corner'])
                                            <br>{{ $artists[$data['artist'] - 1]['corner'] }}<br>
                                            <li> {{ $data['song'] }}</li>
                                        @else
                                            <li> {{ $data['song'] }}</li>
                                        @endif
                                    @else
                                        {{ $artists[$data['artist'] - 1]['name'] }}<br>
                                        <li> {{ $data['song'] }}</li>
                                    @endif
                                @else
                                    <li> {{ $data['song'] }}</li>
                                @endif
                            @endforeach
                        @endif
                        </ol>
                    @endif
                <div class="show_button">
                    @if (isset($previous))
                        <a class="btn btn-outline-dark" href="{{ route('setlists.show', $previous->id) }}"
                            rel="prev" role="button">
                            <</a>
                    @endif
                    @if (isset($next))
                        <a class="btn btn-outline-dark" href="{{ route('setlists.show', $next->id) }}"rel="next"
                            role="button">></a>
                    @endif
                </div>
            </div>
        </div>
    </div>

@endsection
