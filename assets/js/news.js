import $ from 'jquery'
import 'datatables.net'
import 'datatables.net-bs4'
import language from 'datatables.net-plugins/i18n/German.lang'

$(document).ready(function () {
  const dataTable = $('#news')
  const newsItemUrlTemplate = dataTable.data('newsItemUrlTemplate')
  dataTable.DataTable({
    'language': language,
    'lengthMenu': [25, 50, 100],
    'pageLength': 25,
    'processing': false,
    'serverSide': true,
    'order': [[0, 'desc']],
    'searchDelay': 100,
    'pagingType': 'numbers',
    'columns': [
      {
        'data': 'lastModified',
        'orderable': true,
        'searchable': false,
        'className': 'd-none d-md-table-cell',
        'render': function (data, type, row) {
          if (data) {
            const date = new Date(data)
            if (type === 'display') {
              return date.toLocaleDateString('de-DE')
            }
            return date.getTime()
          }
          return data
        }
      },
      {
        'data': 'title',
        'orderable': false,
        'searchable': true,
        'render': function (data, type, row) {
          if (type === 'display' && data) {
            const newsItemUrl = newsItemUrlTemplate
              .replace('1-slug', row.slug)
            return `<a href="${newsItemUrl}">${data}</a>`
          }
          return data
        }
      },
      {
        'data': 'author.name',
        'orderable': false,
        'searchable': true,
        'className': 'd-none d-xl-table-cell',
        'render': function (data, type, row) {
          if (type === 'display' && data) {
            return `<a href="${row.author.uri}">${data}</a>`
          }
          return data
        }
      }
    ]
  })
})
