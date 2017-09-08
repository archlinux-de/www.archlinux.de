import $ from 'jquery'
import AutoComplete from 'js-autocomplete'

$(document).ready(function () {
  const searchInput = document.getElementById('searchfield')
  const suggest = searchInput.dataset.suggest
  AutoComplete({
    selector: searchInput,
    delay: 100,
    minChars: 1,
    source: function (term, response) {
      $.getJSON(suggest, {term: term}, function (data) { response(data) })
    }
  })
})
