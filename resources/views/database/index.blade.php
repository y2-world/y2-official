<!-- スクリプトファイルの追加 -->
{{-- <script src="{{ asset('js/search.js') }}"></script>  --}}

{{-- <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script> --}}

@extends('layouts.app')
@section('content')
    {{-- <style>
        /* Typeaheadのドロップダウンのスタイル */
        .tt-menu {
            background-color: white;
            border: 1px solid #ccc;
            border-radius: 4px;
            padding: 4px 0;
            z-index: 9999;
            /* ドロップダウンのz-indexを上に設定 */
        }

        .tt-suggestion {
            padding: 8px;
            cursor: pointer;
        }

        .tt-suggestion:hover {
            background-color: #f2f2f2;
        }
    </style> --}}
    <br>
    <div class="container">
        <div class="database-wrapper">
            <h2>Mr.Children Database</h2>
            {{-- <div class="search">
                <form action="{{ url('/find') }}" method="GET">
                    <div class="input-group mb-3">
                        <input type="text" id="searchInput" class="form-control" placeholder="Search">
                    </div>
                </form>
            </div> --}}
            <select name="select" onChange="location.href=value;">
                <option value="" disabled selected>Select song</option>
                @foreach ($songs as $song)
                <option value="{{ url('/database/songs/' . $song->id) }}">{{ $song->title }}</option>
                @endforeach
            </select>
            {{-- <div class="search">
                <form action="{{ url('/find') }}" method="GET">
                    <div class="input-group mb-3">
                        <select id="searchInput" class="form-control"></select>
                    </div>
                </form>
            </div> --}}
            {{-- <select class="js-example-basic-multiple" name="states[]" multiple="multiple">
                <option value="AL">Alabama</option>
                  ...
                <option value="WY">Wyoming</option>
              </select> --}}
        </div>
        <hr>
        <h4 class="bio">Discography</h4>
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="year-wrapper">
                    <div class="year-padding">
                        <a class="btn btn-outline-dark btn-sm" href="{{ url('/database/songs') }}" role="button">Songs</a>
                        <a class="btn btn-outline-dark btn-sm" href="{{ url('/database/singles') }}" role="button">Singles</a>
                        <a class="btn btn-outline-dark btn-sm" href="{{ url('/database/albums') }}" role="button">Albums</a>
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
                        <a class="btn btn-outline-dark btn-sm" href="{{ url('/database/live?type=1') }}" role="button">Tours</a>
                        <a class="btn btn-outline-dark btn-sm" href="{{ url('/database/live?type=2') }}" role="button">Events</a>
                        <a class="btn btn-outline-dark btn-sm" href="{{ url('/database/live?type=3') }}" role="button">ap bank fes</a>
                        <a class="btn btn-outline-dark btn-sm" href="{{ url('/database/live?type=4') }}" role="button">Solo</a>
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
    {{-- <script>
   $(document).ready(function() {
        $('.js-example-basic-single').select2();
    });
    </script> --}}
@endsection
