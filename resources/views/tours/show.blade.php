@extends('layouts.app')
@section('content')
<br>
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="setlist_title">{{ $tours->tour_title }}</div>
            <div class="setlist_info">
                {{ date('Y.m.d', strtotime($tours->date1)) }} - {{ date('Y.m.d', strtotime($tours->date2)) }}
                <br>
                {{ $tours->venue }}
            </div>
            <hr>
            <div class="setlist">
                @if(isset($tours->setlist))
                    @foreach ($tours->setlist as $data) 
                        @if(isset($data['song']))
                            {{ $data['#'] }}. <a href="{{ url('songs', $data['song'])}}">{{ $data['song'] }}</a><br>
                        @elseif(isset($data['exception']))
                            {{ $data['#'] }}. {{ $data['exception'] }}<br>
                        @endif
                    @endforeach
                @endif
                @if(isset($tours->encore))
                    <hr width="250">
                    @foreach ($tours->encore as $data) 
                    @if(isset($data['song']))
                        {{ $data['#'] }}. <a href="{{ url('songs', $data['song'])}}">{{ $data['song'] }}</a><br>
                    @elseif(isset($data['exception']))
                        {{ $data['#'] }}. {{ $data['exception'] }}<br>
                    @endif
                @endforeach
                    <br>
                @endif
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