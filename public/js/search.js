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

    $('.typeahead').typeahead({
        minLength: 1,
        highlight: true
    },
    {
        name: 'songs',
        display: 'name',
        source: songs,
        matcher: function(item) {
            // 入力されたテキストが候補の中に存在するかチェック
            var query = new RegExp('^' + $.fn.typeahead.escapeRegExChars(this.query) + '$', 'i');
            return query.test(item.name);
        }
    }).on('typeahead:selected', function(event, data) {
        // 選択された曲の詳細ページにリダイレクト
        window.location.href = '/songs/' + data.id;
    });
});