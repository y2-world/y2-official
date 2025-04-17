@extends('layouts.app')
@section('title', 'Yuki Official - Albums')
@section('content')
<br>
<div class="container-lg">
  <h2>Albums</h2>
  <div class="database-wrapper">
    <div class="dropdown-wrapper">
      <select name="select" onChange="location.href=value;">
        <option value="" disabled selected>Discography</option>
        <option value="{{ url('/database/songs') }}">Songs</option>
        <option value="{{ url('/database/singles') }}">Singles</option>
      </select>
      <select name="select" onChange="location.href=value;">
          <option value="" disabled selected>Live</option>
          <option value="{{ url('/database/live') }}">All</option>
          <option value="{{ url('/database/live?type=1') }}">Tours</option>
          <option value="{{ url('/database/live?type=2') }}">Events</option>
          <option value="{{ url('/database/live?type=3') }}">ap bank fes</option>
          <option value="{{ url('/database/live?type=4') }}">Solo</option>
      </select>
      <select name="select" onChange="location.href=value;">
        <option value="" disabled selected>Years</option>
        @foreach ($bios as $bio)
        <option value="{{ url('/database/years', $bio->year)}}">{{ $bio->year }}</option>
        @endforeach
      </select>
    </div>
  </div>
  <table class="table table-striped">
      <thead>
        <tr>
          <th class="mobile">#</th>
          <th class="mobile">タイトル</th>
          <th class="mobile">リリース日</th>
        </tr>
      </thead>
      <div class="all-setlist">
        <tbody>
            @foreach ($albums as $album)
              <tr>
                  <td>{{$album->album_id}}</td>
                  <td><a href="{{ route('albums.show', $album->id) }}">{{ $album->title }}</a></td>
                  <td>{{ date('Y.m.d', strtotime($album->date)) }}</td>
              </tr>
            @endforeach
        </tbody>
      </div>
    </table>
  <div class=”pagination”>
    {!! $albums->onEachSide(5)->links() !!}
  </div>
</div>
@endsection