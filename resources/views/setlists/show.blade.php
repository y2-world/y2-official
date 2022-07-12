@extends('layouts.app')
@section('content')
<br>
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="setlist_artist">
                <a href="{{ url('artists', $setlists->artist_id)}}"><h5>{{ $setlists->artist->name  }}</h5></a>
            </div>
            <div class="setlist_title">{{ $setlists->tour_title }}</div>
            <div class="setlist_info">
                {{ date('Y.m.d', strtotime($setlists->date)) }}
                <br>
                {{ $setlists->venue }}
            </div>
            <hr>
            <div class="setlist">
                @if($setlists->fes == false)
                    @foreach ($setlists->setlist as $data) 
                        @if($data['#'] === '-')
                        {{ $data['#'] }} <b>{{ $data['song'] }}</b><br>
                        @else
                        {{ $data['#'] }}. {{ $data['song'] }}<br>
                        @endif
                    @endforeach
                    @if(isset($setlists->encore))
                        <hr width="250">
                        @foreach ((array)$setlists->encore as $data)
                        {{ $data['#'] }}. {{ $data['song'] }}<br>
                        @endforeach
                    @endif
                @elseif($setlists->fes == true)
                    @foreach ($setlist->fes_setlist as $data) 
                        @if($data['#'] === '-' && $data['song'] === '-')
                        {{ $data['artist'] }}<br>
                        @elseif($data['artist'] === '-')
                        {{ $data['#'] }}. {{ $data['song'] }}<br>
                        @elseif($data['artist'] === 'END')
                        {{ $data['#'] }}. {{ $data['song'] }}<br><br>
                        @endif
                    @endforeach
                    @if(isset($setlist->fes_encore))
                        <hr width="250">
                        @foreach ((array)$setlist->fes_encore as $data)
                            @if($data['#'] === '-' && $data['song'] === '-')
                            {{ $data['artist'] }}<br>
                            @elseif($data['artist'] === '-')
                            {{ $data['#'] }}. {{ $data['song'] }}<br>
                            @elseif($data['artist'] === 'END')
                            {{ $data['#'] }}. {{ $data['song'] }}<br><br>
                            @endif
                        @endforeach
                    @endif
                @endif
            </div>  
            <br>
            <div class="show_button">
                @if(isset($previous))
                <a class="btn btn-outline-dark" href="{{ route('setlists.show', $previous->id)}}" rel="prev" role="button"><</a>
                @endif
                @if(isset($next))
                <a class="btn btn-outline-dark" href="{{ route('setlists.show', $next->id)}}"rel="next" role="button">></a>
                @endif
            </div> 
        </div>
    </div>       
</div>
            
@endsection