@extends('layouts.app')
<title>Live History - Home</title>
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
            <h1 class="title">LIVE HISTORY</h1>
            <a href="{{ url('setlists/all') }}"><button type="button" class="btn btn-outline-dark">ALL SET LISTS</button></a>
            <br><br>
            <div class="menu">
                <div class="year">
                    <h4>YEAR</h4>
                        <a class="btn btn-outline-dark" href="#" role="button">ALL YEARS</a>
                    {{-- <a class="btn btn-outline-dark" href="#" role="button">2003</a>
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
                    <a class="btn btn-outline-dark" href="#" role="button">2021</a> --}}
                </div>
                <div class="artists">
                    <h4>ARTISTS</h4>
                        <a class="btn btn-outline-dark" href="{{ url('artists') }}" role="button">ALL ARTISTS</a>
                        <a class="btn btn-outline-dark" href="{{ url('artists/17') }}" role="button">FES</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
</script>
@endsection