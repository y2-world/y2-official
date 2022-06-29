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
          {{-- <th class="mb_list">アルバム</th> --}}
        </tr>
      </thead>
      <div class="all-setlist">
        
            @foreach ($songs as $song)
              @if(!is_null($song->song_id))
              <tbody>
                <tr>
                    <td>{{$song->song_id}}</td>
                    <td><a href="{{ route('songs.show', $song->id) }}">{{ $song->title }}</a></td>
                    {{-- <td>{{ $song->album->title }}</td> --}}
                    {{-- <td><a href="{{ route('albums.show', $song->album_id) }}">{{ $song->album->title }}</a></td> --}}
                </tr>
              </tbody>
              @endif
            @endforeach
       
      </div>
    </table>
  <div class=”pagination”>
    {!! $songs->onEachSide(5)->links() !!}
  </div>
</div>
@endsection