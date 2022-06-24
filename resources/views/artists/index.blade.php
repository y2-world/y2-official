@extends('layouts.app')
@section('content')
<br>
<div class="container">
  <h1>All Artists</h1>
  <a class="btn btn-outline-dark btn-sm" href="{{ url('/setlists') }}" role="button">All</a>
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
            <td>{{ $artist->id }}</td>
            <td><a href="{{ url('artists', $artist->id)}}">{{ $artist->name }}</a></td>
        </tr>
        @endforeach
    </tbody>
  </table>
  <div class=”pagination”>
    {!! $artists->links() !!}
  </div>
</div>
@endsection