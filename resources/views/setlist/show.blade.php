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
</div>
            
@endsection