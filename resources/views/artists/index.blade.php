@extends('layouts.app')
@section('content')
<br>
<div class="container">
    <h1>ALL ARTISTS</h1>
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