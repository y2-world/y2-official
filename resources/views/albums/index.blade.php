@extends('layouts.app')
@section('content')
<br>
<div class="container-lg">
  <h2>Albums</h2>
  <div class="parts-wrapper">
    <div class="dropdown-wrapper">
      <a class="btn btn-outline-dark btn-sm" href="{{ url('/database/songs') }}" role="button">Songs</a>
      <a class="btn btn-outline-dark btn-sm" href="{{ url('/database/singles') }}" role="button">Singles</a>
      <a class="btn btn-outline-dark btn-sm" href="{{ url('/database/live') }}" role="button">Live</a>
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