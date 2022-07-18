@extends('layouts.app')
@section('content')
<br>
<div class="container-lg">
  <h2>{{ $bio->year  }}</h2>
  <div class="parts-wrapper">
    <div class="dropdown-wrapper">
      <a class="btn btn-outline-dark btn-sm" href="{{ url('/singles') }}" role="button">Singles</a>
      <a class="btn btn-outline-dark btn-sm" href="{{ url('/albums') }}" role="button">Albums</a>
      <a class="btn btn-outline-dark btn-sm" href="{{ url('/albums') }}" role="button">Tours</a>
      <select name="select" onChange="location.href=value;">
        <option value="" disabled selected>Years</option>
        @foreach ($bios as $bio)
        <option value="{{ url('/bios', $bio->id)}}">{{ $bio->year }}</option>
        @endforeach
      </select>
    </div>
  </div>
  <table class="table table-striped">
      <thead>
        <tr>
          <th class="mb_list">#</th>
          <th class="mb_list">タイトル</th>
          <th class="mb_list">シングル / アルバム</th>
          <th class="pc_list">リリース日</th>
        </tr>
      </thead>
      <div class="all-setlist">
        <tbody>
            @foreach ($songs as $song)
                <tr>
                  <td>{{$song->id}}</td>
                  <td><a href="{{ route('songs.show', $song->id) }}">{{ $song->title }}</a></td>
                  @if(isset($song->single_id) && isset($song->album_id))
                    @if($song->single->date > $song->album->date)
                    <td><a href="{{ route('albums.show', $song->album_id) }}">{{ $song->album->title }}</a></td>
                    <td class="pc_list">{{ date('Y.m.d', strtotime($song->album->date)) }}</td>
                    @else
                    <td><a href="{{ route('singles.show', $song->single_id) }}">{{ $song->single->title }}</a></td>
                    <td class="pc_list">{{ date('Y.m.d', strtotime($song->single->date)) }}</td>
                    @endif
                  @elseif(isset($song->album_id))
                    <td><a href="{{ route('albums.show', $song->album_id) }}">{{ $song->album->title }}</a></td>
                    <td class="pc_list">{{ date('Y.m.d', strtotime($song->album->date)) }}</td>
                  @elseif(isset($song->single_id))
                    <td><a href="{{ route('singles.show', $song->single_id) }}">{{ $song->single->title }}</a></td>
                    <td class="pc_list">{{ date('Y.m.d', strtotime($song->single->date)) }}</td>
                  @else
                    <td></td>
                    <td class="pc_list"></td>
                  @endif
                  
                </tr>
            @endforeach
        </tbody>
      </div>
    </table>
</div>
@endsection