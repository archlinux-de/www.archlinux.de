import '@/js/base'
import $ from 'jquery'

const fileList = $('#fileList')
const filesUrl = fileList.data('url')
const fileListToggle = $('#fileListToggle')

fileList.one('show.bs.collapse', () => {
  fileListToggle.prop('disabled', true)

  fetch(filesUrl, { credentials: 'omit', headers: { Accept: 'application/json' } })
    .then(response => response.json())
    .then(files => files.map(file => file.match(/\/$/) ? `<li class="text-muted">${file}</li>` : `<li>${file}</li>`))
    .then(files => files.length < 1 ? ['<div class="alert alert-warning">Das Paket enthält keine Dateien</div>'] : files)
    .then(files => fileList.append(files))
})

fileList.one('shown.bs.collapse', () => {
  fileList.removeClass('d-none')
  fileListToggle.addClass('d-none')
})
