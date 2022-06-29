@extends('layouts.app')
@section('content')
<br>
<div class="container-lg">
  <h2>Songs</h2>
  <table class="table table-striped">
      <thead>
        <tr>
          <th class="mb_list">#</th>
          <th class="mb_list">タイトル</th>
          <th class="mb_list">アルバム</th>
          <th class="pc_list">会場</th>
        </tr>
      </thead>
      <div class="all-setlist">
        <tbody>
            @foreach ($songs as $song)
                <tr>
                  <td>{{$song->song_id}}</td>
                  <td><a href="{{ route('songs.show', $song->id) }}">{{ $song->title }}</a></td>
                  @if(isset($song->album_id))
                    <td><a href="{{ route('albums.show', $song->album_id) }}">{{ $song->album->title }}</a></td>
                    <td>{{ date('Y.m.d', strtotime($song->album->date)) }}</td>
                  @else
                    <td></td>
                    <td></td>
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