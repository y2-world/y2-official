@extends('layouts.app')
@section('content')
<br>
<div class="container">
  <h2>All Artists</h2>
  <select name="select" onChange="location.href=value;">
    <option value="" disabled selected>Years</option>
    @foreach ($years as $year)
    <option value="{{ url('/years', $year->id)}}">{{ $year->year }}</option>
    @endforeach
  </select>
  <table class="table table-striped">
    <thead>
      <tr>
        <th class="mb_list">#</th>
        <th class="mb_list">アーティスト</th>
      </tr>
    </thead>
    <tbody>
        @foreach ($artists as $artist)
        <tr>
            <td>{{ $artist->id }}</td>
            <td><a href="{{ url('artists', $artist->id)}}">{{ $artist->name }}</a></td>
        </tr>
        @endforeach
    </tbody>
  </table>
  <div class=”pagination”>
    {!! $artists->links() !!}
  </div>
  <br>
</div>
@endsection