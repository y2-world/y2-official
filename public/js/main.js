'use strict';
{
    $(function(){
    //フッターを最下部に固定
        var $footer = $('#footer');
        if(window.innerHeight > $footer.offset().top + $footer.outerHeight() ) {
            $footer.attr({'style': 'position:fixed; top:' + (window.innerHeight - $footer.outerHeight()) + 'px;' });
        }
    })
}
{
    function showElementAnimation() {
                        
    var element = document.getElementsByClassName('js-fadein');
    if(!element) return; // 要素がなかったら処理をキャンセル
                        
    var showTiming = window.innerHeight > 768 ? 200 : 300; // 要素が出てくるタイミングはここで調整
    var scrollY = window.pageYOffset; //スクロール量を取得
    var windowH = window.innerHeight; //ブラウザウィンドウのビューポート(viewport)の高さを取得
                        
    for(var i=0;i<element.length;i++) { 
        var elemClientRect = element[i].getBoundingClientRect(); 
        var elemY = scrollY + elemClientRect.top; 
        if(scrollY + windowH - showTiming > elemY) {
        element[i].classList.add('is-show');
        } else if(scrollY + windowH < elemY) {
        // 上にスクロールして再度非表示にする場合はこちらを記述
        element[i].classList.remove('is-show');
        }
    }
    }
    showElementAnimation();
    window.addEventListener('scroll', showElementAnimation);
}
{
    $('#album4').click(() => {
        $('#album-modal4')
            .removeClass('hide')
        $('#mask')
            .removeClass('hide');
    });

    $('#album3').click(() => {
        $('#album-modal3')
            .removeClass('hide')
        $('#mask')
            .removeClass('hide');
    });

    $('#album2').click(() => {
        $('#album-modal2')
            .removeClass('hide')
        $('#mask')
            .removeClass('hide');
    });

    $('#album1').click(() => {
        $('#album-modal1')
            .removeClass('hide')
        $('#mask')
            .removeClass('hide');
    });

    $('#close4').click(() => {
        $('#album-modal4')
            .addClass('hide')
        $('#mask')
            .addClass('hide');
    });

    $('#close3').click(() => {
        $('#album-modal3')
            .addClass('hide')
        $('#mask')
            .addClass('hide');
    });

    $('#close2').click(() => {
        $('#album-modal2')
            .addClass('hide')
        $('#mask')
            .addClass('hide');
    });

    $('#close1').click(() => {
        $('#album-modal1')
            .addClass('hide')
        $('#mask')
            .addClass('hide');
    });
}
{
    //リストのリンク要素を取得
    const menuItems = document.querySelectorAll('.topic-menu li a');
    //コンテンツを取得
    const contents = document.querySelectorAll('.content');

    menuItems.forEach(clickedItem => {
        //タブをクリックしたら
        clickedItem.addEventListener('click', e => {
            //デフォルト動作を無効にする
            e.preventDefault();

            //item(menuItemsの要素の一つ)のactiveを無効にする
            menuItems.forEach(item => {
                item.classList.remove('active');
            });
            //クリックしたitem(clikedItem)をactiveにする
            clickedItem.classList.add('active');

            //content(タブの中身)のactive(display:block)を無効にする
            contents.forEach(content => {
                content.classList.remove('active');
            });
            //クリックしたitem(clikedItem)datasetをのをactiveにする
            document.getElementById(clickedItem.dataset.id).classList.add('active');
        })
    })
}