@extends('layouts.app')
@section('content')
<br>
<div class="container">
    <h2>Mr.Children Database</h2>
    <a href="{{ url('/songs') }}">Songs</a><br>
    <a href="{{ url('/albums') }}">Albums</a><br>
    <a href="{{ url('/tour') }}">Tour</a>
</div>
@endsection
