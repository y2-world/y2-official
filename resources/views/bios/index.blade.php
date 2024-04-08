<!-- jQuery -->
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>

<!-- Typeahead.js -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/corejs-typeahead/1.3.1/typeahead.bundle.min.js"></script>

<!-- スクリプトファイルの追加 -->
<script src="{{ asset('js/search.js') }}"></script>

@extends('layouts.app')
@section('content')
    <style>
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
    </style>
    <br>
    <div class="container">
        <div class="parts-wrapper">
            <h2>Mr.Children Database</h2>
            <div class="search">
                <form action="{{ url('/find') }}" method="GET">
                    <div class="input-group mb-3">
                        <input type="text" class="form-control" id="searchInput" aria-label="Search"
                            value="{{ request('keyword') }}" name="keyword" list="suggestions" required>
                        <datalist id="suggestions"></datalist>
                        <div class="input-group-append">
                            <button class="btn btn-outline-secondary" type="submit" id="button-addon2">Search</button>
                        </div>
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
                        <a class="btn btn-outline-dark btn-sm" href="{{ url('/tours') }}" role="button">Tours</a>
                        <a class="btn btn-outline-dark btn-sm" href="{{ url('/events') }}" role="button">Events</a>
                        <a class="btn btn-outline-dark btn-sm" href="{{ url('/apbankfes') }}" role="button">ap bank fes</a>
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
    <script>
        $(document).ready(function() {
            $('#searchInput').typeahead({
                source: function(query, result) {
                    $.ajax({
                        url: "{{ route('find.autocomplete') }}",
                        method: "GET",
                        data: {
                            query: query
                        },
                        dataType: "json",
                        success: function(data) {
                            result($.map(data, function(item) {
                                return item;
                            }));
                        }
                    });
                }
            });

            $('#searchButton').click(function() {
                var query = $('#searchInput').val();
                $.ajax({
                    url: "{{ route('find.autocomplete') }}",
                    method: "GET",
                    data: {
                        query: query
                    },
                    dataType: "json",
                    success: function(data) {
                        var html = '<ul>';
                        $.each(data, function(index, value) {
                            html += '<li>' + value + '</li>';
                        });
                        html += '</ul>';
                        $('#searchResults').html(html);
                    }
                });
            });
        });
    </script>
@endsection
