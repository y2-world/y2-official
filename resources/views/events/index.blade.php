@extends('layouts.app')
@section('content')
<br>
<div class="container-lg">
  <h2>Events</h2>
  <div class="parts-wrapper">
    <div class="dropdown-wrapper">
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
    <div class="search">
      <form action="{{url('/find')}}" method="GET">
        <div class="input-group mb-3">
            <input type="search" class="form-control" aria-label="Search" value="{{request('keyword')}}" name="keyword" required>
            <div class="input-group-append">
                <button class="btn btn-outline-secondary" type="submit" id="button-addon2">Search</button>
            </div>
        </div>
      </form>
    </div>
  </div>
  <table class="table table-striped">
      <thead>
        <tr>
          <th class="mb_list">#</th>
          <th class="mb_list">開催日</th>
          <th class="mb_list">タイトル</th>
          <th class="pc_list">会場</th>
        </tr>
      </thead>
      <div class="all-setlist">
        <tbody>
            @foreach ($events as $event)
              <tr>
                  <td>{{$event->id}}</td>
                  @if(isset($event->date1) && isset($event->date2))
                  <td>{{ date('Y.m.d', strtotime($event->date1)) }} - {{ date('Y.m.d', strtotime($event->date2)) }}</td>
                  @elseif(isset($event->date1) && !isset($event->date2))
                  <td>{{ date('Y.m.d', strtotime($event->date1)) }}</td>
                  @endif
                  <td><a href="{{ route('events.show', $event->id) }}">{{ $event->title }}</a></td>
                  <td class="pc_list">{{ $event->venue }}</td>
              </tr>
            @endforeach
        </tbody>
      </div>
    </table>
  <div class=”pagination”>
    {!! $events->links() !!}
  </div>
</div>
@endsection
