import $ from 'jquery'
import 'datatables.net'
import 'datatables.net-bs4'
import language from 'datatables.net-plugins/i18n/German.lang'

$(document).ready(function () {
  $('#mirrors').DataTable({
    'language': language,
    'lengthMenu': [25, 50, 100],
    'pageLength': 25,
    'order': [[4, 'desc']],
    'columns': [
      {
        'data': 'url',
        'render': function (data, type, row) {
          if (type === 'display') {
            return `<a href="${row.url}" rel="nofollow">${new window.URL(data).hostname}</a>`
          }
          return data
        }
      },
      {
        'data': 'country',
        'className': 'd-none d-md-table-cell',
        'render': function (data, type, row) {
          if (type === 'display' && data) {
            return data.name
          }
          return data
        }
      },
      {
        'data': 'durationAvg',
        'searchable': false,
        'className': 'd-none d-lg-table-cell',
        'render': function (data, type, row) {
          if (type === 'display' && data) {
            return new Intl.NumberFormat('de-DE').format(data) + 's'
          }
          return data
        }
      },
      {
        'data': 'delay',
        'searchable': false,
        'className': 'd-none d-lg-table-cell',
        'render': function (data, type, row) {
          if (type === 'display' && data) {
            return new Intl.NumberFormat('de-DE').format(data) + 's'
          }
          return data
        }
      },
      {
        'data': 'lastsync',
        'searchable': false,
        'className': 'd-none d-sm-table-cell',
        'render': function (data, type) {
          if (type === 'display' && data) {
            const date = new Date(data)
            return `${date.toLocaleDateString('de-DE')}
                <span class="d-none d-xl-inline text-nowrap">, ${date.toLocaleTimeString('de-DE')}</span>`
          }
          return data
        }
      },
      {
        'data': 'isos',
        'searchable': false,
        'className': 'd-none d-md-table-cell text-center',
        'render': function (data, type, row) {
          if (type === 'display') {
            return (data ? '<span class="text-success">✓</span>' : '<span class="text-danger">×</span>')
          }
          return data
        }
      },
      {
        'data': 'ipv4',
        'searchable': false,
        'className': 'd-none d-xl-table-cell text-center',
        'render': function (data, type, row) {
          if (type === 'display') {
            return (data ? '<span class="text-success">✓</span>' : '<span class="text-danger">×</span>')
          }
          return data
        }
      },
      {
        'data': 'ipv6',
        'searchable': false,
        'className': 'd-none d-md-table-cell text-center',
        'render': function (data, type, row) {
          if (type === 'display') {
            return (data ? '<span class="text-success">✓</span>' : '<span class="text-danger">×</span>')
          }
          return data
        }
      },
      {
        'data': 'active',
        'searchable': false,
        'className': 'd-none d-xl-table-cell text-center',
        'render': function (data, type, row) {
          if (type === 'display') {
            return (data ? '<span class="text-success">✓</span>' : '<span class="text-danger">×</span>')
          }
          return data
        }
      }
    ]
  })
})
