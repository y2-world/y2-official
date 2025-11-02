"use strict";
// モダンなIntersection Observer APIを使用したフェードインアニメーション
{
    // Intersection Observerのサポート確認
    if ('IntersectionObserver' in window) {
        const fadeInObserver = new IntersectionObserver(
            (entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add("is-show");
                    } else {
                        // 上にスクロールして再度非表示にする
                        entry.target.classList.remove("is-show");
                    }
                });
            },
            {
                // ビューポートの高さに応じて閾値を調整（rootMarginを緩和）
                rootMargin: window.innerHeight > 768 ? "-100px" : "-50px",
                threshold: 0,
            }
        );

        // すべてのフェードイン要素を監視
        document.addEventListener("DOMContentLoaded", () => {
            const fadeElements = document.querySelectorAll(".js-fadein");
            fadeElements.forEach((element, index) => {
                // 最初の要素（index === 0）はフェードインなしで即座に表示
                if (index === 0) {
                    element.classList.add("is-show");
                } else {
                    // 2番目以降の要素はフェードイン効果を適用
                    const rect = element.getBoundingClientRect();
                    const isInViewport = rect.top < window.innerHeight && rect.bottom > 0;
                    
                    if (isInViewport) {
                        // フェードインアニメーションを確実に実行するため、少し遅延
                        setTimeout(() => {
                            element.classList.add("is-show");
                        }, 300);
                    } else {
                        // ビューポート外の場合はIntersection Observerで監視
                        fadeInObserver.observe(element);
                    }
                }
            });
        });
        
        // フォールバック: 5秒後にすべての要素を強制的に表示（Intersection Observerやビューポート検出が失敗した場合のみ）
        setTimeout(() => {
            const fadeElements = document.querySelectorAll(".js-fadein:not(.is-show)");
            fadeElements.forEach((element) => {
                element.classList.add("is-show");
            });
        }, 5000);
    } else {
        // Intersection Observerがサポートされていない場合、すぐにすべての要素を表示
        document.addEventListener("DOMContentLoaded", () => {
            const fadeElements = document.querySelectorAll(".js-fadein");
            fadeElements.forEach((element) => {
                element.classList.add("is-show");
            });
        });
    }
    
    // グローバルフォールバック: 5秒後にすべての要素を強制的に表示（すべてのケースに対応、フェードインを優先）
    setTimeout(() => {
        const fadeElements = document.querySelectorAll(".js-fadein:not(.is-show)");
        fadeElements.forEach((element) => {
            element.classList.add("is-show");
        });
    }, 5000);
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
