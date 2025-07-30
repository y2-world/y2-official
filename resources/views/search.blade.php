@extends('layouts.app')
@section('title', 'Yuki Official - Search : ' . $keyword)
@section('content')
    <br>
    <div class="container-lg">
        <div class="parts-wrapper">
            <div class="pc">
                <small>楽曲検索結果</small>
                <h4>{{ $keyword }}</h4>
            </div>
            <div class="search">
                <form action="{{ url('/search') }}" method="GET">
                    <div class="mb_dropdown">
                        @if (isset($artist_id))
                            <select name="artist_id" data-toggle="select">
                                <option value="">(No Artist Selected)</option>
                                <?php $artist_name = $artists[$artist_id - 1]['name']; ?>
                                @foreach ($artists as $artist)
                                    @if ($artist->name !== $artist_name)
                                        <option value="{{ $artist->id }}">{{ $artist->name }}</option>
                                    @else
                                        <option value="{{ $artist->id }}" selected>{{ $artist->name }}</option>
                                    @endif
                                @endforeach
                            </select>
                        @else
                            <select name="artist_id" data-toggle="select">
                                <option value="">(No Artist Selected)</option>
                                @foreach ($artists as $artist)
                                    <option value="{{ $artist->id }}">{{ $artist->name }}</option>
                                @endforeach
                            </select>
                        @endif
                    </div>

                    {{-- 完全一致・部分一致切替 --}}
                    <div class="mb_dropdown">
                        <label class="small-label">
                            <input type="radio" name="match_type" value="exact"
                                {{ request('match_type', 'exact') === 'exact' ? 'checked' : '' }}>
                            完全一致
                        </label>
                        <label class="small-label" style="margin-left: 20px;">
                            <input type="radio" name="match_type" value="partial"
                                {{ request('match_type') === 'partial' ? 'checked' : '' }}>
                            部分一致
                        </label>
                    </div>

                    <div class="input-group mb-3" style="width: 350px;">
                        <input type="search" class="form-control" aria-label="Search" value="{{ request('keyword') }}"
                            name="keyword" required>
                        <div class="input-group-append">
                            <button class="btn btn-outline-secondary" type="submit" id="button-addon2">
                                <i class="fa-solid fa-magnifying-glass"></i>
                            </button>
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
                        <th class="mobile">タイトル</th>
                        <th class="pc">会場</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($data as $result)
                        <tr>
                            <td></td>
                            <td class="td_search_date">{{ date('Y.m.d', strtotime($result->date)) }}</td>
                            <td class="td_search_title"><a
                                    href="{{ route('setlists.show', $result->id) }}">{{ $result->title }}</a></td>
                            <td class="pc"><a
                                    href="{{ url('/venue?keyword=' . urlencode($result->venue)) }}">{{ $result->venue }}</a>
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

@section('page-script')
    <script src="{{ asset('/js/search.js?time=' . time()) }}"></script>
@endsection
