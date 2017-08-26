import AutoComplete from 'js-autocomplete';

$(document).ready(function () {
    const searchInput = document.getElementById('searchfield');
    const suggest = searchInput.dataset.suggest;
    new AutoComplete({
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
