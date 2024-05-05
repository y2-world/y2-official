@extends('layouts.app')
@section('content')
<br>
<div class="container-lg">
    <h2>{{ $artist->name  }}</h2>
    <?php $artist_id = $artist->id; ?>
    <div class="parts-wrapper">
      <div class="pc">
        <div class="dropdown-wrapper">
          <select name="select" onChange="location.href=value;">
            <option value="" disabled selected>Artists</option>
            @foreach ($artists as $artist)
            <option value="{{ url('/setlists/artists', $artist->id)}}">{{ $artist->name }}</option>
            @endforeach
          </select>
          <select name="select" onChange="location.href=value;">
            <option value="" disabled selected>Years</option>
            @foreach ($years as $year)
            <option value="{{ url('/setlists/years', $year->year)}}">{{ $year->year }}</option>
            @endforeach
          </select>
        </div>
      </div>
      <div class="search">
        <form action="{{url('/search')}}" method="GET">
          <input type="hidden" id="id" name="artist_id" value="{{$artist_id}}">
          <div class="input-group mb-3" style="width: 350px;">
              <input type="search" class="form-control" aria-label="Search" value="{{request('keyword')}}" name="keyword" required>
              <div class="input-group-append">
                  <button class="btn btn-outline-secondary" type="submit" id="button-addon2"><i class="fa-solid fa-magnifying-glass"></i></button>
              </div>
          </div>
        </form>
      </div>
    </div>
    <table class="table table-striped count">
      <thead>
        <tr>
          <th class="mobile">#</th>
          <th class="mobile">開催日</th>
          <th class="mobile">タイトル</th>
          <th class="pc">会場</th>
        </tr>
      </thead>
      <tbody>
        @foreach ($setlists as $setlist)
        <tr>
          <td></td>
          <td>{{ date('Y.m.d', strtotime($setlist->date)) }}</td>
          <td><a href="{{ route('setlists.show', $setlist->id) }}">{{ $setlist->title }}</a></td>
          <td class="pc">{{ $setlist->venue }}</td>
        </tr>
        @endforeach
      </tbody>
    </table> 
    <br>
</div>
@endsection