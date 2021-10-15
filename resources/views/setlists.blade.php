@extends('layouts.app')
@section('content')
<br>
<div class="container">
    <h1>ALL SETLISTS</h1>
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
            @foreach ($setlists as $setlist)
            <tr>
                <td></td>
                <td>{{ $setlist->date }}</td>
                <td><a href="{{ url('artists', $setlist->artist_id)}}">{{ $setlist->artist->name }}</a></td>
                <td><a href="{{ route('setlists.show', $setlist->id) }}">{{ $setlist->tour_title }}</a></td>
                <td>{{ $setlist->venue }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
