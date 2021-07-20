@extends('layouts.app')
<style>
</style>
@section('content')
<br>
<div class="container">
    {{ $setlists -> artist }}
    <h2>{{ $setlists -> tour_title }}</h2>
    {{ $setlists -> date }}
    <br>
    {{ $setlists -> venue }}
    <hr>
    @php
        $rec = [];
        $rec = json_encode( $setlists['setlist'], JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT );
    @endphp
    {{ $rec }}
</div>
            
@endsection