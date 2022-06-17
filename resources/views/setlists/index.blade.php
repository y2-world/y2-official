@extends('layouts.app')
@section('content')
<br>
<div class="container-lg">
  <h1>ALL SET LISTS</h1>
  <a class="btn btn-outline-dark btn-sm" href="{{ url('/setlists') }}" role="button">All</a>
  <div class="btn-group">
    <button class="btn btn-outline-dark btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
      Artists
    </button>
    <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton1">
      @foreach ($artists as $artist)
      <li><a class="dropdown-item" href="{{ url('/artists', $artist->id)}}">{{ $artist->name }}</a></li>
      @endforeach
    </ul>
  </div>
  <div class="btn-group">
    <button class="btn btn-outline-dark btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
      Years
    </button>
    <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton1">
    </ul>
  </div>
  <table class="table table-striped">
      <thead>
        <tr>
          <th class="mb_list">#</th>
          <th class="mb_list">開催日</th>
          <th class="mb_list">Artist</th>
          <th class="mb_list">Tour Title</th>
          <th class="pc_list">Venue</th>
        </tr>
      </thead>
      <div class="all-setlist">
        <tbody>
            @foreach ($setlists as $setlist)
            <tr>
                <td></td>
                <td>{{ date('Y.m.d', strtotime($setlist->date)) }}</td>
                <td><a href="{{ url('artists', $setlist->artist_id)}}">{{ $setlist->artist->name }}</a></td>
                <td><a href="{{ route('setlists.show', $setlist->id) }}">{{ $setlist->tour_title }}</a></td>
                <td class="pc_list">{{ $setlist->venue }}</td>
            </tr>
            @endforeach
        </tbody>
      </div>
  </table>
  <div class=”pagination”>
    {!! $setlists->links() !!}
  </div>
</div>
@endsection
