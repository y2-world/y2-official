@extends('layouts.app')
@section('content')
<br>
<div class="container">
  <h2>All Years</h2>
  <div class="parts_wrapper">
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
  </div>
  <table class="table table-striped">
    <thead>
      <tr>
        <th class="mb_list">#</th>
        <th class="mb_list">年</th>
      </tr>
    </thead>
    <tbody>
        @foreach ($bios as $bio)
        <tr>
            <td>{{ $bio->id }}</td>
            <td><a href="{{ url('bios', $bio->id)}}">{{ $bio->year }}</a></td>
        </tr>
        @endforeach
    </tbody>
  </table>
</div>
@endsection