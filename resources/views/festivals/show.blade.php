@extends('layouts.app')
@section('content')
<br>
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="setlist_title">{{ $festivals->title }}</div>
            <div class="setlist_info">
                {{ date('Y.m.d', strtotime($festivals->date)) }}
                <br>
                {{ $festivals->venue }}
            </div>
            <hr>
            <div class="setlist">
                @foreach ($festivals->setlist as $data) 
                    @if($data['#'] === '-' && $data['song'] === '-')
                    {{ $data['artist'] }}<br>
                    @elseif($data['artist'] === '-')
                    {{ $data['#'] }}. {{ $data['song'] }}<br>
                    @elseif($data['artist'] === 'END')
                    {{ $data['#'] }}. {{ $data['song'] }}<br><br>
                    @endif
                @endforeach
                @if(isset($festivals->encore))
                    <hr width="250">
                    @foreach ((array)$festivals->encore as $data)
                        @if($data['#'] === '-' && $data['song'] === '-')
                        {{ $data['artist'] }}<br>
                        @elseif($data['artist'] === '-')
                        {{ $data['#'] }}. {{ $data['song'] }}<br>
                        @elseif($data['artist'] === 'END')
                        {{ $data['#'] }}. {{ $data['song'] }}<br><br>
                        @endif
                    @endforeach
                @endif
            </div>  
            <br>
            <div class="show_button">
                @if(isset($previous))
                <a class="btn btn-outline-dark" href="{{ route('festivals.show', $previous->id)}}" rel="prev" role="button"><</a>
                @endif
                @if(isset($next))
                <a class="btn btn-outline-dark" href="{{ route('festivals.show', $next->id)}}"rel="next" role="button">></a>
                @endif
            </div> 
        </div>
    </div>       
</div>
            
@endsection