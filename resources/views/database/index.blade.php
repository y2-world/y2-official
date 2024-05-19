@extends('layouts.app')
@section('content')
    <br>
    <div class="container">
        <div class="parts-wrapper">
            <h2>Mr.Children Database</h2>
            <div class="search">
                <form action="" method="GET">
                    <div class="input-group">
                        <input type="text" id="searchInput" class="form-control typeahead" placeholder="Search" style="width: 300px;" required>
                    </div>
                </form>
            </div>
        </div>
        <hr>
        <h4 class="bio">Discography</h4>
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="year-wrapper">
                    <div class="year-padding">
                        <a class="btn btn-outline-dark btn-sm" href="{{ url('/database/songs') }}" role="button">Songs</a>
                        <a class="btn btn-outline-dark btn-sm" href="{{ url('/database/singles') }}"
                            role="button">Singles</a>
                        <a class="btn btn-outline-dark btn-sm" href="{{ url('/database/albums') }}"
                            role="button">Albums</a>
                    </div>
                </div>
            </div>
        </div>
        <br>
        <h4 class="bio">Live</h4>
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="year-wrapper">
                    <div class="year-padding">
                        <a class="btn btn-outline-dark btn-sm" href="{{ url('/database/live') }}" role="button">All</a>
                        <a class="btn btn-outline-dark btn-sm" href="{{ url('/database/live?type=1') }}"
                            role="button">Tours</a>
                        <a class="btn btn-outline-dark btn-sm" href="{{ url('/database/live?type=2') }}"
                            role="button">Events</a>
                        <a class="btn btn-outline-dark btn-sm" href="{{ url('/database/live?type=3') }}" role="button">ap
                            bank fes</a>
                        <a class="btn btn-outline-dark btn-sm" href="{{ url('/database/live?type=4') }}"
                            role="button">Solo</a>
                    </div>
                </div>
            </div>
        </div>
        <br>
        <h4 class="bio">Biography</h4>
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="year-wrapper">
                    @foreach ($bios as $bio)
                        <div class="year-padding">
                            <a class="btn btn-outline-dark btn-sm" href="{{ url('/database/years', $bio->year) }}"
                                role="button">{{ $bio->year }}</a>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
        <br>
    </div>
@endsection
