@extends('layouts.app')
@section('content')
<br>
<div class="container">
  <h2>All Years</h2>
  <select name="select" onChange="location.href=value;">
    <option value="" disabled selected>Artists</option>
    <option value="{{ url('/artists')}}">All Artists</option>
    @foreach ($artists as $artist)
    <option value="{{ url('/artists', $artist->id)}}">{{ $artist->name }}</option>
    @endforeach
  </select>
  <table class="table table-striped">
    <thead>
      <tr>
        <th class="mb_list">#</th>
        <th class="mb_list">年</th>
      </tr>
    </thead>
    <tbody>
        @foreach ($years as $year)
        <tr>
            <td>{{ $year->id }}</td>
            <td><a href="{{ url('years', $year->id)}}">{{ $year->year }}</a></td>
        </tr>
        @endforeach
    </tbody>
  </table>
  <div class=”pagination”>
    {!! $years->links() !!}
  </div>
</div>
@endsection