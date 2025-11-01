@extends('layouts.app')
@section('title', 'Yuki Official - Live')
@section('content')
    <br>
    <div class="container">
        @if (request('type') == 1)
            <h2>Tours</h2>
        @elseif(request('type') == 2)
            <h2>Events</h2>
        @elseif(request('type') == 3)
            <h2>ap bank fes</h2>
        @elseif(request('type') == 4)
            <h2>Solo</h2>
        @else
            <h2>Live</h2>
        @endif
        <div class="parts-wrapper">
            <div class="dropdown-wrapper">
                <select name="select" onChange="location.href=value;">
                    <option value="" disabled selected>Live</option>
                    <option value="{{ url('/database/live') }}">All</option>
                    <option value="{{ url('/database/live?type=1') }}">Tours</option>
                    <option value="{{ url('/database/live?type=2') }}">Events</option>
                    <option value="{{ url('/database/live?type=3') }}">ap bank fes</option>
                    <option value="{{ url('/database/live?type=4') }}">Solo</option>
                </select>
                <select name="select" onChange="location.href=value;">
                    <option value="" disabled selected>Years</option>
                    @foreach ($bios as $bio)
                        <option value="{{ url('/database/years', $bio->year) }}">{{ $bio->year }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th class="mobile">#</th>
                    <th class="mobile">開催日</th>
                    <th class="mobile">タイトル</th>
                    <th class="pc">会場</th>
                </tr>
            </thead>
            <tbody id="tours-container">
                @include('live._list', ['tours' => $tours])
            </tbody>
        </table>
        <div class="pagination" id="pagination-links">
            {!! $tours->appends(['type' => $type])->links() !!}
        </div>
        <br>
    </div>
@endsection

@section('page-script')
    <script src="{{ asset('/js/infinite-scroll.js?v=20251101') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            @if($tours->hasMorePages())
                new InfiniteScroll({
                    container: '#tours-container',
                    nextPageUrl: '{!! $tours->appends(['type' => $type])->nextPageUrl() !!}'
                });
            @endif
        });
    </script>
@endsection
