import "../bootstrap";

import ReactDOM from "react-dom/client";

function App() {
    return (
        <div>
            <div class="container">
                <div class="nav">
                    <nav class="navbar navbar-expand-lg navbar-light bg-light fixed-top">
                        <a class="navbar-brand" href="{{ url('/') }}">
                            Yuki Official
                            <span class="logo">
                                Yuki Yoshida Official Website
                            </span>
                        </a>
                        <button
                            class="navbar-toggler"
                            type="button"
                            data-toggle="collapse"
                            data-target="#navbarSupportedContent"
                            aria-controls="navbarSupportedContent"
                            aria-expanded="false"
                            aria-label="Toggle navigation"
                        >
                            <div class="navbar-toggler-icon"></div>
                        </button>
                        <div class="sns-nav">
                            <a
                                href="https://www.facebook.com/yuki92496?locale=ja_JP"
                                target="_blank"
                            >
                                <i class="fab fa-facebook"></i>
                            </a>
                            <a
                                href="https://twitter.com/y2_engineer"
                                target="_blank"
                            >
                                {" "}
                                <i class="fab fa-twitter"></i>
                            </a>
                            <a
                                href="https://www.instagram.com/y2_world/"
                                target="_blank"
                            >
                                <i class="fab fa-instagram"> </i>
                            </a>
                            <a
                                href="https://github.com/y2-world"
                                target="_blank"
                            >
                                {" "}
                                <i class="fab fa-github"> </i>
                            </a>
                            <a
                                href="https://music.apple.com/jp/artist/yuki-yoshida/1448865361?itsct=music_box_badge&itscg=30200&ct=artists_yuki_yoshida&app=music&ls=1"
                                target="_blank"
                            >
                                <i class="fab fa-apple"></i>
                            </a>
                            <a
                                href="https://open.spotify.com/artist/5x6TjqB9xXXjY4Xn5y2oJm"
                                target="_blank"
                            >
                                <i class="fab fa-spotify"> </i>
                            </a>
                            <a
                                href="https://www.youtube.com/user/yuki92496"
                                target="_blank"
                            >
                                <i class="fab fa-youtube"></i>
                            </a>
                            <a
                                href="https://podcasts.apple.com/us/podcast/y2-radio/id1555086566?uo=4"
                                target="_blank"
                            >
                                <i class="fas fa-podcast"></i>
                            </a>
                        </div>
                        <div
                            class="collapse navbar-collapse justify-content-end"
                            id="navbarSupportedContent"
                        >
                            <ul class="navbar-nav">
                                <li class="nav-item">
                                    <a
                                        class="nav-link"
                                        href="{{ url('/news') }}"
                                    >
                                        &emsp;News
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a
                                        class="nav-link"
                                        href="{{ url('/music') }}"
                                    >
                                        &emsp;Music
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a
                                        class="nav-link"
                                        href="{{ url('/#profile') }}"
                                    >
                                        &emsp;Profile
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a
                                        class="nav-link"
                                        href="{{ url('/#radio') }}"
                                    >
                                        &emsp;Radio
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a
                                        class="nav-link"
                                        href="{{ url('/setlists') }}"
                                    >
                                        &emsp;LIVE×YOU
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a
                                        class="nav-link"
                                        href="{{ url('/bios') }}"
                                    >
                                        &emsp;Database
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a
                                        class="nav-link"
                                        href="{{ url('https://ameblo.jp/y2-world') }}"
                                        target="_blank"
                                    >
                                        &emsp;Blog&emsp;
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <div class="mb-sns-nav">
                                        <a
                                            href="https://www.facebook.com/yuki92496?locale=ja_JP"
                                            target="_blank"
                                        >
                                            <i class="fab fa-facebook fa-2x"></i>
                                        </a>
                                        <a
                                            href="https://twitter.com/y2_engineer"
                                            target="_blank"
                                        >
                                            {" "}
                                            <i class="fab fa-twitter fa-2x"></i>
                                        </a>
                                        <a
                                            href="https://www.instagram.com/y2_world/"
                                            target="_blank"
                                        >
                                            <i class="fab fa-instagram fa-2x">
                                                {" "}
                                            </i>
                                        </a>
                                        <a
                                            href="https://github.com/y2-world"
                                            target="_blank"
                                        >
                                            {" "}
                                            <i class="fab fa-github fa-2x"> </i>
                                        </a>
                                        <a
                                            href="https://music.apple.com/jp/artist/yuki-yoshida/1448865361?itsct=music_box_badge&itscg=30200&ct=artists_yuki_yoshida&app=music&ls=1"
                                            target="_blank"
                                        >
                                            <i class="fab fa-apple fa-2x"></i>
                                        </a>
                                        <a
                                            href="https://open.spotify.com/artist/5x6TjqB9xXXjY4Xn5y2oJm"
                                            target="_blank"
                                        >
                                            <i class="fab fa-spotify fa-2x">
                                                {" "}
                                            </i>
                                        </a>
                                        <a
                                            href="https://www.youtube.com/user/yuki92496"
                                            target="_blank"
                                        >
                                            <i class="fab fa-youtube fa-2x"></i>
                                        </a>
                                        <a
                                            href="https://podcasts.apple.com/us/podcast/y2-radio/id1555086566?uo=4"
                                            target="_blank"
                                        >
                                            <i class="fas fa-podcast fa-2x"></i>
                                        </a>
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </nav>
                    <script
                        src="https://code.jquery.com/jquery-3.3.1.slim.min.js"
                        integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo"
                        crossorigin="anonymous"
                    ></script>
                    <script
                        src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.3/umd/popper.min.js"
                        integrity="sha384-ZMP7rVo3mIykV+2+9J3UJ46jBk0WLaUAdn689aCwoqbBJiSnjAK/l8WvCWPIPm49"
                        crossorigin="anonymous"
                    ></script>
                    <script
                        src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"
                        integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM"
                        crossorigin="anonymous"
                    ></script>
                </div>
            </div>
        </div>
    );
}

const root = ReactDOM.createRoot(document.getElementById("app"));
root.render(<App />);
