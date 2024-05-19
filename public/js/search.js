$(document).ready(function(){
    // Typeaheadの初期化
    var songs = new Bloodhound({
        datumTokenizer: Bloodhound.tokenizers.obj.whitespace('name'),
        queryTokenizer: Bloodhound.tokenizers.whitespace,
        remote: {
            url: '/search?q=%QUERY',
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
        source: songs,
        matcher: function(item) {
            // 入力されたテキストが候補の中に存在するかチェック
            var query = new RegExp('^' + $.fn.typeahead.escapeRegExChars(this.query) + '$', 'i');
            return query.test(item.title);
        }
    }).on('typeahead:selected', function(event, data) {
        // 選択された曲の詳細ページにリダイレクト
        window.location.href = '/database/songs/' + data.id;
    });
});