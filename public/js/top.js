document.addEventListener("DOMContentLoaded", function () {
    const viewAllBtn = document.getElementById("view-all-news-btn");
    const newsContainer = document.getElementById("news-container");

    // Bladeテンプレートで生成されたURLを変数に格納
    const newsUrl = "top/news"; // Blade構文でURLを生成

    let allNewsData = null; // 全ニュースデータを保存
    let initialNewsHTML = newsContainer.innerHTML; // 初期表示のHTMLを保存
    let isShowingAll = false; // 表示状態を管理

    if (viewAllBtn) {
        viewAllBtn.addEventListener("click", function () {
            if (isShowingAll) {
                // Show Lessの処理：初期表示に戻す
                newsContainer.innerHTML = initialNewsHTML;
                viewAllBtn.textContent = "View All";
                isShowingAll = false;
            } else {
                // View Allの処理
                if (allNewsData) {
                    // 既にデータがある場合は再利用
                    displayAllNews(allNewsData);
                    viewAllBtn.textContent = "Show Less";
                    isShowingAll = true;
                } else {
                    // 初回はAJAXでデータを取得
                    viewAllBtn.textContent = "Loading...";
                    viewAllBtn.disabled = true;

                    fetch(newsUrl)
                        .then((response) => {
                            if (!response.ok) {
                                throw new Error("Network response was not ok");
                            }
                            return response.json();
                        })
                        .then((data) => {
                            if (data.top && data.top.length > 0) {
                                allNewsData = data.top; // データを保存
                                displayAllNews(data.top);
                                viewAllBtn.textContent = "Show Less";
                                isShowingAll = true;
                            } else {
                                alert("No more news to display.");
                            }
                        })
                        .catch((error) => {
                            console.error("Error fetching news:", error);
                            alert("Failed to load news. Please try again later.");
                        })
                        .finally(() => {
                            viewAllBtn.disabled = false;
                        });
                }
            }
        });
    }

    // 全ニュースを表示する関数
    function displayAllNews(newsData) {
        let newsHTML = "";
        newsData.forEach((newsItem) => {
            const formattedDate = formatDate(newsItem.published_at || newsItem.date);
            newsHTML += `
                <div class="news-item">
                    <a href="/news/${newsItem.id}" class="news-link" data-id="${newsItem.id}">
                        <div class="news-item__title">
                            <div class="date">${formattedDate}</div>
                            ${newsItem.title}
                        </div>
                    </a>
                </div>`;
        });
        newsContainer.innerHTML = newsHTML;
    }

    // 日付をyyyy.mm.dd形式に変換する関数
    function formatDate(dateString) {
        const date = new Date(dateString);
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, "0"); // 1月は0なので+1
        const day = String(date.getDate()).padStart(2, "0");
        return `${year}.${month}.${day}`;
    }
});

document.addEventListener("DOMContentLoaded", function () {
    const viewAllBtn = document.getElementById("view-all-music-btn");
    const musicContainer = document.getElementById("music-container");
    const musicUrl = "/top/music";

    let allMusicData = null; // 全Musicデータを保存
    let initialMusicHTML = musicContainer.innerHTML; // 初期表示のHTMLを保存
    let isShowingAll = false; // 表示状態を管理

    if (viewAllBtn) {
        viewAllBtn.addEventListener("click", function () {
            if (isShowingAll) {
                // Show Lessの処理：初期表示に戻す
                musicContainer.classList.remove('album-wrapper-grid');
                musicContainer.classList.add('album-wrapper-scroll');
                musicContainer.innerHTML = initialMusicHTML;
                viewAllBtn.textContent = "View All";
                isShowingAll = false;
            } else{
                // View Allの処理
                if (allMusicData) {
                    // 既にデータがある場合は再利用
                    displayAllMusic(allMusicData);
                    viewAllBtn.textContent = "Show Less";
                    isShowingAll = true;
                } else {
                    // 初回はAJAXでデータを取得
                    viewAllBtn.textContent = "Loading...";
                    viewAllBtn.disabled = true;

                    fetch(musicUrl)
                        .then((response) => {
                            if (!response.ok) {
                                throw new Error("Network response was not ok");
                            }
                            return response.json();
                        })
                        .then((data) => {
                            if (data.top && data.top.length > 0) {
                                allMusicData = data.top; // データを保存
                                displayAllMusic(data.top);
                                viewAllBtn.textContent = "Show Less";
                                isShowingAll = true;
                            } else {
                                alert("No more music to display.");
                            }
                        })
                        .catch((error) => {
                            console.error("Error fetching music:", error);
                            alert("Failed to load music. Please try again later.");
                        })
                        .finally(() => {
                            viewAllBtn.disabled = false;
                        });
                }
            }
        });
    }

    // 全Musicを表示する関数
    function displayAllMusic(musicData) {
        // グリッド表示に切り替え
        musicContainer.classList.remove('album-wrapper-scroll');
        musicContainer.classList.add('album-wrapper-grid');

        let musicHTML = "";
        musicData.forEach((musicItem) => {
            const formattedDate = formatDate(musicItem.date);
            musicHTML += `
                <div class="album-container">
                    <a href="/music/${musicItem.id}">
                        <img src="https://res.cloudinary.com/hqrgbxuiv/${musicItem.image}" class="album-image">
                    </a>
                    <div class="music-item__gray">
                        <a href="/music/${musicItem.id}">${musicItem.title}</a>
                        <p>
                            ${musicItem.subtitle}<br>${formattedDate}
                        </p>
                    </div>
                </div>`;
        });
        musicContainer.innerHTML = musicHTML;
    }

    function formatDate(dateString) {
        const date = new Date(dateString);
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, "0");
        const day = String(date.getDate()).padStart(2, "0");
        return `${year}.${month}.${day}`;
    }
});

// シンプルな自動スクロール（CSSアニメーション使用）
document.addEventListener('DOMContentLoaded', function() {
    const scrollContainer = document.querySelector('.album-wrapper-scroll');
    if (!scrollContainer) return;
    
    const inner = scrollContainer.querySelector('.album-wrapper-inner');
    if (!inner) return;
    
    // マウスホバー時は一時停止
    scrollContainer.addEventListener('mouseenter', function() {
        inner.classList.add('paused');
    });
    
    scrollContainer.addEventListener('mouseleave', function() {
        inner.classList.remove('paused');
    });
    
    // タッチ開始時は一時停止
    let touchTimeout = null;
    scrollContainer.addEventListener('touchstart', function() {
        inner.classList.add('paused');
        clearTimeout(touchTimeout);
    }, { passive: true });
    
    scrollContainer.addEventListener('touchend', function() {
        // タッチが終わってから少し待って再開
        touchTimeout = setTimeout(function() {
            inner.classList.remove('paused');
        }, 1500);
    }, { passive: true });
});

document.addEventListener('DOMContentLoaded', function () {
    const overlay = document.getElementById('overlay');
    const popup = document.getElementById('news-popup');
    const popupTitle = document.getElementById('popup-title');
    const popupDate = document.getElementById('popup-date');
    const popupText = document.getElementById('popup-text');
    const popupImg = document.getElementById('popup-img');
    const popupOpenLink = document.getElementById('popup-open-link');
    const closeBtn = document.querySelector('.close-btn');
    const newsContainer = document.getElementById('news-container');

    // 動的要素にも適用するためのイベントデリゲーション
    newsContainer.addEventListener('click', function (e) {
        if (e.target.closest('.news-link')) {
            e.preventDefault();
            const newsLink = e.target.closest('.news-link');
            const newsId = newsLink.getAttribute('data-id');

            fetch(`/news/${newsId}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTPエラー: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.error) {
                        alert(data.error);
                        return;
                    }

                    // 日付を整形
                    const date = new Date(data.published_at || data.date);
                    const formattedDate = `${date.getFullYear()}.${String(date.getMonth() + 1).padStart(2, '0')}.${String(date.getDate()).padStart(2, '0')}`;

                    // データをポップアップにセット
                    popupTitle.textContent = data.title;
                    popupDate.textContent = formattedDate;

                    // XSS対策: textContentを使用する or HTMLを許可するならDOMPurifyを利用
                    popupText.innerHTML = data.text; 
                    // popupText.innerHTML = DOMPurify.sanitize(data.text);

                    if (data.image) {
                        popupImg.style.display = 'block';
                        popupImg.src = `https://res.cloudinary.com/hqrgbxuiv/${data.image}`;
                    } else {
                        popupImg.style.display = 'none'; // 画像がない場合は非表示にする
                    }

                    // 個別ページのリンクを設定
                    popupOpenLink.href = `/news/${newsId}`;

                    // ポップアップを表示
                    overlay.classList.add('open');
                    popup.classList.add('open');
                })
                .catch(error => {
                    console.error('エラーが発生しました:', error);
                });
        }
    });

    // ポップアップを閉じる
    closeBtn.addEventListener('click', closeNewsPopup);
    overlay.addEventListener('click', closeNewsPopup);

    // Escキーでポップアップを閉じる
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && popup.classList.contains('open')) {
            closeNewsPopup();
        }
    });

    function closeNewsPopup() {
        popup.classList.remove('open');
        overlay.classList.remove('open');
    }
});

// Fade-in on observe
document.addEventListener('DOMContentLoaded', function () {
    const fadeTargets = document.querySelectorAll('.js-fadein');
    if (!('IntersectionObserver' in window)) {
        // fallback
        fadeTargets.forEach(el => el.classList.add('is-show'));
        return;
    }
    const io = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-show');
                io.unobserve(entry.target);
            }
        });
    }, { threshold: 0.2 });
    fadeTargets.forEach(el => io.observe(el));
});