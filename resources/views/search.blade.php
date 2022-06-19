@extends('layouts.app')
@section('content')
<br>
<div class="container">
    <h4>検索結果 : {{$keyword}}</h4>
    @if($data->isEmpty())
        <p>検索結果がありません。</p>
    @endif 
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
                    <td>{{ $result->date }}</td>
                    <td><a href="{{ url('artists', $result->artist_id)}}">{{ $result->artist->name }}</a></td>
                    <td><a href="{{ route('setlists.show', $result->id) }}">{{ $result->tour_title }}</a></td>
                    <td>{{ $result->venue }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
