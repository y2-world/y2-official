@extends('layouts.app')
@section('content')
<div class="mt-4"></div>
    <div class="news">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-8">
                <h2 class="news-title">News</h2>
                    <div class="element js-fadein">
                        <hr>
                        <small class="date">2021.09.21</small>
                        <div class="link">Yuki Official リニューアル！</div>
                        <p class="text">Yuki Officialがリニューアルしました！これからもYukiの情報を発信していきます！<br></p>
                        <hr>
                        <small class="date">2021.04.29</small>
                        <div class="link">y2_radio 質問・感想フォーム開設！</div>
                        <p class="text">質問・感想フォームが完成しました！ぜひメッセージをお願いします！<br></p>
                        <small class="news_link"><a href="{{ url('/radio/form') }}">質問・感想フォーム</a></small>
                        <hr>
                        <small class="date">2021.04.21</small>
                        <div class="link">Radioページ開設！</div>
                        <p class="text">y2_radioが聞けるRadioページを開設しました！質問・感想フォームも現在開設中です！<br></p>
                        <small class="news_link"><a href="{{ url('/radio') }}">Radioページへ</a></small>
                        <hr> 
                        <small class="date">2021.02.25</small>
                        <div class="link">Yuki Yoshida Official Podcast "y2_radio" 開設！</div>
                        <p class="text">音楽や英語、プログラミングなど様々なテーマについて語ります！不定期更新予定。<br></p>
                        <small class="news_link"><a href="https://anchor.fm/13190" target="_blank">https://anchor.fm/13190</a></small>
                        <hr> 
                        <small class="date">2021.02.18</small>
                        <div class="link">Yuki Yoshida Official Qiita 開設！</div>
                        <p class="text">Yuki Yoshida Official Qiitaが開設されました。プログラミング学習のアウトプットのために今後随時更新していきます。</p>  
                        <small class="news_link"><a href="https://qiita.com/y2_engineer" target="_blank">https://qiita.com/y2_engineer</a></small>
                        <hr> 
                        <small class="date">2021.02.07</small>
                        <div class="link">Yuki Yoshida Official Website 開設！</div>
                        <p class="text">Yukiのアーティスト活動やエンジニアとしての活動をお伝えするオフィシャルウェブサイトが開設されました。</p>  
                        <hr> 
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection