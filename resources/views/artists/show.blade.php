@extends('layouts.app')
@section('content')
<br>
<div class="container-lg">
    <h2>{{ $artist->name  }}</h2>
    <?php $artist_id = $artist->id; ?>
    <div class="parts-wrapper">
      <div class="pc_list">
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
      </div>
      <div class="search">
        <form action="{{url('/search')}}" method="GET">
          <input type="hidden" id="id" name="artist_id" value="{{$artist_id}}">
          <div class="input-group mb-3">
              <input type="search" class="form-control" aria-label="Search" value="{{request('keyword')}}" name="keyword" required>
              <div class="input-group-append">
                  <button class="btn btn-outline-secondary" type="submit" id="button-addon2">Search</button>
              </div>
          </div>
        </form>
      </div>
    </div>
    <div class="artist">
      <table class="table table-striped">
        <thead>
          <tr>
            <th class="mb_list">#</th>
            <th class="mb_list">開催日</th>
            <th class="mb_list">ツアータイトル</th>
            <th class="mb_list">会場</th>
          </tr>
        </thead>
        <tbody>
          @foreach ($setlists as $setlist)
          <tr>
            <td></td>
            <td>{{ date('Y.m.d', strtotime($setlist->date)) }}</td>
            <td><a href="{{ route('setlists.show', $setlist->id) }}">{{ $setlist->title }}</a></td>
            <td>{{ $setlist->venue }}</td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>  
</div>
@endsection