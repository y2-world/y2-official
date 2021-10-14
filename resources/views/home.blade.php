@extends('layouts.app')
<title>Yuki Yoshida Live History - Home</title>
<style>
.title {
        padding-top: 10px;
    }
.top-image {
    text-align: center;
}
.btn-outline-dark {
    margin: 2px 0px;
}
</style>
@section('content')
<div class="row">
    <div class="col-md-8 my-auto">
        <div class="top-image">
            <img src={{ asset('images/top_image.jpg') }} width="100%">
        </div>
    </div>
    <div class="col-md-4 my-auto">
        <div class="container">
            <h1 class="title"> Yuki Yoshida Live History</h1>
            <a href="{{ url('setlists') }}"><button type="button" class="btn btn-outline-dark">ALL SET LISTS</button></a>
            <hr>
            <div class="year">
                <h4>YEAR</h4>
                <a class="btn btn-outline-dark" href="#" role="button">2003</a>
                <a class="btn btn-outline-dark" href="#" role="button">2004</a>
                <a class="btn btn-outline-dark" href="#" role="button">2005</a>
                <a class="btn btn-outline-dark" href="#" role="button">2009</a>
                <a class="btn btn-outline-dark" href="#" role="button">2011</a>
                <a class="btn btn-outline-dark" href="#" role="button">2012</a>
                <a class="btn btn-outline-dark" href="#" role="button">2013</a>
                <a class="btn btn-outline-dark" href="#" role="button">2014</a>
                <a class="btn btn-outline-dark" href="#" role="button">2015</a>
                <a class="btn btn-outline-dark" href="#" role="button">2016</a>
                <a class="btn btn-outline-dark" href="#" role="button">2017</a>
                <a class="btn btn-outline-dark" href="#" role="button">2018</a>
                <a class="btn btn-outline-dark" href="#" role="button">2019</a>
                <a class="btn btn-outline-dark" href="#" role="button">2020</a>
                <a class="btn btn-outline-dark" href="#" role="button">2021</a>
            </div>
            <hr>
            <div class="artists">
                <h4>ARTISTS</h4>
                <div class="artist-button">
                    <a class="btn btn-outline-dark" href="{{ url('artists/1') }}" role="button">w-inds.</a>
                    <a class="btn btn-outline-dark" href="{{ url('artists/2') }}" role="button">Mr.Children</a>
                    <a class="btn btn-outline-dark" href="{{ url('artists/3') }}" role="button">B'z</a>
                    <a class="btn btn-outline-dark" href="{{ url('artists/4') }}" role="button">flumpool</a>
                    <a class="btn btn-outline-dark" href="{{ url('artists/5') }}" role="button">福山雅治</a>
                    <a class="btn btn-outline-dark" href="{{ url('artists/6') }}" role="button">コブクロ</a>
                    <a class="btn btn-outline-dark" href="{{ url('artists/7') }}" role="button">小池美由</a>
                    <a class="btn btn-outline-dark" href="{{ url('artists/8') }}" role="button">SE7EN</a>
                    <a class="btn btn-outline-dark" href="{{ url('artists/9') }}" role="button">スキマスイッチ</a>
                    <a class="btn btn-outline-dark" href="{{ url('artists/10') }}" role="button">Kis-My-Ft2</a>
                    <a class="btn btn-outline-dark" href="{{ url('artists/11') }}" role="button">CHEMISTRY</a>
                    <a class="btn btn-outline-dark" href="{{ url('artists/12') }}" role="button">Charlie Puth</a>
                    <a class="btn btn-outline-dark" href="{{ url('artists/13') }}" role="button">ウカスカジー</a>
                    <a class="btn btn-outline-dark" href="{{ url('artists/14') }}" role="button">嵐</a>
                    <a class="btn btn-outline-dark" href="{{ url('artists/15') }}" role="button">フラチナリズム</a>
                    <a class="btn btn-outline-dark" href="{{ url('artists/16') }}" role="button">Official髭男dism</a>
                </div>
            </div>
            <hr>
        </div>
    </div>
</div>
<script>
</script>
@endsection