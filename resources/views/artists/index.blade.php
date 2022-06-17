@extends('layouts.app')
@section('content')
<br>
<div class="container">
    <h1>ALL ARTISTS</h1>
    <a class="btn btn-outline-dark btn-sm" href="{{ url('/setlists') }}" role="button">All</a>
    <div class="artist">
      <table class="table table-striped">
        <thead>
          <tr>
            <th class="mb_list">#</th>
            <th class="mb_list">アーティスト</th>
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
</div>
@endsection