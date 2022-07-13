@extends('layouts.app')
@section('content')
<br>
<div class="container-lg">
  <h2>Singles</h2>
  <a class="btn btn-outline-dark btn-sm" href="{{ url('/songs') }}" role="button">Songs</a>
  <a class="btn btn-outline-dark btn-sm" href="{{ url('/singles') }}" role="button">Singles</a>
  <a class="btn btn-outline-dark btn-sm" href="{{ url('/albums') }}" role="button">Albums</a>
  <div class="btn-group">
    <button class="btn btn-outline-dark btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
      Years
    </button>
    <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton1">
      <li><a class="dropdown-item" href="{{ url('/bios')}}">All Years</a></li>
      <div class="dropdown-divider"></div>
      @foreach ($bios as $bio)
      <li><a class="dropdown-item" href="{{ url('/bios', $bio->id)}}">{{ $bio->year }}</a></li>
      @endforeach
    </ul>
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