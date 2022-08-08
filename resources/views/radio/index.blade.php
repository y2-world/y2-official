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
                        {{-- @foreach ($radios as $radio)
                        <hr>
                        <small class="date">{{$radio->date}}</small><br>
                        <h5>{{$radio->title}}</h5>
                        {{$radio->text}}
                        @endforeach --}}
                        <hr>
                        <iframe allow="autoplay *; encrypted-media *; fullscreen *" frameborder="0" height="450" style="width:100%;max-width:660px;overflow:hidden;background:transparent;" sandbox="allow-forms allow-popups allow-same-origin allow-scripts allow-storage-access-by-user-activation allow-top-navigation-by-user-activation" src="https://embed.podcasts.apple.com/us/podcast/y2-radio/id1555086566"></iframe>
                        <br>
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