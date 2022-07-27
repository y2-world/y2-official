@extends('layouts.app')
@section('content')
<br>
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="setlist_title">{{ $apbanks->title }}</div>
            <div class="setlist_info">
                @if(isset($apbanks->date1) && isset($apbanks->date2))
                {{ date('Y.m.d', strtotime($apbanks->date1)) }} - {{ date('Y.m.d', strtotime($apbanks->date2)) }}
                @elseif(isset($apbanks->date1) && !isset($$apbanks->date2))
                {{ date('Y.m.d', strtotime($apbanks->date1)) }}
                @endif
                <br>
                {{ $apbanks->venue }}
            </div>
            <div class="setlist">
                @if(isset($apbanks->setlist))
                <hr>
                @endif
                <div class="setlist-row">
                    <div class="column1">
                        @if(isset($apbanks->setlist))
                            @foreach ($apbanks->setlist as $data) 
                                @if(!isset($data['song']) && !isset($data['exception']))
                                    @if($data['#'] == 'ENCORE')
                                        <hr width="250">
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
                                        {{ $data['#'] }}. {{ $data['exception'] }}<br>
                                    @endif
                                @endif
                            @endforeach
                        @endif
                    </div>
                </div>
                @if(!is_null($apbanks->text))
                <hr>
                {!! nl2br(e($apbanks->text)) !!}
                @endif
            </div>  
            <br>
            <div class="show_button">
                @if(isset($previous))
                <a class="btn btn-outline-dark" href="{{ route('apbanks.show', $previous->id)}}" rel="prev" role="button"><</a>
                @endif
                @if(isset($next))
                <a class="btn btn-outline-dark" href="{{ route('apbanks.show', $next->id)}}"rel="next" role="button">></a>
                @endif
            </div> 
        </div>
    </div>       
</div>
            
@endsection