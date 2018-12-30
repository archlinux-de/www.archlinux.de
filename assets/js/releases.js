import $ from 'jquery'
import 'datatables.net'
import 'datatables.net-bs4'
import language from 'datatables.net-plugins/i18n/German.lang'

$(document).ready(function () {
  const dataTable = $('#releases')
  const releaseUrlTemplate = dataTable.data('releaseUrlTemplate')
  dataTable.DataTable({
    'language': language,
    'lengthMenu': [25, 50, 100],
    'pageLength': 25,
    'processing': false,
    'serverSide': true,
    'order': [[1, 'desc']],
    'searchDelay': 100,
    'columns': [
      {
        'data': 'version',
        'orderable': true,
        'searchable': true,
        'render': function (data, type, row) {
          if (type === 'display' && data) {
            const releaseUrl = releaseUrlTemplate
              .replace('1_version_', row.version)
            return `<a href="${releaseUrl}">${data}</a>`
          }
          return data
        }
      },
      {
        'data': 'releaseDate',
        'orderable': true,
        'searchable': false,
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
        'data': 'kernelVersion',
        'orderable': false,
        'searchable': true,
        'className': 'd-none d-xl-table-cell'
      },
      {
        'data': 'available',
        'orderable': false,
        'searchable': false,
        'className': 'd-none d-md-table-cell',
        'render': function (data, type, row) {
          if (type === 'display' && data != null) {
            return (data ? '<span class="text-success">✓</span>' : '<span class="text-danger">×</span>')
          }
          return data
        }
      }
    ]
  })
})
