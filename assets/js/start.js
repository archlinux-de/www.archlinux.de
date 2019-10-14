import '@/js/base'
import '@/css/start.scss'

import AutoComplete from 'js-autocomplete'

const searchInput = document.querySelector('#searchfield')
const suggestApiUrl = searchInput.dataset.suggest
AutoComplete({
  selector: searchInput,
  delay: 100,
  minChars: 1,
  source: (term, response) => {
    fetch(
      `${suggestApiUrl}?term=${encodeURI(term)}`,
      { credentials: 'omit', headers: { Accept: 'application/json' } }
    )
      .then(response => response.json())
      .then(data => response(data))
  }
})
