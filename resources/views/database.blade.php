@extends('layouts.app')
@section('content')
<br>
<div class="container">
    <h2>Mr.Children Database</h2>
    <hr>
    <h2><a href="{{ url('/songs') }}">Songs</a></h2>
    <hr>
    <h2><a href="{{ url('/albums') }}">Albums</a></h2>
    <hr>
    <h2><a href="{{ url('/tour') }}">Tour</a></h2>
    <hr>
</div>
@endsection
