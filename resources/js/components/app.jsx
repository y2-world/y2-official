import "../bootstrap";

import ReactDOM from "react-dom/client";

function App() {
    return (
        <div>
            <div class="header-wrapper">
                <div class="logo-wrapper">
                    <a href="/">Yuki Official</a>
                    <div class="sub-logo">Yuki Official Official Website</div>
                </div>
                <div class="menu-wrapper">
                    <a href="/news">News</a>
                    <a href="/music">Music</a>
                    <a href="#profile">Profile</a>
                    <a href="#radio">Radio</a>
                    <a
                        href="https://ameblo.jp/y2-world"
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
