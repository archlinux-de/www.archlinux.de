require(['js-autocomplete'], function (autoComplete) {
    new autoComplete({
        selector: '#searchfield',
        delay: 100,
        minChars: 1,
        source: function (term, callback) {
            fetch(autoSuggestRoute + '?term=' + encodeURI(term))
                .then(function (response) {
                    if (!response.ok) {
                        throw new Error();
                    }
                    return response.json();
                })
                .then(function (data) {
                    return callback(data);
                })
                .catch(function () {
                    return callback([]);
                });
        }
    });
});
