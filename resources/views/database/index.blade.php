@extends('layouts.app')
@section('title', 'Yuki Official - Database')
@section('content')
    <div class="database-hero">
        <div class="container">
            <h1 class="database-title">Mr.Children Database</h1>
            <p class="database-subtitle">Explore the complete discography, live performances, and biography</p>
            <div class="database-search">
                <form action="" method="GET">
                    <div class="search-wrapper">
                        <input type="text" id="searchInput" class="database-search-input typeahead" placeholder="Search songs, albums, or tours..." required>
                        <i class="fa-solid fa-magnifying-glass search-icon"></i>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="container database-content">
        <div class="row">
            <!-- Discography Card -->
            <div class="col-md-4 mb-4">
                <div class="database-card">
                    <div class="card-icon">
                        <i class="fa-solid fa-music"></i>
                    </div>
                    <h3 class="card-title">Discography</h3>
                    <p class="card-description">Browse through all songs, singles, and albums</p>
                    <div class="card-links">
                        <a href="{{ url('/database/songs') }}" class="database-link">
                            <span>Songs</span>
                            <i class="fa-solid fa-arrow-right"></i>
                        </a>
                        <a href="{{ url('/database/singles') }}" class="database-link">
                            <span>Singles</span>
                            <i class="fa-solid fa-arrow-right"></i>
                        </a>
                        <a href="{{ url('/database/albums') }}" class="database-link">
                            <span>Albums</span>
                            <i class="fa-solid fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Live Card -->
            <div class="col-md-4 mb-4">
                <div class="database-card">
                    <div class="card-icon">
                        <i class="fa-solid fa-guitar"></i>
                    </div>
                    <h3 class="card-title">Live</h3>
                    <p class="card-description">Discover all tours, events, and performances</p>
                    <div class="card-links">
                        <a href="{{ url('/database/live') }}" class="database-link">
                            <span>All</span>
                            <i class="fa-solid fa-arrow-right"></i>
                        </a>
                        <a href="{{ url('/database/live?type=1') }}" class="database-link">
                            <span>Tours</span>
                            <i class="fa-solid fa-arrow-right"></i>
                        </a>
                        <a href="{{ url('/database/live?type=2') }}" class="database-link">
                            <span>Events</span>
                            <i class="fa-solid fa-arrow-right"></i>
                        </a>
                        <a href="{{ url('/database/live?type=3') }}" class="database-link">
                            <span>ap bank fes</span>
                            <i class="fa-solid fa-arrow-right"></i>
                        </a>
                        <a href="{{ url('/database/live?type=4') }}" class="database-link">
                            <span>Solo</span>
                            <i class="fa-solid fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Biography Card -->
            <div class="col-md-4 mb-4">
                <div class="database-card">
                    <div class="card-icon">
                        <i class="fa-solid fa-book"></i>
                    </div>
                    <h3 class="card-title">Biography</h3>
                    <p class="card-description">Explore the history year by year</p>
                    <div class="card-links card-links-grid">
                        @foreach ($bios as $bio)
                            <a href="{{ url('/database/years', $bio->year) }}" class="database-link-year">
                                {{ $bio->year }}
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('page-script')
<script src="{{ asset('/js/search.js?time=' . time()) }}"></script>
@endsection
