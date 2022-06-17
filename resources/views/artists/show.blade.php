@extends('layouts.app')
@section('content')
<br>
<div class="container-lg">
    <h1>{{ $artist->name  }}</h1>
    <a class="btn btn-outline-dark btn-sm" href="{{ url('/setlists') }}" role="button">All</a>
    <div class="btn-group">
      <button class="btn btn-outline-dark btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
        Artists
      </button>
      <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton1">
        @foreach ($artists as $artist)
        <li><a class="dropdown-item" href="{{ url('/artists', $artist->id)}}">{{ $artist->name }}</a></li>
        @endforeach
      </ul>
    </div>
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
            <td><a href="{{ route('setlists.show', $setlist->id) }}">{{ $setlist->tour_title }}</a></td>
            <td>{{ $setlist->venue }}</td>
          </tr>
          @endforeach
        </tbody>
    </table>
</div>
@endsection