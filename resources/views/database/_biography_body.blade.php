{{-- Biography の年のページの中身（その年に初めて出た曲と、その年のツアー）。公式の Database とマイページで共通。
     $songUrl・$discUrl・$tourUrl・$songNumber は、曲・シングル／アルバム・ツアーのリンクと曲番を返す関数 --}}
    <div class="container database-year-content">
        <div class="database-row">
            @if (!$songs->isEmpty())
                <div class="{{ $tours->isEmpty() ? 'column' : 'column1' }}">
                    <table class="table table-striped {{ $tours->isEmpty() ? '' : 'songs' }}">
                    <thead>
                        <tr>
                            <th class="mobile">#</th>
                            <th class="mobile">タイトル</th>
                            <th class="mobile">シングル / アルバム</th>
                            <th class="pc">リリース日</th>
                        </tr>
                    </thead>
                    <h5>Songs</h5>
                    <tbody>
                        @foreach ($songs as $song)
                            @php
                                $single = $song->singleFromTracklist;
                                $album = $song->albumFromTracklist;
                                $number = $songNumber($song);
                            @endphp
                            <tr>
                                <td>{{ $number }}</td>
                                <td><a href="{{ $songUrl($song) }}">{{ $song->title }}</a></td>
                                @if ($single && $album)
                                    @if ($single->date > $album->date)
                                        <td><a href="{{ $discUrl($album) }}">{{ $album->title }}</a></td>
                                        <td class="pc">{{ date('Y.m.d', strtotime($album->date)) }}</td>
                                    @else
                                        <td><a href="{{ $discUrl($single) }}">{{ $single->title }}</a></td>
                                        <td class="pc">{{ date('Y.m.d', strtotime($single->date)) }}</td>
                                    @endif
                                @elseif($album)
                                    <td><a href="{{ $discUrl($album) }}">{{ $album->title }}</a></td>
                                    <td class="pc">{{ date('Y.m.d', strtotime($album->date)) }}</td>
                                @elseif($single)
                                    <td><a href="{{ $discUrl($single) }}">{{ $single->title }}</a></td>
                                    <td class="pc">{{ date('Y.m.d', strtotime($single->date)) }}</td>
                                @else
                                    <td></td>
                                    <td class="pc"></td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                    </table>
                </div>
            @endif
            <div class="{{ $songs->isEmpty() ? 'column' : 'column2-bio' }}">
                @if (!$tours->isEmpty())
                    @php
                        $hasTourEvent = false;
                        $hasSolo = false;
                    @endphp

                    @foreach ($tours as $tour)
                        @if ($tour->type == 0 || $tour->type != 4)
                            @php $hasTourEvent = true; @endphp
                        @else
                            @php $hasSolo = true; @endphp
                        @endif
                    @endforeach

                    @if ($hasTourEvent)
                        <table class="table table-striped count tour">
                            <thead>
                                <tr>
                                    <th class="mobile">#</th>
                                    <th class="mobile">開催日</th>
                                    <th class="mobile">タイトル</th>
                                </tr>
                            </thead>
                            <tbody>
                                <h5>Tour / Event</h5>
                                @foreach ($tours as $tour)
                                    @if ($tour->type == 0 || $tour->type != 4)
                                        <tr>
                                            <td></td>
                                            @if (isset($tour->date1) && isset($tour->date2))
                                                <td class="td_date">{{ date('Y.m.d', strtotime($tour->date1)) }} - {{ date('Y.m.d', strtotime($tour->date2)) }}</td>
                                            @elseif(isset($tour->date1) && !isset($tour->date2))
                                                <td class="td_date">{{ date('Y.m.d', strtotime($tour->date1)) }}</td>
                                            @endif
                                            <td class="td_title"><a href="{{ $tourUrl($tour) }}">{{ $tour->title }}</a></td>
                                        </tr>
                                    @endif
                                @endforeach
                            </tbody>
                        </table>
                        <br>
                    @endif

                    @if ($hasSolo)
                        <table class="table table-striped count solo">
                            <thead>
                                <tr>
                                    <th class="mobile">#</th>
                                    <th class="mobile">開催日</th>
                                    <th class="mobile">タイトル</th>
                                </tr>
                            </thead>
                            <tbody>
                                <h5>Solo</h5>
                                @foreach ($tours as $tour)
                                    @if ($tour->type == 4)
                                        <tr>
                                            <td></td>
                                            @if (isset($tour->date1) && isset($tour->date2))
                                                <td class="td_date">{{ date('Y.m.d', strtotime($tour->date1)) }} - {{ date('Y.m.d', strtotime($tour->date2)) }}</td>
                                            @elseif(isset($tour->date1) && !isset($tour->date2))
                                                <td class="td_date">{{ date('Y.m.d', strtotime($tour->date1)) }}</td>
                                            @endif
                                            <td class="td_title"><a href="{{ $tourUrl($tour) }}">{{ $tour->title }}</a></td>
                                        </tr>
                                    @endif
                                @endforeach
                            </tbody>
                        </table>
                        <br>
                    @endif
                @endif
            </div>
        </div>
    </div>
