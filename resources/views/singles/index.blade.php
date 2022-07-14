@extends('layouts.app')
@section('content')
<br>
<div class="container-lg">
  <h2>Singles</h2>
  <div class="parts-wrapper">
    <div class="dropdown-wrapper">
      <a class="btn btn-outline-dark btn-sm" href="{{ url('/songs') }}" role="button">Songs</a>
      <a class="btn btn-outline-dark btn-sm" href="{{ url('/albums') }}" role="button">Albums</a>
      <a class="btn btn-outline-dark btn-sm" href="{{ url('/tours') }}" role="button">Tours</a>
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
          <th class="mb_list">リリース日</th>
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
</div>
@endsection