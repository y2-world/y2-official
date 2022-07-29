@extends('layouts.app')
@section('content')
<br>
<div class="container-lg">
    <div class="parts-wrapper">
        <div class="pc_list">
            <h4>検索結果 : {{$keyword}}</h4>
        </div>
        <div class="search">
            <form action="{{url('/find')}}" method="GET">
                <div class="input-group mb-3">
                    <input type="search" class="form-control" aria-label="Search" value="{{request('keyword')}}" name="keyword" required>
                    <div class="input-group-append">
                        <button class="btn btn-outline-secondary" type="submit" id="button-addon2">Search</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <div class="error">
        @if($data->isEmpty())
        <p>検索結果がありません。</p>
        @else
        <p>全{{count($data)}}件</p>
        @endif 
    </div>
    <div class="artist">
        <table class="table table-striped">
            <thead>
            <tr>
                <th class="mb_list">#</th>
                <th class="mb_list">開催日</th>
                <th class="mb_list">タイトル</th>
            </tr>
            </thead>
            <tbody>
                @foreach ($data as $result)
                <tr>
                    <td></td>
                    @if(isset($result->date1) && isset($result->date2))
                    <td>{{ date('Y.m.d', strtotime($result->date1)) }} - {{ date('Y.m.d', strtotime($result->date2)) }}</td>
                    @elseif(isset($result->date1) && !isset($result->date2))
                    <td>{{ date('Y.m.d', strtotime($result->date1)) }}</td>
                    @endif
                    <td><a href="{{ route('tours.show', $result->id) }}">{{ $result->title }}</a></td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <div class=”pagination”>
            {!! $data->links() !!}
        </div>
    </div>
</div>
@endsection
