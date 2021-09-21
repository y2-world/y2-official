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
    @php
        $rec = json_decode($setlists -> setlist, true);
    @endphp
    @foreach ($rec as $data)
    {{ $rec['楽曲'] }}
    @endforeach
</div>
            
@endsection