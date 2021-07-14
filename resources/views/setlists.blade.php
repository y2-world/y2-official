@extends('layouts.app')
<style>
</style>
@section('content')
<div class="container">
    <h1>All Setlists</h1>
    @foreach ($setlists as $setlist)
    <li class="list-group-item">
        <div class="row">
            <div class="row">
                <div class="col-md-1">
                {{ $setlist -> id }}
                </div>
                <div class="col-md-2">
                {{ $setlist -> date }}
                </div>
                <div class="col-md-1">
                {{ $setlist -> artist }}
                </div>
                <div class="col-md-4">
                {{ $setlist -> tour_title }}
                </div>
                <div class="col-md-3">
                {{ $setlist -> venue }}
                </div>
            </div>
        </div>
    </li>
    @endforeach
</div>
@endsection