"use strict";
// フェードインアニメーション
{
    function checkAndShowElements() {
        const fadeElements = document.querySelectorAll(".js-fadein:not(.is-show)");
        const viewportHeight = window.innerHeight || document.documentElement.clientHeight;
        const triggerOffset = viewportHeight * 0.8; // ビューポートの80%の位置で発火
        
        fadeElements.forEach((element) => {
            const rect = element.getBoundingClientRect();
            // 要素の上端がビューポートの80%の位置より上にある場合に表示
            if (rect.top < triggerOffset && rect.bottom > 0) {
                element.classList.add("is-show");
            }
        });
    }

    function initFadeIn() {
        const fadeElements = document.querySelectorAll(".js-fadein");

        if (fadeElements.length === 0) return;

        fadeElements.forEach((element, index) => {
            // 最初の要素は即座に表示（フェードインなし）
            if (index === 0) {
                element.classList.add("is-show");
            }
        });

        // 初期チェック：ページロード時に表示領域内の要素をチェック
        setTimeout(() => {
            checkAndShowElements();
        }, 100);

        // Intersection Observerがサポートされている場合は使用
        if ('IntersectionObserver' in window) {
            const observer = new IntersectionObserver((entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add("is-show");
                        // 一度表示したら監視を停止
                        observer.unobserve(entry.target);
                    }
                });
            }, {
                rootMargin: "200px 0px",
                threshold: 0
            });

            // 2番目以降の要素を監視
            fadeElements.forEach((element, index) => {
                if (index > 0 && !element.classList.contains("is-show")) {
                    observer.observe(element);
                }
            });
        }

        // スクロールイベントでもチェック（Intersection Observerのフォールバック＋iOS対応）
        let scrollTimeout;
        const handleScroll = () => {
            clearTimeout(scrollTimeout);
            scrollTimeout = setTimeout(checkAndShowElements, 50);
        };

        window.addEventListener("scroll", handleScroll, { passive: true });
        window.addEventListener("touchmove", handleScroll, { passive: true });

        // リサイズ時もチェック
        window.addEventListener("resize", () => {
            checkAndShowElements();
        }, { passive: true });
    }

    // DOMContentLoaded時に初期化
    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", initFadeIn);
    } else {
        // すでに読み込み済みの場合
        initFadeIn();
    }
}
{
    //リストのリンク要素を取得
    const menuItems = document.querySelectorAll(".topic-menu li a");
    //コンテンツを取得
    const contents = document.querySelectorAll(".content");

    menuItems.forEach((clickedItem) => {
        //タブをクリックしたら
        clickedItem.addEventListener("click", (e) => {
            //デフォルト動作を無効にする
            e.preventDefault();

            //item(menuItemsの要素の一つ)のactiveを無効にする
            menuItems.forEach((item) => {
                item.classList.remove("active");
            });
            //クリックしたitem(clikedItem)をactiveにする
            clickedItem.classList.add("active");

            //content(タブの中身)のactive(display:block)を無効にする
            contents.forEach((content) => {
                content.classList.remove("active");
            });
            //クリックしたitem(clikedItem)datasetをのをactiveにする
            document
                .getElementById(clickedItem.dataset.id)
                .classList.add("active");
        });
    });
}
{
    $(window).on("load resize", function () {
        // navbarの高さを取得する
        var height = $(".navbar").height();
        // bodyのpaddingにnavbarの高さを設定する
        $("body").css("padding-top", height);
    });
}

// document.addEventListener('DOMContentLoaded', function () {
//     const viewAllBtn = document.getElementById('view-all-news-btn');
//     const newsContainer = document.getElementById('news-container');

//     if (viewAllBtn) {
//         viewAllBtn.addEventListener('click', function () {
//             viewAllBtn.textContent = 'Loading...';
//             viewAllBtn.disabled = true;

//             fetch("/news/all")
//                 .then(response => {
//                     if (!response.ok) throw new Error('Network response was not ok');
//                     return response.json();
//                 })
//                 .then(data => {
//                     if (data.news && data.news.length > 0) {
//                         let newsHTML = '';
//                         data.news.forEach(newsItem => {
//                             newsHTML += `
//                                 <div class="news-item">
//                                     <a href="/news/${newsItem.id}" class="news-link">
//                                         <div class="topic-title">
//                                             <div class="date">${newsItem.date}</div>
//                                             ${newsItem.title}
//                                         </div>
//                                     </a>
//                                 </div>`;
//                         });
//                         newsContainer.innerHTML = newsHTML;
//                     } else {
//                         alert('No more news to display.');
//                     }
//                 })
//                 .catch(error => {
//                     console.error('Error fetching news:', error);
//                     alert('Failed to load news. Please try again later.');
//                 })
//                 .finally(() => {
//                     viewAllBtn.textContent = 'View All';
//                     viewAllBtn.disabled = false;
//                 });
//         });
//     }
// });
