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
            <hr>
            <div class="year">
                <h4>YEAR</h4>
                <a href="{{ url('years/2003') }}"><button type="button" class="btn btn-outline-dark">2003</button></a>
                <button type="button" class="btn btn-outline-dark">2004</button>
                <button type="button" class="btn btn-outline-dark">2005</button>
                <button type="button" class="btn btn-outline-dark">2009</button>
                <button type="button" class="btn btn-outline-dark">2011</button>
                <button type="button" class="btn btn-outline-dark">2012</button>
                <button type="button" class="btn btn-outline-dark">2013</button>
                <button type="button" class="btn btn-outline-dark">2014</button>
                <button type="button" class="btn btn-outline-dark">2015</button>
                <button type="button" class="btn btn-outline-dark">2016</button>
                <button type="button" class="btn btn-outline-dark">2017</button>
                <button type="button" class="btn btn-outline-dark">2018</button>
                <button type="button" class="btn btn-outline-dark">2019</button>
                <button type="button" class="btn btn-outline-dark">2020</button>
                <button type="button" class="btn btn-outline-dark">2021</button>
            </div>
            <hr>
            <div class="artists">
                <h4>ARTISTS</h4>
                <div class="artist-button">
                    <button type="button" class="btn btn-outline-dark">w-inds.</button>
                    <a href="{{ url('artists/mr.children') }}"><button type="button" class="btn btn-outline-dark">Mr.Children</button></a>
                    <button type="button" class="btn btn-outline-dark">B'z</button>
                    <button type="button" class="btn btn-outline-dark">flumpool</button>
                    <button type="button" class="btn btn-outline-dark">福山雅治</button>
                    <button type="button" class="btn btn-outline-dark">コブクロ</button>
                    <button type="button" class="btn btn-outline-dark">小池美由</button>
                    <button type="button" class="btn btn-outline-dark">SE7EN</button>
                    <button type="button" class="btn btn-outline-dark">スキマスイッチ</button>
                    <button type="button" class="btn btn-outline-dark">Kis-My-Ft2</button>
                    <button type="button" class="btn btn-outline-dark">CHEMISTRY</button>
                    <button type="button" class="btn btn-outline-dark">Charlie Puth</button>
                    <button type="button" class="btn btn-outline-dark">ウカスカジー</button>
                    <button type="button" class="btn btn-outline-dark">嵐</button>
                    <button type="button" class="btn btn-outline-dark">フラチナリズム</button>
                    <button type="button" class="btn btn-outline-dark">Official髭男dism</button>
                </div>
            </div>
            <hr>
        </div>
    </div>
</div>
<script>
</script>
@endsection