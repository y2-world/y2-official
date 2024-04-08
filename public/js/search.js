$(document).ready(function() {
    $('#searchInput').typeahead({
        source: function(query, result) {
            $.ajax({
                url: "{{ route('find.autocomplete') }}",
                method: "GET",
                data: {
                    query: query
                },
                dataType: "json",
                success: function(data) {
                    result($.map(data, function(item) {
                        return item;
                    }));
                }
            });
        }
    });

    $('#searchButton').click(function() {
        var query = $('#searchInput').val();
        $.ajax({
            url: "{{ route('find.autocomplete') }}",
            method: "GET",
            data: {
                query: query
            },
            dataType: "json",
            success: function(data) {
                var html = '<ul>';
                $.each(data, function(index, value) {
                    html += '<li>' + value + '</li>';
                });
                html += '</ul>';
                $('#searchResults').html(html);
            }
        });
    });
});