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
        highlight: true,
        hint: false
    },
    {
        name: 'songs',
        display: 'title',
        source: songs,
        templates: {
            suggestion: function(data) {
                return '<div>' + data.title + '</div>';
            }
        }
    }).on('typeahead:selected', function(event, data) {
        // 選択された曲の詳細ページにリダイレクト
        window.location.href = '/database/songs/' + data.id;
    }).on('typeahead:asyncrequest', function(event, query) {
        console.log('=== Typeahead Request ===');
        console.log('Query:', query);
        console.log('Input value:', $('#searchInput').val());
        console.log('URL:', '/find?q=' + encodeURIComponent(query || $('#searchInput').val() || ''));
        console.log('Timestamp:', new Date().toISOString());
    }).on('typeahead:asynccancel', function(event, query) {
        console.log('=== Typeahead Request Cancelled ===');
        console.log('Query:', query);
        console.log('Input value:', $('#searchInput').val());
        console.log('Timestamp:', new Date().toISOString());
    }).on('typeahead:asyncreceive', function(event, query) {
        console.log('=== Typeahead Response Received ===');
        console.log('Query:', query);
        console.log('Input value:', $('#searchInput').val());
        // データはBloodhoundのfilter関数で処理された後、Typeaheadに渡される
        // 実際のデータは、typeahead:renderイベントで確認できる
        console.log('Timestamp:', new Date().toISOString());
    }).on('typeahead:render', function(event, suggestions) {
        console.log('=== Typeahead Render ===');
        console.log('Suggestions:', suggestions);
        console.log('Suggestions count:', suggestions ? suggestions.length : 0);
        if (suggestions && suggestions.length > 0) {
            console.log('First suggestion:', suggestions[0]);
        }
    });
});