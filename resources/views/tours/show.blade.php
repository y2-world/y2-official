@extends('layouts.app')
@section('content')
<br>
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="setlist_title">{{ $tours->title }}</div>
            <div class="setlist_info">
                @if(isset($tours->date1) && isset($tours->date2))
                {{ date('Y.m.d', strtotime($tours->date1)) }} - {{ date('Y.m.d', strtotime($tours->date2)) }}
                @elseif(isset($tours->date1) && !isset($$tours->date2))
                {{ date('Y.m.d', strtotime($tours->date1)) }}
                @endif
                <br>
                {{ $tours->venue }}
            </div>
            <div class="setlist">
                @if(isset($tours->setlist1))
                <hr>
                @endif
                <div class="setlist-row">
                    <div class="column1">
                        @if(isset($tours->setlist1))
                            @foreach ($tours->setlist1 as $data) 
                                @if(!isset($data['song']) && !isset($data['exception']))
                                    @if($data['#'] == 'ENCORE')
                                        <hr width="250">
                                    @elseif($data['#'] == 'hr')
                                        <hr width="80%">
                                    @else
                                        <h5>{{ $data['#'] }} </h5>
                                    @endif
                                @elseif(isset($data['song']) && !isset($data['exception']))
                                    @if($data['#'] !== '-')
                                        {{ $data['#'] }}. <a href="{{ url('songs', $data['id'])}}">{{ $data['song'] }}</a><br>
                                    @elseif($data['#'] == '-')
                                        {{ $data['#'] }} <a href="{{ url('songs', $data['id'])}}">{{ $data['song'] }}</a><br>
                                    @endif
                                @elseif(!isset($data['song']) && isset($data['exception']))
                                    @if(isset($data['id']))
                                        @if($data['#'] == '-')
                                            {{ $data['#'] }} <a href="{{ url('songs', $data['id'])}}">{{ $data['exception'] }}</a><br>
                                        @else
                                            {{ $data['#'] }}. <a href="{{ url('songs', $data['id'])}}">{{ $data['exception'] }}</a><br>
                                        @endif
                                    @else
                                        @if($data['#'] == '-')
                                            {{ $data['#'] }} {{ $data['exception'] }}<br>
                                        @else
                                            {{ $data['#'] }}. {{ $data['exception'] }}<br>
                                        @endif
                                    @endif
                                @endif
                            @endforeach
                        @endif
                    </div>
                    @if(isset($tours->setlist2))
                        <div class="column2-tour">
                            @if(isset($tours->setlist2))
                            @foreach ($tours->setlist2 as $data) 
                                @if(!isset($data['song']) && !isset($data['exception']))
                                    @if($data['#'] == 'ENCORE')
                                        <hr width="250">
                                    @elseif($data['#'] == 'hr')
                                        <hr width="80%">
                                    @else
                                        <h5>{{ $data['#'] }} </h5>
                                    @endif
                                @elseif(isset($data['song']) && !isset($data['exception']))
                                    @if($data['#'] !== '-')
                                        {{ $data['#'] }}. <a href="{{ url('songs', $data['id'])}}">{{ $data['song'] }}</a><br>
                                    @elseif($data['#'] == '-')
                                        {{ $data['#'] }} <a href="{{ url('songs', $data['id'])}}">{{ $data['song'] }}</a><br>
                                    @endif
                                 @elseif(!isset($data['song']) && isset($data['exception']))
                                    @if(isset($data['id']))
                                        @if($data['#'] == '-')
                                            {{ $data['#'] }} <a href="{{ url('songs', $data['id'])}}">{{ $data['exception'] }}</a><br>
                                        @else
                                            {{ $data['#'] }}. <a href="{{ url('songs', $data['id'])}}">{{ $data['exception'] }}</a><br>
                                        @endif
                                    @else
                                        @if($data['#'] == '-')
                                            {{ $data['#'] }} {{ $data['exception'] }}<br>
                                        @else
                                            {{ $data['#'] }}. {{ $data['exception'] }}<br>
                                        @endif
                                    @endif
                                @endif
                            @endforeach
                            @endif
                        </div>
                    @endif
                </div>
                @if(!is_null($tours->schedule))
                <hr>
                <h5>SCHEDULE</h5>
                {!! nl2br(e($tours->schedule)) !!}
                @endif
                @if(!is_null($tours->text))
                <hr>
                {!! nl2br(e($tours->text)) !!}
                @endif
            </div>  
            <br>
            <div class="show_button">
                @if(isset($previous))
                <a class="btn btn-outline-dark" href="{{ route('tours.show', $previous->id)}}" rel="prev" role="button"><</a>
                @endif
                @if(isset($next))
                <a class="btn btn-outline-dark" href="{{ route('tours.show', $next->id)}}"rel="next" role="button">></a>
                @endif
            </div> 
        </div>
    </div>       
</div>
            
@endsection