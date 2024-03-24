@extends('layouts.app')
@section('content')
<br>
<div class="container-lg">
  <h2>Set Lists</h2>
  <div class="parts-wrapper">
    <div class="dropdown-wrapper">
      <select name="select" onChange="location.href=value;">
        <option value="" disabled selected>Artists</option>
        <option value="{{ url('/artists')}}">All Artists</option>
        @foreach ($artists as $artist)
        <option value="{{ url('/artists', $artist->id)}}">{{ $artist->name }}</option>
        @endforeach
      </select>
      <select name="select" onChange="location.href=value;">
        <option value="" disabled selected>Years</option>
        <option value="{{ url('/years')}}">All Years</option>
        @foreach ($years as $year)
        <option value="{{ url('/years', $year->id)}}">{{ $year->year }}</option>
        @endforeach
      </select>
    </div>
    <div class="pc_list">
      <div class="search">
        <form action="{{url('/search')}}" method="GET">
          <div class="mb_dropdown">
            <select name="artist_id" required data-toggle="select"> 
              <option value="" disabled selected>Artists</option>
                @foreach ($artists as $artist)
                @if($artist->visible != 1)
                    <option value="{{ $artist->id }}" required>{{$artist->name}}</option>
                @endif
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
  </div>
  <table class="table table-striped">
      <thead>
        <tr>
          <th class="mb_list">#</th>
          <th class="mb_list">開催日</th>
          <th class="mb_list">アーティスト / タイトル</th>
          <th class="pc_list">会場</th>
        </tr>
      </thead>
      <div class="all-setlist">
        <tbody>
            @foreach ($setlists as $setlist)
              <tr>
                  <td>{{$setlist->id}}</td>
                  <td>{{ date('Y.m.d', strtotime($setlist->date)) }}</td>
                  @if(isset($setlist->artist_id))
                  <td>
                    <a href="{{ url('artists', $setlist->artist_id)}}">{{ $setlist->artist->name }}</a> /  <a href="{{ route('setlists.show', $setlist->id) }}">{{ $setlist->title }}</a>
                  </td>
                  @else
                  <td><a href="{{ route('setlists.show', $setlist->id) }}">{{ $setlist->title }}</td>
                  @endif
                  {{-- <td><a href="{{ route('setlists.show', $setlist->id) }}">{{ $setlist->title }}</a></td> --}}
                  <td class="pc_list">{{ $setlist->venue }}</td>
              </tr>
            @endforeach
        </tbody>
      </div>
    </table>
  <div class=”pagination”>
    {!! $setlists->links() !!}
  </div>
  <br>
</div>
@endsection
