
window.addEventListener('load', function () {
    var autoComplete = require('js-autocomplete');
    var searchInput = document.getElementById('searchfield');
    var suggest = searchInput.dataset.suggest;
    new autoComplete({
        selector: searchInput,
        delay: 100,
        minChars: 1,
        source: function (term, callback) {
            fetch(suggest + '?term=' + encodeURI(term))
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
