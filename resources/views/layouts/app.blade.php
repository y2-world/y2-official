<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>@yield('title', 'Yuki Official')</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Zen+Old+Mincho&display=swap" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta1/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-giJF6kkoqNQ00vy+HMDP7azOuL0xtbfIcaT9wjKHr8RbDVddVHyTfAAsrekwKmP1" crossorigin="anonymous">
    <script src="https://kit.fontawesome.com/e47a10189c.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="{{ asset('/css/main.css?time=' . time()) }}">
</head>
<div class="container">
    <div class="nav">
        <nav class="navbar navbar-expand-lg navbar-light bg-light fixed-top">
            <a class="navbar-brand" href="{{ url('/') }}">Yuki Official
                <span class="logo">Yuki Yoshida Official Website</span></a>
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent"
                aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
                <div class="navbar-toggler-icon"></div>
            </button>
            <div class="sns-nav">
                {{-- <a href="https://www.facebook.com/yuki92496?locale=ja_JP" target="_blank"><i class="fab fa-facebook"></i></a>
            <a href="https://twitter.com/y2_engineer" target="_blank"> <i class="fab fa-twitter"></i></a>
            <a href="https://www.instagram.com/y2_world/" target="_blank"><i class="fab fa-instagram"> </i></a>
            <a href="https://github.com/y2-world" target="_blank"> <i class="fab fa-github"> </i></a> --}}
                <a href="https://music.apple.com/jp/artist/yuki-yoshida/1448865361?itsct=music_box_badge&itscg=30200&ct=artists_yuki_yoshida&app=music&ls=1"
                    target="_blank"><i class="fab fa-apple fa-xl"></i></a>
                <a href="https://open.spotify.com/artist/5x6TjqB9xXXjY4Xn5y2oJm" target="_blank"><i
                        class="fab fa-spotify fa-xl"> </i></a>
                <a href="https://www.youtube.com/user/yuki92496" target="_blank"><i
                        class="fab fa-youtube fa-xl"></i></a>
                <a href="https://open.spotify.com/show/5uQQnvpk9DSuY4rBwptQkZ" target="_blank"><i
                        class="fas fa-podcast fa-xl"></i></a>
                {{-- <a href="https://podcasts.apple.com/us/podcast/y2-radio/id1555086566?uo=4" target="_blank"><i class="fas fa-podcast"></i></a> --}}
            </div>
            <div class="collapse navbar-collapse justify-content-end" id="navbarSupportedContent">
                <ul class="navbar-nav">
                    {{-- <li class="nav-item active">
                    <a class="nav-link" href="{{ url('/') }}">&emsp;Home</a>
                </li> --}}
                    <li class="nav-item">
                        <a class="nav-link" href="{{ url('/#news') }}">&emsp;News</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ url('/#music') }}">&emsp;Music</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ url('/#profile') }}">&emsp;Profile</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ url('/#radio') }}">&emsp;Radio</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ url('/setlists') }}">&emsp;Setlists</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ url('/database') }}">&emsp;Database</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ url('https://ameblo.jp/y2-world') }}"
                            target="_blank">&emsp;Blog</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ url('/admin') }}" target="_blank">&emsp;Admin&emsp;</a>
                    </li>
                    <li class="nav-item">
                        <div class="mb-sns-nav">
                            <a href="https://music.apple.com/jp/artist/yuki-yoshida/1448865361?itsct=music_box_badge&itscg=30200&ct=artists_yuki_yoshida&app=music&ls=1"
                                target="_blank"><i class="fab fa-apple fa-2x"></i></a>
                            <a href="https://open.spotify.com/artist/5x6TjqB9xXXjY4Xn5y2oJm" target="_blank"><i
                                    class="fab fa-spotify fa-2x"> </i></a>
                            <a href="https://www.youtube.com/user/yuki92496" target="_blank"><i
                                    class="fab fa-youtube fa-2x"></i></a>
                            <a href="https://open.spotify.com/show/5uQQnvpk9DSuY4rBwptQkZ" target="_blank"><i
                                    class="fas fa-podcast fa-2x"></i></a>
                        </div>
                    </li>
                </ul>
            </div>
        </nav>
        <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js"
            integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous">
        </script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.3/umd/popper.min.js"
            integrity="sha384-ZMP7rVo3mIykV+2+9J3UJ46jBk0WLaUAdn689aCwoqbBJiSnjAK/l8WvCWPIPm49" crossorigin="anonymous">
        </script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"
            integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous">
        </script>
    </div>
</div>
<div class="header_space"></div>
@yield('content')
</html>
{{-- <footer id='footer'>
    <footer class="text-left bg-dark text-white">
        <div class="footer-main">
            <div class="container">
                <br>
                <div class="sns">
                    <a href="https://www.facebook.com/yuki92496?locale=ja_JP" target="_blank"><i class="fab fa-facebook fa-lg"></i></a>
                    <a href="https://twitter.com/y2_engineer" target="_blank"> <i class="fab fa-twitter fa-lg"></i></a>
                    <a href="https://www.instagram.com/y2_world/" target="_blank"><i class="fab fa-instagram fa-lg"> </i></a>
                    <a href="https://github.com/y2-world" target="_blank"> <i class="fab fa-github fa-lg"> </i></a>
                    <a href="https://music.apple.com/jp/artist/yuki-yoshida/1448865361?itsct=music_box_badge&itscg=30200&ct=artists_yuki_yoshida&app=music&ls=1" target="_blank"><i class="fab fa-apple fa-lg"></i></a>
                    <a href="https://open.spotify.com/artist/5x6TjqB9xXXjY4Xn5y2oJm" target="_blank"><i class="fab fa-spotify fa-lg"> </i></a>
                    <a href="https://www.youtube.com/user/yuki92496" target="_blank"><i class="fab fa-youtube fa-lg"></i></a>
                    <a href="https://podcasts.apple.com/us/podcast/y2-radio/id1555086566?uo=4" target="_blank"><i class="fas fa-podcast fa-lg"></i></a>
                </div>
                <br>
                <div class="hour">Yuki Yoshida Official Website</div>
            </div> 
        </div>
    </footer>  
</footer> --}}

<!-- JS -->
<script src='https://code.jquery.com/jquery-3.6.4.min.js'></script>
<script src="{{ asset('/js/main.js') }}"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js"></script>

<!-- スクリプトの読み込み -->
<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/typeahead.js/0.11.1/typeahead.bundle.min.js"></script>
<script src="{{ asset('/js/search.js') }}"></script>

<!-- スクリプトの読み込み -->
<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/typeahead.js/0.11.1/typeahead.bundle.min.js"></script>
<script src="{{ asset('/js/top.js?time=' . time()) }}"></script>

<!-- スクリプトの読み込み -->
<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/typeahead.js/0.11.1/typeahead.bundle.min.js"></script>
<script src="{{ asset('/js/music.js?time=' . time()) }}"></script>

<!-- Bootstrap scripts -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.3/umd/popper.min.js"
    integrity="sha384-ZMP7rVo3mIykV+2+9J3UJ46jBk0WLaUAdn689aCwoqbBJiSnjAK/l8WvCWPIPm49" crossorigin="anonymous">
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"
    integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous">
</script>
