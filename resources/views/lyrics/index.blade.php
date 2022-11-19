@extends('layouts.app')
@section('content')
<br>
<div class="container">
  <h2>Lyrics</h2>
  <select name="select" onChange="location.href=value;">
    <option value="" disabled selected>Discography</option>
    <option value="" disabled>Single</option>
      @foreach ($discos as $disc)
      @if($disc->type == 0)
      <option value="{{ url('/music', $disc->id)}}">{{ $disc->title }}</option>
      @endif
      @endforeach
    <option value="" disabled>Album</option>
      @foreach ($discos as $disc)
      @if($disc->type == 1)
      <option value="{{ url('/music', $disc->id)}}">{{ $disc->title }}</option>
      @endif
      @endforeach
  </select>
  <table class="table table-striped">
    <thead>
      <tr>
        <th class="mb_list">#</th>
        <th class="mb_list">タイトル</th>
        <th class="mb_list">アルバム</th>
      </tr>
    </thead>
    <tbody>
        @foreach ($lyrics as $lyric)
        <tr>
            <td>{{ $lyric->id }}</td>
            <td><a href="{{ url('lyrics', $lyric->id)}}">{{ $lyric->title }}</a></td>
            @if(isset($lyric->album_id))
            <td><a href="{{ url('music', $lyric->album_id)}}">{{ $lyric->disco->title }}</a></td>
            @else
            <td></td>
            @endif
        </tr>
        @endforeach
    </tbody>
  </table>
  <div class=”pagination”>
    {!! $lyrics->links() !!}
  </div>
  <br>
</div>
@endsection