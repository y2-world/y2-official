{{-- <!-- Bootstrap JS -->
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
<!-- Typeahead.js -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/corejs-typeahead/1.3.1/typeahead.bundle.min.js"></script>

<!-- スクリプトファイルの追加 -->
<script src="{{ asset('js/search.js') }}"></script> --}}

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
            <div>
                <select name="select" onChange="location.href=value;">
                    <option value="" disabled selected>Select song</option>
                    @foreach ($songs as $song)
                    <option value="{{ url('songs/' . $song->id) }}">{{ $song->title }}</option>
                    @endforeach
                </select>
                <div class="row">
                    <div class="col-md-3">
                      <div class="form-check">
                        <input class="form-check-input" type="radio" name="include" id="allRadio" value="1" checked>
                        <label class="form-check-label" for="allRadio">All</label>
                      </div>
                    </div>
                    <div class="col-md-9">
                      <div class="form-check">
                        <input class="form-check-input" type="radio" name="include" id="includeSoloRadio" value="2">
                        <label class="form-check-label" for="includeSoloRadio">Not Include Solo</label>
                    </div>
                    </div>
                  </div>
            </div>
        </div>
        <hr>
        <h4 class="bio">Discography</h4>
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="year-wrapper">
                    <div class="year-padding">
                        <a class="btn btn-outline-dark btn-sm" href="{{ url('/songs') }}" role="button">Songs</a>
                        <a class="btn btn-outline-dark btn-sm" href="{{ url('/singles') }}" role="button">Singles</a>
                        <a class="btn btn-outline-dark btn-sm" href="{{ url('/albums') }}" role="button">Albums</a>
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
                        <a class="btn btn-outline-dark btn-sm" href="{{ url('/live') }}" role="button">All</a>
                        <a class="btn btn-outline-dark btn-sm" href="{{ url('/live?type=1') }}" role="button">Tours</a>
                        <a class="btn btn-outline-dark btn-sm" href="{{ url('/live?type=2') }}" role="button">Events</a>
                        <a class="btn btn-outline-dark btn-sm" href="{{ url('/live?type=3') }}" role="button">ap bank fes</a>
                        <a class="btn btn-outline-dark btn-sm" href="{{ url('/live?type=4') }}" role="button">Solo</a>
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
                            <a class="btn btn-outline-dark btn-sm" href="{{ url('bios', $bio->id) }}"
                                role="button">{{ $bio->year }}</a>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
        <br>
    </div>
@endsection
