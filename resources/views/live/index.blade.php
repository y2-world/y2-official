@extends('layouts.app')
@section('content')
    <br>
    <div class="container">
        @if (request('type') == 1)
            <h2>Tours</h2>
        @elseif(request('type') == 2)
            <h2>Events</h2>
        @elseif(request('type') == 3)
            <h2>ap bank fes</h2>
        @elseif(request('type') == 4)
            <h2>Solo</h2>
        @else
            <h2>Live</h2>
        @endif
        <div class="parts-wrapper">
            <div class="dropdown-wrapper">
                <select name="select" onChange="location.href=value;">
                    <option value="" disabled selected>Live</option>
                    <option value="{{ url('/live') }}">All</option>
                    <option value="{{ url('/live?type=1') }}">Tours</option>
                    <option value="{{ url('/live?type=2') }}">Events</option>
                    <option value="{{ url('/live?type=3') }}">ap bank fes</option>
                    <option value="{{ url('/live?type=4') }}">Solo</option>
                </select>
                <select name="select" onChange="location.href=value;">
                    <option value="" disabled selected>Years</option>
                    @foreach ($bios as $bio)
                        <option value="{{ url('/bios', $bio->id) }}">{{ $bio->year }}</option>
                    @endforeach
                </select>
            </div>
            {{-- <div class="search">
      <form action="{{url('/find')}}" method="GET">
          <div class="input-group mb-3">
              <input type="text" class="form-control" id="searchInput" aria-label="Search" value="{{request('keyword')}}" name="keyword"
                  list="suggestions" required>
              <datalist id="suggestions"></datalist>
              <div class="input-group-append">
                  <button class="btn btn-outline-secondary" type="submit" id="button-addon2">Search</button>
              </div>
          </div>
      </form>
    </div> --}}
            <div>
                <select name="select" onChange="location.href=value;">
                    <option value="" disabled selected>Select song</option>
                    @foreach ($songs as $song)
                        <option value="{{ url('songs/' . $song->id) }}">{{ $song->title }}</option>
                    @endforeach
                </select>
                <div class="row">
                  <div class="col-md-3">
                      <div class="form-check">
                          <input class="form-check-input" type="radio" name="include" id="allRadio" value="1"
                              checked>
                          <label class="form-check-label" for="allRadio">All</label>
                      </div>
                  </div>
                  <div class="col-md-9">
                      <div class="form-check">
                          <input class="form-check-input" type="radio" name="include" id="includeSoloRadio"
                              value="2">
                          <label class="form-check-label" for="includeSoloRadio">Not Include Solo</label>
                      </div>
                  </div>
              </div>
            </div>
        </div>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th class="mb_list">#</th>
                    <th class="mb_list">開催日</th>
                    <th class="mb_list">タイトル</th>
                    <th class="pc_list">会場</th>
                </tr>
            </thead>
            <div class="all-setlist">
                <tbody>
                    @foreach ($tours as $tour)
                        <tr>
                            @if (request('type') == 1)
                                <td>{{ $tour->tour_id }}</td>
                            @elseif(request('type') == 2)
                                <td>{{ $tour->event_id }}</td>
                            @elseif(request('type') == 3)
                                <td>{{ $tour->ap_id }}</td>
                            @elseif(request('type') == 4)
                                <td>{{ $tour->solo_id }}</td>
                            @else
                                <td>{{ $tour->id }}</td>
                            @endif
                            @if (isset($tour->date1) && isset($tour->date2))
                                <td class="td_date">{{ date('Y.m.d', strtotime($tour->date1)) }} -
                                    {{ date('Y.m.d', strtotime($tour->date2)) }}</td>
                            @elseif(isset($tour->date1) && !isset($tour->date2))
                                <td class="td_date">{{ date('Y.m.d', strtotime($tour->date1)) }}</td>
                            @endif
                            <td class="td_title"><a href="{{ route('tours.show', $tour->id) }}">{{ $tour->title }}</a>
                            </td>
                            <td class="pc_list">{{ $tour->venue }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </div>
        </table>
        <div class=”pagination”>
            {!! $tours->links() !!}
        </div>
        <br>
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
