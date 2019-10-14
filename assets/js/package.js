import '@/js/base'
import $ from 'jquery'

const fileList = $('#fileList')
const fileListToggle = $('#fileListToggle')
const showFileListEvent = 'show.bs.collapse'
const shownFileListEvent = 'shown.bs.collapse'

fileList.one(showFileListEvent, () => {
  fileListToggle.prop('disabled', true)
  const filesUrl = fileList.data('ajax')

  $.getJSON(filesUrl, files => {
    const fileListItems = $.map(files, file => {
      if (file.match(/\/$/)) {
        return [`<li class="text-muted">${file}</li>`]
      } else {
        return [`<li>${file}</li>`]
      }
    })

    if (fileListItems.length > 0) {
      fileList.append(fileListItems.join(''))
    } else {
      fileList.append('<li class="alert alert-info">Das Paket enthält keine Dateien</li>')
    }
  })
})

fileList.one(shownFileListEvent, () => {
  fileList.removeClass('d-none')
  fileListToggle.addClass('d-none')
})
