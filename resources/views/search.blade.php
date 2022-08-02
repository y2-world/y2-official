@extends('layouts.app')
@section('content')
<br>
<div class="container-lg">
    <div class="parts-wrapper">
        <div class="pc_list">
            @if (isset($artist_id)) 
            <h4>検索結果 : {{$keyword}}</h4>
            @endif
        </div>
        <div class="search">
            <form action="{{url('/search')}}" method="GET">
            <div class="mb_dropdown">
                @if (isset($artist_id)) 
                    <select name="artist_id" data-toggle="select">
                        <?php $artist_name = $artists[$artist_id - 1]['name']; ?>
                        @foreach ($artists as $artist)
                            @if($artist->name !== $artist_name)
                            <option value="{{ $artist->id }}">{{$artist->name}}</option>
                            @else
                            <option value="{{ $artist->id }}" selected>{{$artist->name}}</option>
                            @endif
                        @endforeach
                    </select>
                @else
                    <select name="artist_id" data-toggle="select">
                        <option hidden>Artists</option>
                        @foreach ($artists as $artist)
                            <option value="{{ $artist->id }}">{{$artist->name}}</option>
                        @endforeach
                        @endif
                    </select>
            </div>
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
        @endif 
    </div>
    <div class="artist">
        <table class="table table-striped">
            <thead>
            <tr>
                <th class="mb_list">#</th>
                <th class="mb_list">開催日</th>
                 <th class="mb_list">ツアータイトル</th>
                <th class="pc_list">会場</th>
            </tr>
            </thead>
            <tbody>
                @foreach ($data as $result)
                <tr>
                    <td></td>
                    <td>{{ date('Y.m.d', strtotime($result->date)) }}</td>
                    <td><a href="{{ route('setlists.show', $result->id) }}">{{ $result->title }}</a></td>
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
