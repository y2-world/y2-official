@extends('layouts.app')
@section('content')
<br>
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <a href="{{ url('artists', $setlists->artist_id)}}"><h4>{{ $setlists->artist->name  }}</h4></a>
            <h2>{{ $setlists->tour_title }}</h2>
            {{ $setlists->date }}
            <br>
            {{ $setlists->venue }}
            <hr>
            <div class="setlist">
                @foreach ($setlists->setlist as $data)
                    @if($data['#'] === 'Artist')
                    <br>
                    {{ $data['#'] }}: <b>{{ $data['song'] }}</b><br>
                    @else
                    {{ $data['#'] }}. {{ $data['song'] }}<br>
                    @endif
                @endforeach
                {{-- <br>
                @foreach ((array)$setlists->encore as $data)
                {{ $data['#'] }}. {{ $data['song'] }}<br>
                @endforeach --}}
            </div>  
            <br>
            <div class="show_button">
                @if(isset($previous))
                <a class="btn btn-outline-dark" href="{{ route('setlists.show', $previous->id)}}"　rel="prev" role="button"><</a>
                @endif
                @if(isset($next))
                <a class="btn btn-outline-dark" href="{{ route('setlists.show', $next->id)}}"　rel="next" role="button">></a>
                @endif
            </div> 
        </div>
    </div>       
</div>
            
@endsection