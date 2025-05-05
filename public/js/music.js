
document.addEventListener('DOMContentLoaded', function () {
    const overlay = document.getElementById('overlay');
    const popup = document.getElementById('lyrics-popup');
    const popupTitle = document.getElementById('popup-title');
    const popupLyrics = document.getElementById('popup-lyrics');
    const closeBtn = document.querySelector('.close-btn');
    const lyricContainer = document.getElementById('music-container');

    // 動的要素にも適用するためのイベントデリゲーション
    lyricContainer.addEventListener('click', function (e) {
        if (e.target.closest('.music-link')) {
            e.preventDefault();
            const lyricLink = e.target.closest('.music-link');
            const lyricId = lyricLink.getAttribute('data-id');

            fetch(`/lyrics/${lyricId}`)
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
                })
                .catch(error => {
                    console.error('エラーが発生しました:', error);
                });
        }
    });

    // ポップアップを閉じる
    closeBtn.addEventListener('click', closeMusicPopup); // 閉じるボタンをクリックしたらポップアップを閉じる
    overlay.addEventListener('click', closeMusicPopup); // オーバーレイをクリックしたらポップアップを閉じる

    function closeMusicPopup() {
        popup.classList.remove('open');
        overlay.classList.remove('open');
    }
});