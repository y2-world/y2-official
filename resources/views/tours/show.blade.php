@extends('layouts.app')
@section('content')
<br>
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="setlist_title">{{ $tours->tour_title }}</div>
            <div class="setlist_info">
                @if(isset($tours->date1) && isset($tours->date2))
                {{ date('Y.m.d', strtotime($tours->date1)) }} - {{ date('Y.m.d', strtotime($tours->date2)) }}
                @elseif(isset($tours->date1) && !isset($$tours->date2))
                {{ date('Y.m.d', strtotime($tours->date1)) }}
                @endif
                <br>
                {{ $tours->venue }}
            </div>
            <hr>
            <div class="setlist">
                <div class="setlist-row">
                    <div class="column">
                        @if(isset($tours->setlist))
                            @foreach ($tours->setlist as $data) 
                                @if(!isset($data['song']) && !isset($data['exception']))
                                    @if($data['#'] == 'ENCORE')
                                        <hr width="250">
                                    @elseif($data['#'] == 'HR')
                                        <hr>
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
                                    @if(isset($data['id']) && isset($data['exception']))
                                        {{ $data['#'] }}. <a href="{{ url('songs', $data['id'])}}">{{ $data['exception'] }}</a><br>
                                    @elseif(!isset($data['id']) && isset($data['exception']))
                                        {{ $data['#'] }}. {{ $data['exception'] }}<br>
                                    @endif
                                @endif
                            @endforeach
                        @endif
                    </div>
                    <br>
                    <div class="column">
                        @if(isset($tours->encore))
                        @foreach ($tours->encore as $data) 
                            @if(!isset($data['song']) && !isset($data['exception']))
                                @if($data['#'] == 'ENCORE')
                                    <hr width="250">
                                @elseif($data['#'] == 'HR')
                                    <hr>
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
                                @if(isset($data['id']) && isset($data['exception']))
                                    {{ $data['#'] }}. <a href="{{ url('songs', $data['id'])}}">{{ $data['exception'] }}</a><br>
                                @elseif(!isset($data['id']) && isset($data['exception']))
                                    {{ $data['#'] }}. {{ $data['exception'] }}<br>
                                @endif
                            @endif
                        @endforeach
                        @endif
                    </div>
                </div>
                @if(!is_null($tours->text))
                <br>
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