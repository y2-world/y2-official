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

    // デバッグ用: 要素の存在を確認
    if (!musicContainer) {
        console.error("Error: 'music-container' element is not found in the DOM.");
        return; // エラーがあれば処理を中断
    }

    if (viewAllBtn) {
        viewAllBtn.addEventListener("click", function () {
            console.log("View All button clicked!");

            viewAllBtn.textContent = "Loading...";
            viewAllBtn.disabled = true;

            fetch("top/music")
                .then((response) => {
                    if (!response.ok) {
                        throw new Error("Network response was not ok");
                    }
                    return response.json();
                })
                .then((data) => {
                    if (data.top && data.top.length > 0) {
                        let musicHTML = "";
                        data.top.forEach((musicItem) => {
                            const formattedDate = formatDate(musicItem.date);
                            musicHTML += `
                                <div class="single">
                                    <a href="/music/${musicItem.id}">
                                        <img src="https://res.cloudinary.com/hqrgbxuiv/${musicItem.image}" class="single-image">
                                    </a>
                                    <div class="topic">
                                        <a href="/music/${musicItem.id}">${musicItem.title}</a>
                                    </div>
                                    <p class="topic">${musicItem.subtitle}<br>${formattedDate}</p>
                                </div>`;
                        });
                        musicContainer.innerHTML = musicHTML;
                        viewAllBtn.style.display = "none";
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