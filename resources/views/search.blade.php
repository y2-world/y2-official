@extends('layouts.app')
@section('content')
<br>
<div class="container">
    <h4>検索結果 : {{$keyword}}</h4>
    @if($data->isEmpty())
        <p>検索結果がありません。</p>
    @endif 
    <table class="table table-striped">
        <thead>
          <tr>
            <th>#</th>
            <th>開催日</th>
            <th>アーティスト名</th>
            <th>ツアータイトル</th>
            <th>会場</th>
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
@endsection
