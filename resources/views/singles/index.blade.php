@extends('layouts.app')
@section('title', 'Yuki Official - Singles')
@section('content')
<br>
<div class="container-lg">
  <h2>Singles</h2>
  <div class="database-wrapper">
    <div class="dropdown-wrapper">
      <select name="select" onChange="location.href=value;">
        <option value="" disabled selected>Discography</option>
        <option value="{{ url('/database/songs') }}">Songs</option>
        <option value="{{ url('/database/albums') }}">Albums</option>
    </select>
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
        <option value="{{ url('/database/years', $bio->year)}}">{{ $bio->year }}</option>
        @endforeach
      </select>
    </div>
  </div>
  <table class="table table-striped">
      <thead>
        <tr>
          <th class="mobile">#</th>
          <th class="mobile">タイトル</th>
          <th class="mobile">リリース日</th>
        </tr>
      </thead>
      <tbody id="singles-container">
          @include('singles._list', ['singles' => $singles])
      </tbody>
    </table>
  <div class="pagination" id="pagination-links" style="display: none;">
    {!! $singles->onEachSide(5)->links() !!}
  </div>
  <br>
</div>
@endsection

@section('page-script')
    <script src="{{ asset('/js/infinite-scroll.js?v=20251101') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            @if($singles->hasMorePages())
                let nextUrl = '{!! $singles->nextPageUrl() !!}';
                if (window.location.protocol === 'https:') {
                    nextUrl = nextUrl.replace('http://', 'https://');
                }
                new InfiniteScroll({
                    container: '#singles-container',
                    nextPageUrl: nextUrl
                });
            @else
                // ページがない場合はページネーションを表示
                const pagination = document.getElementById('pagination-links');
                if (pagination) {
                    pagination.style.display = 'block';
                }
            @endif
        });
    </script>
@endsection