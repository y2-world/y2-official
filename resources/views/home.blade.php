@extends('layouts.app')
@section('content')
<div class="cover">
    <div class="element js-fadein">
    <img src={{ asset('images/top_image.jpg') }}>
    <p><span>Yuki Yoshida</span> <span>Official Website</span></p>
    </div>
</div>
<div class="element js-fadein">
    <div class="topics">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-7">
                    <ul class="topic-menu">
                        <li><a href="#" class="active" data-id="menu-topics">TOPICS</a></li>
                        <li><a href="#" data-id="menu-release">NEW RELEASE</a></li>
                        <li><a href="#" data-id="menu-radio">RADIO</a></li>
                    </ul>
                    <hr>
                    <div class="topic-container">
                        <section class="content active" id="menu-topics">
                            <div class="topic-list">
                                <div class="date">2021.09.21</div>
                                <div class="topic"><a href="{{ url('/news') }}">Yuki Offcial リニューアル！</a></div>
                            </div>
                            <div class="topic-list">
                                <div class="date">2021.04.29</div>
                                <div class="topic"><a href="{{ url('/news') }}">y2_radio 質問・感想フォーム開設！</a></div>
                            </div>
                            <div class="topic-list">
                                <div class="date">2021.04.21</div>
                                <div class="topic"><a href="{{ url('/news') }}">Radioページ開設！</a></div>
                            </div>
                            <div class="topic-list">
                                <div class="date">2021.02.25</div>
                                <div class="topic"><a href="{{ url('/news') }}">Yuki Yoshida Official Podcast "y2_radio" 開設！</a></div>
                            </div>
                            <div class="topic-more">
                                <a href="{{ url('/news') }}">MORE</a>
                            </div>
                        </section>
                        <section class="content" id="menu-release">
                            <div class="topic-list">
                                <div class="date">2020.11.27</div>
                                <div class="topic"><a href="{{ url('/music/single') }}">13th Single "Daydream"</a><span class="topic-text">夢や人生観を描いたまさにコロナ禍の"今"に響くような歌詞、
                                    エモーショナルなミディアムロックチューン。</div>
                            </div>
                            <div class="topic-more">
                                <a href="{{ url('/music') }}">MORE</a>
                            </div>
                        </section>
                        <section class="content" id="menu-radio">
                            <div class="topic-list">
                                <div class="date">2021.07.15</div>
                                <div class="topic">#018 (ヒゲダン、ライブ、フェスについて)</div>
                            </div>
                            <div class="topic-list">
                                <div class="date">2021.07.08</div>
                                <div class="topic">#017 (Official髭男dismについて)</div>
                            </div>
                            <div class="topic-list">
                                <div class="date">2021.07.01</div>
                                <div class="topic">#016 (近況報告 〜今年が半分終わっちゃいましたね〜)</div>
                            </div>
                            <div class="topic-list">
                                <div class="date">2021.06.15</div>
                                <div class="topic">#015 (質問コーナー#2、近況報告、iMacについて)</div>
                            </div>
                            <div class="topic-more">
                                <a href="{{ url('/radio') }}">MORE</a>
                            </div>
                        </section>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
   

