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
            <div class="all-setlist">
                <tbody>
                    @foreach ($tours as $tour)
                        <tr>
                            @if (request('type') == 1)
                                <td>{{ $tour->tour_id }}</td>
                            @elseif(request('type') == 2)
                                <td>{{ $tour->event_id }}</td>
                            @elseif(request('type') == 3)
                                <td>{{ $tour->ap_id }}</td>
                            @elseif(request('type') == 4)
                                <td>{{ $tour->solo_id }}</td>
                            @else
                                <td>{{ $tour->id }}</td>
                            @endif
                            @if (isset($tour->date1) && isset($tour->date2))
                                <td class="td_date">{{ date('Y.m.d', strtotime($tour->date1)) }} -
                                    {{ date('Y.m.d', strtotime($tour->date2)) }}</td>
                            @elseif(isset($tour->date1) && !isset($tour->date2))
                                <td class="td_date">{{ date('Y.m.d', strtotime($tour->date1)) }}</td>
                            @endif
                            <td class="td_title"><a href="{{ route('live.show', $tour->id) }}">{{ $tour->title }}</a>
                            </td>
                            <td class="pc_venue"><a href="{{ url('/venue?keyword='.urlencode($tour->venue)) }}">{{ $tour->venue }}</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </div>
        </table>
        <div class=”pagination”>
            {!! $tours->appends(['type' => $type])->links() !!}
        </div>
        <br>
    </div>
@endsection
