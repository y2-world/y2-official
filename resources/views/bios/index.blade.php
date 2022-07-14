@extends('layouts.app')
@section('content')
<br>
<div class="container">
  <h2>Mr.Children Database</h2>
  <div class="parts-wrapper">
    <div class="dropdown-wrapper">
      <a class="btn btn-outline-dark btn-sm" href="{{ url('/songs') }}" role="button">Songs</a>
      <a class="btn btn-outline-dark btn-sm" href="{{ url('/singles') }}" role="button">Singles</a>
      <a class="btn btn-outline-dark btn-sm" href="{{ url('/albums') }}" role="button">Albums</a>
    </div>
    <div class="search">
      <form action="{{url('/search')}}" method="GET">
        <div class="input-group mb-3">
            <input type="search" class="form-control" aria-label="Search" value="{{request('keyword')}}" name="keyword" required>
            <div class="input-group-append">
                <button class="btn btn-outline-secondary" type="submit" id="button-addon2">Search</button>
            </div>
        </div>
      </form>
    </div>
  </div>
  <hr>
  <h4 class="bio">Biography</h4>
  <div class="row justify-content-center">
    <div class="col-md-6">
      <div class="year-wrapper">
          @foreach ($bios as $bio)
          <div class="year-padding">
          <a class="btn btn-outline-dark btn-sm" href="{{ url('bios', $bio->id)}}" role="button">{{ $bio->year }}</a>
          </div>
          @endforeach
      </div>
    </div>
  </div>
</div>
@endsection