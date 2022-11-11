@extends('layouts.app')
@section('content')
<div class="mt-4"></div>
    <div class="radio">
        <div class="container">  
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <h2>y2_radio</h2> 
                    <div class="element js-fadein">
                        <p class="text">
                            週1回不定期で更新！<br>
                            音楽、英語、旅行、プログラミングなどなど何でも語っちゃいます！！<br>
                        </p>
                        <div class="row align-items-center">
                            <div class="col">
                                @foreach ($radios as $radio)
                                <small class="date">{{ date('Y.m.d', strtotime($radio->date)) }}</small><br>
                                <h6>{{$radio->title}}</h6>
                                <p class="text">{{$radio->text}}</p>
                                @endforeach
                            </div>
                            <div class="col">
                                <iframe src="https://embed.podcasts.apple.com/us/podcast/y2-radio/id1555086566?itsct=podcast_box_player&amp;itscg=30200&amp;ls=1&amp;theme=light" height="450px" frameborder="0" sandbox="allow-forms allow-popups allow-same-origin allow-scripts allow-top-navigation-by-user-activation" allow="autoplay *; encrypted-media *; clipboard-write" style="width: 100%; max-width: 660px; overflow: hidden; border-radius: 10px; background: transparent;"></iframe>
                            </div>
                        </div>
                        他のプラットフォームからはコチラ<br>
                        <small class="news_link"><a href="https://anchor.fm/13190" target="_blank">https://anchor.fm/13190</a></small>
                        <br><br>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection