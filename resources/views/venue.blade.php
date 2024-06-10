@extends('layouts.app')
@section('content')
    <br>
    <div class="container-lg">
        <div class="parts-wrapper">
            <div class="pc">
                <small>会場検索結果</small>
                <h4>{{ $keyword }}</h4>
            </div>
            <div class="search">
                <form action="{{ url('/venue') }}" method="GET">
                    <div class="input-group mb-3" style="width: 350px;">
                        <input type="search" class="form-control" aria-label="Search" value="{{ request('keyword') }}"
                            name="keyword" required>
                        <div class="input-group-append">
                            <button class="btn btn-outline-secondary" type="submit" id="button-addon2"><i
                                    class="fa-solid fa-magnifying-glass"></i></button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <div class="error">
            @if ($data->isEmpty())
                <small>検索結果がありません。</small>
            @else
                <small>全{{ count($data) }}件</small>
            @endif
        </div>
        @if (!$data->isEmpty())
            <table class="table table-striped count">
                <thead>
                    <tr>
                        <th class="mobile">#</th>
                        <th class="mobile">開催日</th>
                        <th class="pc">アーティスト</th>
                        <th class="sp">アーティスト / タイトル</th>
                        <th class="mobile">タイトル</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($data as $result)
                        <tr>
                            <td></td>
                            <td class="td_search_date">{{ date('Y.m.d', strtotime($result->date)) }}</td>
                            @if (isset($result->artist_id))
                            <td class="pc">
                                <a href="{{ url('/setlists/artists', $result->artist_id) }}">{{ $result->artist->name }}</a>
                            </td>
                            <td class="sp">
                                <a href="{{ url('/setlists/artists', $result->artist_id) }}">{{ $result->artist->name }}</a> /
                                <a href="{{ route('setlists.show', $result->id) }}">{{ $result->title }}</a>
                            </td>
                        @else
                            <td class="pc"></td>
                            <td class="sp"><a
                                    href="{{ route('setlists.show', $result->id) }}">{{ $result->title }}</a></td>
                        @endif
                        <td class="pc"><a href="{{ route('setlists.show', $result->id) }}">{{ $result->title }}</a>
                        </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <br>
        @endif
        {{-- <div class=”pagination”>
            {!! $data->links() !!}
        </div> --}}
    </div>
@endsection
