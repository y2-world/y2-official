$(document).ready(function(){
    // Typeaheadの初期化
    var songs = new Bloodhound({
        datumTokenizer: Bloodhound.tokenizers.obj.whitespace('title'),
        queryTokenizer: Bloodhound.tokenizers.whitespace,
        remote: {
            url: '/find?q=%QUERY',
            wildcard: '%QUERY'
        },
        limit: 20
    });

    $('#searchInput').typeahead({
        minLength: 1,
        highlight: true,
        hint: true,
        classNames: {
            menu: 'tt-menu-modern',
            suggestion: 'tt-suggestion-modern',
            cursor: 'tt-cursor-modern'
        }
    },
    {
        name: 'songs',
        display: 'title',
        source: songs,
        limit: 20,
        templates: {
            empty: '<div class="tt-empty">該当する曲が見つかりません</div>',
            suggestion: function(data) {
                return '<div class="tt-suggestion-content"><i class="fa-solid fa-music"></i><span>' + data.title + '</span></div>';
            }
        }
    }).on('typeahead:selected', function(event, data) {
        // 選択された曲の詳細ページにリダイレクト
        window.location.href = '/database/songs/' + data.id;
    });
});