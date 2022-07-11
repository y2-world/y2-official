@extends('layouts.app')
@section('content')
<br>
<div class="container-lg">
  <a class="btn btn-outline-dark btn-sm" href="{{ url('/songs') }}" role="button">Songs</a>
  <a class="btn btn-outline-dark btn-sm" href="{{ url('/singles') }}" role="button">Singles</a>
  <a class="btn btn-outline-dark btn-sm" href="{{ url('/albums') }}" role="button">Albums</a>
  <br><br>
  <h2>Songs</h2>
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
                  <td>{{$song->song_id}}</td>
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
  <div class=”pagination”>
    {!! $songs->onEachSide(5)->links() !!}
  </div>
</div>
@endsection