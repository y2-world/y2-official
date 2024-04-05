@extends('layouts.app')
@section('content')
<br>
<div class="container-lg">
    <div class="parts-wrapper">
        <div class="pc_list">
            <h4>検索結果 : {{$query}}</h4>
        </div>
        <div class="search">
            <form action="{{url('/find')}}" method="GET">
                <div class="input-group mb-3">
                    <input type="text" class="form-control" id="searchInput" aria-label="Search" value="{{request('keyword')}} name="keyword"
                        list="suggestions" required>
                    <datalist id="suggestions"></datalist>
                    <div class="input-group-append">
                        <button class="btn btn-outline-secondary" type="submit" id="button-addon2">Search</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <div class="error">
        @if($tours->isEmpty())
        <p>検索結果がありません。</p>
        @else
        <p>全{{count($tours)}}件</p>
        @endif 
    </div>
    @if(!$tours->isEmpty())
        <table class="table table-striped count">
            <thead>
            <tr>
                <th class="mb_list">#</th>
                <th class="mb_list">開催日</th>
                <th class="mb_list">タイトル</th>
            </tr>
            </thead>
            <tbody>
                @foreach ($tours as $result)
                <tr>
                    <td></td>
                    @if(isset($result->date1) && isset($result->date2))
                    <td class="td_date">{{ date('Y.m.d', strtotime($result->date1)) }} - {{ date('Y.m.d', strtotime($result->date2)) }}</td>
                    @elseif(isset($result->date1) && !isset($result->date2))
                    <td class="td_date">{{ date('Y.m.d', strtotime($result->date1)) }}</td>
                    @endif
                    <td class="td_title"><a href="{{ route('tours.show', $result->id) }}">{{ $result->title }}</a></td>
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
<script>
    const searchInput = document.getElementById('searchInput');
    const suggestionsList = document.getElementById('suggestions');

    searchInput.addEventListener('input', function() {
        const query = this.value.toLowerCase();
        suggestionsList.innerHTML = '';

        fetch(`/find/suggestions?query=${query}`)
            .then(response => response.json())
            .then(data => {
                data.forEach(song => {
                    const option = document.createElement('option');
                    option.value = song;
                    suggestionsList.appendChild(option);
                });
            })
            .catch(error => console.error('Error:', error));
    });
</script>
@endsection
