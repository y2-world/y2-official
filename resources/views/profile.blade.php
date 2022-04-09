@extends('layouts.app')
@section('content')
<div class="mt-4"></div>
<div class="profile">
    <div class="container">
        <div class="col-md-12">
            <h2>Profile</h2>
                <div class="element js-fadein"> 
                    <div class="row">
                        <div class="col-md-6 my-auto">
                            <div class="prof_img">
                                <img src={{ asset('images/profile_image.jpg') }} class="image" width="80%">
                            </div>
                        </div>
                        <div class="col-md-5 my-auto"> 
                            <br>
                            <h2>Yuki Yoshida</h2>
                            <p>1996年09月24日 茨城県出身<p>
                            <hr>
                            <p class="profile-text">東京都生まれ、茨城県出身。高校在学中にJimdoのWebサービスを使い、ホームページ 「<a class="link" href="https://y2-world.jimdofree.com/" target="_blank">y2_world</a>」を開設。大学在学中にはアメリカのカリフォルニア州立大学フラトン校にて言語学を学ぶ。高校時代から曲作りをしていたが、その曲たちを形にしたいと思い、留学中に本格的に音楽制作を開始。2017年12月にシングル「Snow White」でデビュー。その後、アルバム4枚、シングル13枚をリリース。2022年には、およそ1年半振りに音楽活動を再開予定。2022年12月にはデビュー5周年を迎える。</p>
                            <hr>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection