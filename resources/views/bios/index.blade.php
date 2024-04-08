<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search</title>
    <!-- jQuery -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>

    <!-- Typeahead.js -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/corejs-typeahead/1.3.1/typeahead.bundle.min.js"></script>

    <!-- Bootstrap CSS -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <style>
        /* Typeaheadのドロップダウンのスタイル */
        .tt-menu {
            background-color: white;
            border: 1px solid #ccc;
            border-radius: 4px;
            padding: 4px 0;
            z-index: 9999; /* ドロップダウンのz-indexを上に設定 */
        }

        .tt-suggestion {
            padding: 8px;
            cursor: pointer;
        }

        .tt-suggestion:hover {
            background-color: #f2f2f2;
        }
    </style>

    <div class="container mt-5">
        <input type="text" id="searchInput" class="form-control" placeholder="Search...">
        <button id="searchButton" class="btn btn-primary">Search</button>
    </div>

    <script>
        $(document).ready(function(){
            // サンプルのダミーデータ
            var dummyData = [
                { id: 1, title: 'ロード・アイ・ミス・ユー' },
                { id: 2, title: 'Mr.Shining Moon' },
                { id: 3, title: '君がいた夏' },
                { id: 4, title: '風 〜The wind knows how I feel' },
                { id: 5, title: 'ためいきの日曜日' },
                { id: 6, title: '友達のままで' },
                { id: 7, title: "CHILDREN'S WORLD" },
                { id: 8, title: 'グッバイ・マイ・グルーミー・デイズ' },
                { id: 9, title: '抱きしめたい' },
                { id: 10, title: '君の事以外は何も考えられない' },
                { id: 11, title: '虹の彼方へ' },
                { id: 12, title: 'All by myself' },
                { id: 13, title: 'BLUE' },
                { id: 14, title: 'Distance' },
                { id: 15, title: '車の中でかくれてキスをしよう' },
                { id: 16, title: '思春期の夏 〜君との恋が今も牧場に〜' },
                { id: 17, title: '星になれたら' },
                { id: 18, title: 'ティーン・エイジ・ドリーム (I〜II)' },
                { id: 19, title: 'いつの日にか二人で' },
                { id: 20, title: 'Replay' },
                { id: 21, title: 'Another Mind' },
                { id: 22, title: 'メインストリートに行こう' },
                { id: 23, title: 'and I close to you' },
                { id: 24, title: 'マーマレード・キッス' },
                { id: 25, title: '逃亡者' },
                { id: 26, title: '蜃気楼' },
                { id: 27, title: 'LOVE' },
                { id: 28, title: 'さよならは夢の中へ' },
                { id: 29, title: 'my life' },
                { id: 30, title: 'CROSS ROAD' },
                { id: 31, title: 'innocent world' },
                { id: 32, title: 'my confidence song' },
                { id: 33, title: 'Dance Dance Dance' },
                { id: 34, title: 'ラヴ コネクション' },
                { id: 35, title: 'クラスメイト' },
                { id: 36, title: 'ジェラシー' },
                { id: 37, title: 'Asia (エイジア)' },
                { id: 38, title: '雨のち晴れ' },
                { id: 39, title: 'Round About 〜孤独の肖像〜' },
                { id: 40, title: 'Over' },
                { id: 41, title: 'Tomorrow never knows' },
                { id: 42, title: 'everybody goes -秩序のない現代にドロップキック-' },
                { id: 43, title: '【es】〜Theme of es〜' },
                { id: 44, title: 'シーソーゲーム 〜勇敢な恋の歌〜' },
                { id: 45, title: 'フラジャイル' },
                { id: 46, title: '名もなき詩' },
                { id: 47, title: 'また会えるかな' },
                { id: 48, title: '花 -Mémento-Mori-' },
                { id: 49, title: 'シーラカンス' },
                { id: 50, title: '手紙' },
                { id: 51, title: 'ありふれたLove Story 〜男女問題はいつも面倒だ〜' },
                { id: 52, title: 'Mirror' },
                { id: 53, title: 'So Let’s Get Truth' },
                { id: 54, title: 'マシンガンをぶっ放せ' },
                { id: 55, title: 'ゆりかごのある丘から' },
                { id: 56, title: '虜' },
                { id: 57, title: '深海' },
                { id: 58, title: 'Love is Blindness' },
                { id: 59, title: '旅人' },
                { id: 60, title: 'Everything (It’s you)' },
                { id: 61, title: 'デルモ' },
                { id: 62, title: 'タイムマシーンに乗って' },
                { id: 63, title: 'Brandnew my lover' },
                { id: 64, title: '傘の下の君に告ぐ' },
                { id: 65, title: 'ALIVE' },
                { id: 66, title: '幸せのカテゴリー' },
                { id: 67, title: 'ボレロ' },
                { id: 68, title: 'ニシエヒガシエ' },
                { id: 69, title: '終わりなき旅' },
                { id: 70, title: 'Prism' },
                { id: 71, title: '光の射す方へ' },
                { id: 72, title: '独り言' },
                { id: 73, title: 'DISCOVERY' },
                { id: 74, title: 'アンダーシャツ' },
                { id: 75, title: 'Simple' },
                { id: 76, title: "I'll be" },
                { id: 77, title: '#2601' },
                { id: 78, title: 'ラララ' },
                { id: 79, title: 'Image' },
                { id: 80, title: 'Surrender' },
                { id: 81, title: '口笛' },
                { id: 82, title: 'Heavenly kiss' },
                { id: 83, title: 'NOT FOUND' },
                { id: 84, title: '1999年、夏、沖縄' },
                { id: 85, title: 'CENTER OF UNIVERSE' },
                { id: 86, title: 'その向こうへ行こう' },
                { id: 87, title: 'スロースターター' },
                { id: 88, title: 'つよがり' },
                { id: 89, title: '十二月のセントラルパークブルース' },
                { id: 90, title: '友と嘘とコーヒーと胃袋' },
                { id: 91, title: 'ロードムービー' },
                { id: 92, title: 'Everything is made from a dream' },
                { id: 93, title: 'Hallelujah' },
                { id: 94, title: '安らげる場所' },
                { id: 95, title: '優しい歌' },
                { id: 96, title: 'youthful days' },
                { id: 97, title: 'Drawing' },
                { id: 98, title: '君が好き' },
                { id: 99, title: 'さよなら2001年' },
                { id: 100, title: '蘇生' },
                { id: 101, title: 'Dear wonderful world' },
                { id: 102, title: 'one two three' },
                { id: 103, title: '渇いたkiss' },
                { id: 104, title: 'ファスナー' },
                { id: 105, title: 'Bird Cage' },
                { id: 106, title: 'LOVE はじめました' },
                { id: 107, title: 'UFO' },
                { id: 108, title: 'いつでも微笑みを' },
                { id: 109, title: "It's a wonderful world" },
                { id: 110, title: 'Any' },
                { id: 111, title: "I'm sorry" },
                { id: 112, title: 'HERO' },
                { id: 113, title: '空風の帰り道' },
                { id: 114, title: '掌' },
                { id: 115, title: 'くるみ' },
                { id: 116, title: '言わせてみてぇもんだ' },
                { id: 117, title: 'PADDLE' },
                { id: 118, title: '花言葉' },
                { id: 119, title: 'Pink 〜奇妙な夢' },
                { id: 120, title: '血の管' },
                { id: 121, title: '天頂バス' },
                { id: 122, title: 'タガタメ' },
                { id: 123, title: 'Sign' },
                { id: 124, title: '妄想満月' },
                { id: 125, title: 'こんな風にひどく蒸し暑い日' },
                { id: 126, title: '未来' },
                { id: 127, title: 'and I love you' },
                { id: 128, title: 'ランニングハイ' },
                { id: 129, title: 'ヨーイドン' },
                { id: 130, title: 'Worlds end' },
                { id: 131, title: 'Monster' },
                { id: 132, title: '僕らの音' },
                { id: 133, title: '靴ひも' },
                { id: 134, title: 'CANDY' },
                { id: 135, title: 'Door' },
                { id: 136, title: '跳べ' },
                { id: 137, title: '隔たり' },
                { id: 138, title: '潜水' },
                { id: 139, title: '箒星' },
                { id: 140, title: 'ほころび' },
                { id: 141, title: 'my sweet heart' },
                { id: 142, title: 'しるし' },
                { id: 143, title: 'ひびき' },
                { id: 144, title: 'フェイク' },
                { id: 145, title: 'Wake me up!' },
                { id: 146, title: '彩り' },
                { id: 147, title: 'Another Story' },
                { id: 148, title: 'PIANO MAN' },
                { id: 149, title: 'もっと' },
                { id: 150, title: 'やわらかい風' },
                { id: 151, title: 'ポケット カスタネット' },
                { id: 152, title: 'SUNRISE' },
                { id: 153, title: '通り雨' },
                { id: 154, title: 'あんまり覚えてないや' },
                { id: 155, title: '旅立ちの唄' },
                { id: 156, title: '羊、吠える' },
                { id: 157, title: 'GIFT' },
                { id: 158, title: '横断歩道を渡る人たち' },
                { id: 159, title: '風と星とメビウスの輪' },
                { id: 160, title: 'HANABI' },
                { id: 161, title: 'タダダキアッテ' },
                { id: 162, title: '夏が終わる 〜夏の日のオマージュ〜' },
                { id: 163, title: '花の匂い' },
                { id: 164, title: '終末のコンフィデンスソング' },
                { id: 165, title: 'エソラ' },
                { id: 166, title: '声' },
                { id: 167, title: '少年' },
                { id: 168, title: '口がすべって' },
                { id: 169, title: '水上バス' },
                { id: 170, title: '東京' },
                { id: 171, title: 'ロックンロール' },
                { id: 172, title: 'fanfare' },
                { id: 173, title: 'I' },
                { id: 174, title: '擬態' },
                { id: 175, title: 'HOWL' },
                { id: 176, title: "I'm talking about Lovin'" },
                { id: 177, title: '365日' },
                { id: 178, title: 'ロックンロールは生きている' },
                { id: 179, title: 'ロザリータ' },
                { id: 180, title: '蒼' },
                { id: 181, title: 'ハル' },
                { id: 182, title: 'Prelude' },
                { id: 183, title: 'Forever' },
                { id: 184, title: 'かぞえうた' },
                { id: 185, title: '祈り 〜涙の軌道' },
                { id: 186, title: 'End of the day' },
                { id: 187, title: 'pieces' },
                { id: 188, title: 'hypnosis' },
                { id: 189, title: 'Marshmallow day' },
                { id: 190, title: '常套句' },
                { id: 191, title: 'イミテーションの木' },
                { id: 192, title: 'インマイタウン' },
                { id: 193, title: '過去と未来と交信する男' },
                { id: 194, title: 'Happy Song' },
                { id: 195, title: 'REM' },
                { id: 196, title: '放たれる' },
                { id: 197, title: '足音 〜Be Strong' },
                { id: 198, title: 'Melody' },
                { id: 199, title: 'fantasy' },
                { id: 200, title: 'FIGHT CLUB' },
                { id: 201, title: '斜陽' },
                { id: 202, title: '蜘蛛の糸' },
                { id: 203, title: 'I Can Make It' },
                { id: 204, title: "ROLLIN' ROLLING 〜一見は百聞に如かず" },
                { id: 205, title: '街の風景' },
                { id: 206, title: '運命' },
                { id: 207, title: '忘れ得ぬ人' },
                { id: 208, title: 'You make me happy' },
                { id: 209, title: 'Jewelry' },
                { id: 210, title: 'WALTZ' },
                { id: 211, title: '進化論' },
                { id: 212, title: '幻聴' },
                { id: 213, title: '遠くへと' },
                { id: 214, title: 'I wanna be there' },
                { id: 215, title: 'Starting Over' },
                { id: 216, title: '未完' },
                { id: 217, title: 'ヒカリノアトリエ' },
                { id: 218, title: 'himawari' },
                { id: 219, title: '忙しい僕ら' },
                { id: 220, title: 'here comes my love' },
                { id: 221, title: 'Your Song' },
                { id: 222, title: '海にて、心は裸になりたがる' },
                { id: 223, title: 'SINGLES' },
                { id: 224, title: '箱庭' },
                { id: 225, title: 'addiction' },
                { id: 226, title: 'day by day (愛犬クルの物語)' },
                { id: 227, title: '秋がくれた切符' },
                { id: 228, title: '皮膚呼吸' },
                { id: 229, title: 'Birthday' },
                { id: 230, title: '君と重ねたモノローグ' },
                { id: 231, title: 'turn over?' },
                { id: 232, title: 'DANCING SHOES' },
                { id: 233, title: 'Brand new planet' },
                { id: 234, title: 'losstime' },
                { id: 235, title: 'Documentary film' },
                { id: 236, title: 'others' },
                { id: 237, title: 'The song of praise' },
                { id: 238, title: 'memories' },
                { id: 239, title: '永遠' },
                { id: 240, title: '生きろ' },
                { id: 241, title: 'I MISS YOU' },
                { id: 242, title: "Fifty's map 〜おとなの地図" },
                { id: 243, title: '青いリンゴ' },
                { id: 244, title: 'Are you sleeping well without me?' },
                { id: 245, title: 'LOST' },
                { id: 246, title: 'アート＝神の見えざる手' },
                { id: 247, title: '雨の日のパレード' },
                { id: 248, title: 'Party is over' },
                { id: 249, title: 'We have no time' },
                { id: 250, title: 'ケモノミチ' },
                { id: 251, title: '黄昏と積み木' },
                { id: 252, title: 'deja-vu' },
                { id: 253, title: 'おはよう' },

            ];

            // Bloodhoundの設定
            var songs = new Bloodhound({
                datumTokenizer: Bloodhound.tokenizers.obj.whitespace('title'),
                queryTokenizer: Bloodhound.tokenizers.whitespace,
                local: dummyData
            });

            // Typeahead.jsの設定
            $('#searchInput').typeahead({
                hint: true,
                highlight: true,
                minLength: 1
            },
            {
                name: 'songs',
                display: 'title',
                source: songs
            });

            // 検索ボタンがクリックされたときの処理
            $('#searchButton').click(function() {
                var query = $('#searchInput').val();
                var results = dummyData.filter(function(item) {
                    return item.title.toLowerCase().includes(query.toLowerCase());
                });

                var html = '<ul>';
                results.forEach(function(result) {
                    html += '<li>' + result.title + '</li>';
                });
                html += '</ul>';
                $('#searchResults').html(html);
            });

            // Typeahead.jsで候補が選択されたときの処理
            $('#searchInput').on('typeahead:select', function(event, data) {
                // 検索結果を表示するためのAPIなどを呼び出す
                var results = dummyData.filter(function(item) {
                    return item.id === data.id;
                });

                var html = '<ul>';
                results.forEach(function(result) {
                    html += '<li>' + result.title + '</li>';
                });
                html += '</ul>';
                $('#searchResults').html(html);
            });
        });
    </script>
</body>
</html>