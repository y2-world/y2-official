@extends('layouts.app')
@section('content')
<br>
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="setlist_artist">
                <a href="{{ url('artists', $setlists->artist_id)}}"><h4>{{ $setlists->artist->name  }}</h4></a>
            </div>
            <div class="setlist_title">{{ $setlists->tour_title }}</div>
            <div class="setlist_info">
                {{ date('Y.m.d', strtotime($setlists->date)) }}
                <br>
                {{ $setlists->venue }}
            </div>
            <hr>
            <div class="setlist">
                @foreach ($setlists->setlist as $data) 
                    @if($data['#'] === 'Artist')
                    <br>
                    {{ $data['#'] }} : <b>{{ $data['song'] }}</b><br>
                    @else
                    {{ $data['#'] }}. {{ $data['song'] }}<br>
                    @endif
                @endforeach
                @if(isset($setlists->encore))
                    <hr width="200">
                    @foreach ((array)$setlists->encore as $data)
                    {{ $data['#'] }}. {{ $data['song'] }}<br>
                    @endforeach
                @endif
            </div>  
            <br>
            <div class="show_button">
                @if(isset($previous))
                <a class="btn btn-outline-dark" href="{{ route('setlists.show', $previous->id)}}" rel="prev" role="button"><</a>
                @endif
                @if(isset($next))
                <a class="btn btn-outline-dark" href="{{ route('setlists.show', $next->id)}}"rel="next" role="button">></a>
                @endif
            </div> 
        </div>
    </div>       
</div>
            
@endsection