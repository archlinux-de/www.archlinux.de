import '@/js/base'
import '@/css/start.scss'

import $ from 'jquery'
import AutoComplete from 'js-autocomplete'

$(document).ready(() => {
  const searchInput = document.querySelector('#searchfield')
  const suggest = searchInput.dataset.suggest
  AutoComplete({
    selector: searchInput,
    delay: 100,
    minChars: 1,
    source: (term, response) => {
      $.getJSON(suggest, { term: term }, data => response(data))
    }
  })
})
