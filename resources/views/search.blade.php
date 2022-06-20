@extends('layouts.app')
@section('content')
<br>
<div class="container">
    <div class="parts-wrapper">
        <h4>検索結果 : {{$keyword}}</h4>
        <div class="search">
            <form class="d-flex" action="{{url('/search')}}" method="GET">
                <input class="form-control me-2" type="search" aria-label="Search" value="{{request('keyword')}}" name="keyword" required>
                <button class="btn btn-outline-success" type="submit">Search</button>
            </form>
        </div>
    </div>
    <div class="error">
        @if($data->isEmpty())
        <p>検索結果がありません。</p>
        @endif 
    </div>
    <div class="artist">
        <table class="table table-striped">
            <thead>
            <tr>
                <th class="mb_list">#</th>
                <th class="mb_list">開催日</th>
                <th class="mb_list">アーティスト</th>
                <th class="mb_list">ツアータイトル</th>
                <th class="pc_list">会場</th>
            </tr>
            </thead>
            <tbody>
                @foreach ($data as $result)
                <tr>
                    <td></td>
                    <td>{{ date('Y.m.d', strtotime($result->date)) }}</td>
                    <td><a href="{{ url('artists', $result->artist_id)}}">{{ $result->artist->name }}</a></td>
                    <td><a href="{{ route('setlists.show', $result->id) }}">{{ $result->tour_title }}</a></td>
                    <td class="pc_list">{{ $result->venue }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        {{-- <div class=”pagination”>
            {!! $data->links() !!}
        </div> --}}
    </div>
</div>
@endsection
