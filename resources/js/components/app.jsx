import "../bootstrap";

import ReactDOM from "react-dom/client";

function App() {
    return (
        <div>
            <div class="header-wrapper">
                <div class="logo-wrapper">
                    <a href="{{ url('/') }}">Yuki Official</a>
                    <div class="sub-logo">Yuki Official Official Website</div>
                </div>
                <div class="menu-wrapper">
                    <a href="{{ url('/news') }}">News</a>
                    <a href="{{ url('/music') }}">Music</a>
                    <a href="{{ url('/#profile') }}">Profile</a>
                    <a href="{{ url('/#radio') }}">Radio</a>
                    <a href="{{ url('/setlists') }}">Fan Club</a>
                    <a
                        href="{{ url('https://ameblo.jp/y2-world') }}"
                        target="_blank"
                    >
                        Blog
                    </a>
                </div>
                <div class="mobile-wrapper">
                    <i class="fa-solid fa-bars fa-2xl"></i>
                </div>
            </div>
        </div>
    );
}

const root = ReactDOM.createRoot(document.getElementById("app"));
root.render(<App />);
