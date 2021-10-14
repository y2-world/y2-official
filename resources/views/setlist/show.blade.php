@extends('layouts.app')
<style>
</style>
@section('content')
<br>
<div class="container">
    <h4>{{ $setlists -> artist }}</h4>
    <h2>{{ $setlists -> tour_title }}</h2>
    {{ $setlists -> date }}
    <br>
    {{ $setlists -> venue }}
    <hr>
    @foreach ($setlists -> setlist as $data)
    {{ $data['#'] }}.{{ $data['楽曲'] }}<br>
    @endforeach
</div>
            
@endsection