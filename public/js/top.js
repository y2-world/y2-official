document.addEventListener("DOMContentLoaded", function () {
    const viewAllBtn = document.getElementById("view-all-news-btn");
    const newsContainer = document.getElementById("news-container");

    // Bladeテンプレートで生成されたURLを変数に格納
    const newsUrl = "top/news"; // Blade構文でURLを生成

    if (viewAllBtn) {
        viewAllBtn.addEventListener("click", function () {
            console.log("View All button clicked!");

            // ボタンの状態変更
            viewAllBtn.textContent = "Loading...";
            viewAllBtn.disabled = true;

            // AJAXリクエストを送信
            fetch(newsUrl)
                .then((response) => {
                    if (!response.ok) {
                        throw new Error("Network response was not ok");
                    }
                    return response.json();
                })
                .then((data) => {
                    console.log("Fetched data:", data);

                    if (data.top && data.top.length > 0) {
                        let newsHTML = "";
                        data.top.forEach((newsItem) => {
                            // 日付をyyyy.mm.dd形式にフォーマット
                            const formattedDate = formatDate(newsItem.date);

                            // HTMLコンテンツを作成
                            newsHTML += `
                                <div class="news-item">
                                    <a href="/news/${newsItem.id}" class="news-link">
                                        <div class="topic-title">
                                            <div class="date">${formattedDate}</div>
                                            ${newsItem.title}
                                        </div>
                                    </a>
                                </div>`;
                        });
                        newsContainer.innerHTML = newsHTML;

                        // View All ボタンを非表示にする
                        viewAllBtn.style.display = "none";
                    } else {
                        alert("No more news to display.");
                    }
                })
                .catch((error) => {
                    console.error("Error fetching news:", error);
                    alert("Failed to load news. Please try again later.");
                })
                .finally(() => {
                    // ボタンの状態を戻す
                    viewAllBtn.textContent = "View All";
                    viewAllBtn.disabled = false;
                });
        });
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

    if (!musicContainer) {
        console.error(
            "Error: 'music-container' element is not found in the DOM."
        );
        return;
    }

    const newsUrl = "/top/music"; // 必ず正しいURLを設定

    // Media query を設定
    const mediaQuery = window.matchMedia("(max-width: 1200px)");

    // 初期表示を制御する関数
    function limitMusicDisplay() {
        const musicItems = musicContainer.querySelectorAll(".album-container");

        // メディアクエリを使用して条件分岐
        if (mediaQuery.matches) {
            musicItems.forEach((item, index) => {
                if (index >= 2) {
                    item.style.display = "none";
                } else {
                    item.style.display = "block";
                }
            });
        } else {
            musicItems.forEach((item) => {
                item.style.display = "block";
            });
        }
    }

    limitMusicDisplay();

    // ウィンドウサイズが変更されるたびにメディアクエリを再評価
    mediaQuery.addEventListener("change", limitMusicDisplay);

    if (viewAllBtn) {
        viewAllBtn.addEventListener("click", function () {
            console.log("View All button clicked!");

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
                    console.log("Fetched data:", data);

                    if (data.top && data.top.length > 0) {
                        let musicHTML = "";

                        data.top.forEach((musicItem) => {
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

                        musicContainer.innerHTML = musicHTML; // 初期データを削除し、新しいデータを上書き

                        viewAllBtn.style.display = "none"; // 全て表示済みの場合は非表示
                    } else {
                        alert("No more music to display.");
                    }
                })
                .catch((error) => {
                    console.error("Error fetching music:", error);
                    alert("Failed to load music. Please try again later.");
                })
                .finally(() => {
                    viewAllBtn.textContent = "View All";
                    viewAllBtn.disabled = false;
                });
        });
    }

    function formatDate(dateString) {
        const date = new Date(dateString);
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, "0");
        const day = String(date.getDate()).padStart(2, "0");
        return `${year}.${month}.${day}`;
    }
});

document.addEventListener('DOMContentLoaded', function () {
    const overlay = document.getElementById('overlay');
    const popup = document.getElementById('news-popup');
    const popupTitle = document.getElementById('popup-title');
    const popupDate = document.getElementById('popup-date');
    const popupDescription = document.getElementById('popup-text');
    const closeBtn = document.querySelector('.close-btn');

    // ニュースリンクにクリックイベントを追加
    document.querySelectorAll('.news-link').forEach(link => {
        link.addEventListener('click', function (e) {
            e.preventDefault();

            const newsId = this.getAttribute('data-id'); // ニュースIDを取得

            // ニュースデータを取得
            fetch(`/news/${newsId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        alert(data.error);
                        return;
                    }

                    // ポップアップにデータをセット
                    popupTitle.textContent = data.title;
                    popupDate.textContent = new Date(data.date).toLocaleDateString();
                    popupDescription.innerHTML = data.text;

                    // ポップアップを表示
                    overlay.classList.add('open');
                    popup.classList.add('open');
                })
                .catch(error => {
                    console.error('エラーが発生しました:', error);
                });
        });
    });

    // ポップアップを閉じる
    closeBtn.addEventListener('click', closePopup);
    overlay.addEventListener('click', closePopup);

    function closePopup() {
        popup.classList.remove('open');
        overlay.classList.remove('open');
    }
});