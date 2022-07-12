@extends('layouts.app')
@section('content')
<br>
<div class="container">
  <h2>All Artists</h2>
  <a class="btn btn-outline-dark btn-sm" href="{{ url('/setlists') }}" role="button">All</a>
  <div class="btn-group">
    <button class="btn btn-outline-dark btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
      Years
    </button>
    <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton1">
      <li><a class="dropdown-item" href="{{ url('/years')}}">All Years</a></li>
      <div class="dropdown-divider"></div>
      @foreach ($years as $year)
      <li><a class="dropdown-item" href="{{ url('/years', $year->id)}}">{{ $year->year }}</a></li>
      @endforeach
    </ul>
  </div>
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