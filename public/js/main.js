"use strict";
{
    function showElementAnimation() {
        var element = document.getElementsByClassName("js-fadein");
        if (!element) return; // 要素がなかったら処理をキャンセル

        var showTiming = window.innerHeight > 768 ? 200 : 300; // 要素が出てくるタイミングはここで調整
        var scrollY = window.pageYOffset; //スクロール量を取得
        var windowH = window.innerHeight; //ブラウザウィンドウのビューポート(viewport)の高さを取得

        for (var i = 0; i < element.length; i++) {
            var elemClientRect = element[i].getBoundingClientRect();
            var elemY = scrollY + elemClientRect.top;
            if (scrollY + windowH - showTiming > elemY) {
                element[i].classList.add("is-show");
            } else if (scrollY + windowH < elemY) {
                // 上にスクロールして再度非表示にする場合はこちらを記述
                element[i].classList.remove("is-show");
            }
        }
    }
    showElementAnimation();
    window.addEventListener("scroll", showElementAnimation);
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
//     const viewAllBtn = document.getElementById('view-all-btn');
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

document.addEventListener('DOMContentLoaded', function () {
    const viewAllBtn = document.getElementById('view-all-btn');

    if (viewAllBtn) {
        viewAllBtn.addEventListener('click', function () {
            console.log('View All button clicked!');
        });
    } else {
        console.log('View All button not found!');
    }
});
