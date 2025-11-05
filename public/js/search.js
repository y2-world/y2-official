$(document).ready(function(){
    // Typeaheadの初期化
    var songs = new Bloodhound({
        datumTokenizer: Bloodhound.tokenizers.obj.whitespace('title'),
        queryTokenizer: Bloodhound.tokenizers.whitespace,
        remote: {
            url: '/find?q=%QUERY',
            wildcard: '%QUERY'
        }
    });

    $('#searchInput').typeahead({
        minLength: 1,
        highlight: true
    },
    {
        name: 'songs',
        display: 'title',
        source: songs
    }).on('typeahead:selected', function(event, data) {
        // 選択された曲の詳細ページにリダイレクト
        window.location.href = '/database/songs/' + data.id;
    });
});