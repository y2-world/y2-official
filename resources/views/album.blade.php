@extends('layouts.app')
@section('content')
<div class="mt-4"></div>
<div class="disco">
    {{-- <div class="container"> --}}
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div id="mask" class="hide"></div>
                <h2 class="music-header">Music</h2>
                    <ul class="music-menu">
                        <li><a href="{{ url('/music') }}">Info</a></li>
                        <li><a href="{{ url('/music/single') }}">Single</a></li>
                        <li><a href="{{ url('/music/album') }}" class="active">Album</a></li>
                    </ul>
                    <hr>
                    <div class="element js-fadein">
                        <div class="disco-wrapper">
                            {{-- <div class="container"> --}}
                                <div class="album-wrapper">
                                    <div class="single">
                                        <img src={{ asset('images/album_image4.jpg') }} class="single-image" id="album4">
                                        <div class="topic">BIRTH</div>
                                        <p class="text">4th Album<br>2019.09.24</p>
                                    </div>
                                    <div id="album-modal4" class="hide">
                                        <div class="album-row">
                                            <div class="album-index">4th Album</div>
                                            <div id ="close4">
                                                <span class="material-icons">close</span>
                                            </div>
                                        </div>
                                        <div class="album-title">BIRTH</div>
                                        <div class="album-date">2019.09.24</div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="modal-img">
                                                    <img src={{ asset('images/album_image4.jpg') }} class="album-image">
                                                </div>
                                            </div>
                                            <div class="col-md-6 my-auto">
                                                <div class=track-list>
                                                    01.生まれたての子鹿のように<br>
                                                    02.Take off<br>
                                                    03.春風<br>
                                                    04.Lovin' you<br>
                                                    05.ストローク<br>
                                                    06.Graduation<br>
                                                    07.風が運んだ手紙<br>
                                                    08.Waitin' for you<br>
                                                    09.Turquoise<br>
                                                    10.陽炎<br>
                                                    11.Next to you<br>
                                                    12.繋ぎとめるもの<br>
                                                    13.Up to you<br>
                                                    14.夜を通り抜けて<br>
                                                    15.願い
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="single">
                                        <img src={{ asset('images/album_image3.jpg') }} class="single-image" id="album3">
                                        <div class="topic">My World</div>
                                        <p class="text">3rd Album<br>2018.08.01</p>
                                    </div>
                                    <div id="album-modal3" class="hide">
                                        <div class="album-row">
                                            <div class="album-index">3rd Album</div>
                                            <div id ="close3">
                                                <span class="material-icons">close</span>
                                            </div>
                                        </div>
                                        <div class="album-title">My World</div>
                                        <div class="album-date">2018.08.01</div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="modal-img">
                                                    <img src={{ asset('images/album_image3.jpg') }} class="album-image">
                                                </div>
                                            </div>
                                            <div class="col-md-6 my-auto">
                                                <div class="track-list">
                                                    01.Overture 〜Welcome to my world〜<br>
                                                    02.Clover<br>
                                                    03.自由の砦<br>
                                                    04.melody<br>
                                                    05.Dear my friends<br>
                                                    06.I miss you<br>
                                                    07.あなたがいれば<br>
                                                    08.A piece<br>
                                                    09.Present from you<br>
                                                    10.夏のシグナル<br>
                                                    11.TOKYO<br>
                                                    12.Wonderful World<br>
                                                    13.orange
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="single">
                                        <img src={{ asset('images/album_image2.jpg') }} class="single-image" id="album2">
                                        <div class="topic">Second Life</div>
                                        <p class="text">2nd Album<br>2018.03.19</p>
                                    </div>
                                    <div id="album-modal2" class="hide">
                                        <div class="album-row">
                                            <div class="album-index">2nd Album</div>
                                            <div id ="close2">
                                                <span class="material-icons">close</span>
                                            </div>
                                        </div>
                                        <div class="album-title">Second Life</div>
                                        <div class="album-date">2018.03.19</div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="modal-img">
                                                    <img src={{ asset('images/album_image2.jpg') }} class="album-image">
                                                </div>
                                            </div>
                                            <div class="col-md-6 my-auto">
                                                <div class="track-list">
                                                    01.空へ<br>
                                                    02.YOU<br>
                                                    03.僕には明日が見えない<br>
                                                    04.夢花火<br>
                                                    05.Ordinary<br>
                                                    06.君の笑顔<br>
                                                    07.Rollin' on<br>
                                                    08.Search for Love<br>
                                                    09.星空のセレナーデ<br>
                                                    10.僕なりのクリスマス<br>
                                                    11.SUMMER VIBRATION<br>
                                                    12.この世界で<br>
                                                    13.Traveling Life<br>
                                                    14.アリガト 〜Dear K〜
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                    
                                    <div class="single">
                                        <img src={{ asset('images/album_image1.jpg') }} class="single-image" id="album1">
                                        <div class="topic">First Records</div>
                                        <p class="text">1st Album<br>2018.02.26</p>
                                    </div>
                                    <div id="album-modal1" class="hide">
                                        <div class="album-row">
                                            <div class="album-index">1st Album</div>
                                            <div id ="close1">
                                                <span class="material-icons">close</span>
                                            </div>
                                        </div>
                                        <div class="album-title">First Records</div>
                                        <div class="album-date">2018.02.26</div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="modal-img">
                                                    <img src={{ asset('images/album_image1.jpg') }} class="album-image">
                                                </div>
                                            </div>
                                            <div class="col-md-6 my-auto">
                                                <div class=track-list>
                                                    01.未開の地へ<br>
                                                    02.You're the only one<br>
                                                    03.process<br>
                                                    04.今夜の魔法<br>
                                                    05.anymore<br>
                                                    06.Umbrella<br>
                                                    07.Snow White<br>
                                                    08.フルコース<br>
                                                    09.My Love<br>
                                                    10.On the road<br>
                                                    11.Rainbow<br>
                                                    12.ACACIA 〜My Precious Time〜
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            {{-- </div> --}}
                            
                        </div>
                    </div>
                </div>
            </div>
        </div>
    {{-- </div> --}}
</div>
@endsection