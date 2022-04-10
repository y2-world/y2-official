@extends('layouts.app')
@section('content')
<br>
<div class="container">
    <h1>ALL ARTISTS</h1>
    <a class="btn btn-outline-dark btn-sm" href="{{ url('/setlists') }}" role="button">All</a>
    <table class="table table-striped">
        <thead>
          <tr>
            <th>#</th>
            <th>アーティスト名</th>
          </tr>
        </thead>
        <tbody>
            @foreach ($artists as $artist)
            <tr>
                <td></td>
                <td><a href="{{ url('artists', $artist->id)}}">{{ $artist->name }}</a></td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection