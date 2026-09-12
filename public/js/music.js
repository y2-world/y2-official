
document.addEventListener('DOMContentLoaded', function () {
    const overlay = document.getElementById('overlay');
    const popup = document.getElementById('lyrics-popup');
    const popupTitle = document.getElementById('popup-title');
    const popupLyrics = document.getElementById('popup-lyrics');
    const closeBtn = document.querySelector('.close-btn');
    const lyricContainer = document.getElementById('music-container');

    if (!lyricContainer) {
        return;
    }

    const basePath = window.location.pathname;

    // 動的要素にも適用するためのイベントデリゲーション
    lyricContainer.addEventListener('click', function (e) {
        if (e.target.closest('.music-link')) {
            e.preventDefault();
            const lyricLink = e.target.closest('.music-link');
            const lyricId = lyricLink.getAttribute('data-id');
            openLyricsPopup(lyricId, true);
        }
    });

    function openLyricsPopup(lyricId, pushState) {
        fetch(`/lyrics/${lyricId}`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    alert(data.error);
                    return;
                }

                // データをポップアップにセット
                popupTitle.textContent = data.title;
                popupLyrics.innerHTML = data.lyrics; // innerHTML を使用して HTML として表示

                // ポップアップを表示
                overlay.classList.add('open');
                popup.classList.add('open');

                // URLをシェア可能な個別歌詞URLに変更（ページ遷移はしない）
                if (pushState) {
                    history.pushState({ lyricId: lyricId }, '', `/lyrics/${lyricId}`);
                }
            })
            .catch(error => {
                console.error('エラーが発生しました:', error);
            });
    }

    // ポップアップを閉じる
    closeBtn.addEventListener('click', function () { closeMusicPopup(true); });
    overlay.addEventListener('click', function () { closeMusicPopup(true); });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && popup.classList.contains('open')) {
            closeMusicPopup(true);
        }
    });

    function closeMusicPopup(popState) {
        popup.classList.remove('open');
        overlay.classList.remove('open');

        // ポップアップを閉じたら元のURLに戻す
        if (popState && window.location.pathname !== basePath) {
            history.pushState(null, '', basePath);
        }
    }

    // ブラウザの戻る/進むボタンでポップアップの開閉を同期
    window.addEventListener('popstate', function (e) {
        if (e.state && e.state.lyricId) {
            openLyricsPopup(e.state.lyricId, false);
        } else {
            closeMusicPopup(false);
        }
    });
});
