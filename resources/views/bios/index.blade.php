@extends('layouts.app')
@section('content')
<br>
<div class="container">
  <h2>All Years</h2>
  <div class="parts_wrapper">
    <a class="btn btn-outline-dark btn-sm" href="{{ url('/songs') }}" role="button">Songs</a>
    <a class="btn btn-outline-dark btn-sm" href="{{ url('/singles') }}" role="button">Singles</a>
    <a class="btn btn-outline-dark btn-sm" href="{{ url('/albums') }}" role="button">Albums</a>
    <select name="select" onChange="location.href=value;">
      <option value="" disabled selected>Years</option>
      @foreach ($bios as $bio)
      <option value="{{ url('/bios', $bio->id)}}">{{ $bio->year }}</option>
      @endforeach
    </select>
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
  <div class=”pagination”>
    {!! $bios->links() !!}
  </div>
</div>
@endsection