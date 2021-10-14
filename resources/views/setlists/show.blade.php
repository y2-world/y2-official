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
                {{ $data['#'] }}. {{ $data['song'] }}<br>
                @endforeach
            </div>   
        </div>
    </div>       
</div>
            
@endsection