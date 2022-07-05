@extends('layouts.app')
@section('content')
<br>
<div class="container-lg">
  <h2>Festivals</h2>
  <div class="parts-wrapper">
    <div class="dropdown-wrapper">
      <a class="btn btn-outline-dark btn-sm" href="{{ url('/setlists') }}" role="button">All</a>
      <div class="btn-group">
        <button class="btn btn-outline-dark btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
          Artists
        </button>
        <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton1">
          <li><a class="dropdown-item" href="{{ url('/artists')}}">All Artists</a></li>
          <div class="dropdown-divider"></div>
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
    </div>
    <div class="search">
      <form action="{{url('/search')}}" method="GET">
        <div class="mb_dropdown">
          <select name="artist_id" required data-toggle="select"> 
            <option value="" disabled selected>Artists</option>
              @foreach ($artists as $artist)
                  <option value="{{ $artist->id }}" required>{{$artist->name}}</option>
              @endforeach
          </select>
        </div>
        <div class="input-group mb-3">
            <input type="search" class="form-control" aria-label="Search" value="{{request('keyword')}}" name="keyword" required>
            <div class="input-group-append">
                <button class="btn btn-outline-secondary" type="submit" id="button-addon2">Search</button>
            </div>
        </div>
      </form>
    </div>
  </div>
  <table class="table table-striped">
      <thead>
        <tr>
          <th class="mb_list">#</th>
          <th class="mb_list">開催日</th>
          <th class="mb_list">タイトル</th>
          <th class="pc_list">会場</th>
        </tr>
      </thead>
      <div class="all-setlist">
        <tbody>
            @foreach ($festivals as $festival)
              <tr>
                  <td>{{$festival->id}}</td>
                  <td>{{ date('Y.m.d', strtotime($festival->date)) }}</td>
                  <td><a href="{{ route('festivals.show', $festival->id) }}">{{ $festival->title }}</a></td>
                  <td class="pc_list">{{ $festival->venue }}</td>
              </tr>
            @endforeach
        </tbody>
      </div>
    </table>
  <div class=”pagination”>
    {!! $festivals->links() !!}
  </div>
</div>
@endsection
