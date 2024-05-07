@extends('layouts.app')
@section('content')
<br>
<div class="container-lg">
  <h2>Singles</h2>
  <div class="database-wrapper">
    <div class="dropdown-wrapper">
      <a class="btn btn-outline-dark btn-sm" href="{{ url('/database/songs') }}" role="button">Songs</a>
      <a class="btn btn-outline-dark btn-sm" href="{{ url('/database/albums') }}" role="button">Albums</a>
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
            @foreach ($singles as $single)
              <tr>
                  <td>{{$single->single_id}}</td>
                  <td><a href="{{ route('singles.show', $single->id) }}">{{ $single->title }}</a></td>
                  <td>{{ date('Y.m.d', strtotime($single->date)) }}</td>
              </tr>
            @endforeach
        </tbody>
      </div>
    </table>
  <div class=”pagination”>
    {!! $singles->onEachSide(5)->links() !!}
  </div>
  <br>
</div>
@endsection