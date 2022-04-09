@extends('layouts.app')
@section('content')
<div class="mt-4"></div>
<div class="works">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <h2>Works</h2>
                <div class="element js-fadein">
                    <div class="works">
                        <a href="http://18.179.42.2/" target="_blank"> <img src={{ asset('images/works_image2.png') }} class="image" width="100%"></a>
                        <a href="http://18.179.42.2/" target="_blank"><h5 class="works-header">TRAVEL×YOU</h5></a>
                        <hr>
                        <h6 class>旅とあなたをつなぐプラットフォーム</h6>
                        <p class="text"><span>今までのあなたの旅の歴史を記録し、</span>
                            <span>さらにこれからのあなたの旅をより楽しいものにするツールです。</span>
                            <span>旅行記を作ったり、</span>
                            <span>旅行先でのトラブルをシェアしたり、</span>
                            <span>疑問に思ったことを質問したり...</span> 
                            <span>楽しみ方は何通りもあります。</span>
                            <span>さぁ、一緒に旅に出かけましょう！</span>
                        <br>
                        <br>
                        使用言語 : PHP (Laravel) / JavaScript<br>
                        データベース : MySQL<br>
                        サーバー : AWS
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection