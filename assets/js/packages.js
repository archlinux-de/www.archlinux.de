import $ from 'jquery'
import 'datatables.net'
import 'datatables.net-bs4'
import language from 'datatables.net-plugins/i18n/German.lang'

$(document).ready(function () {
  const dataTable = $('#packages')
  const packageUrlTemplate = dataTable.data('packageUrlTemplate')
  const repositoryUrlTemplate = dataTable.data('repositoryUrlTemplate')
  dataTable.DataTable({
    'language': language,
    'lengthMenu': [25, 50, 100],
    'pageLength': 25,
    'processing': false,
    'serverSide': true,
    'order': [[5, 'desc']],
    'searchDelay': 100,
    'columns': [
      {
        'data': 'repository.name',
        'orderable': true,
        'searchable': true,
        'className': 'd-none d-lg-table-cell',
        'render': function (data, type, row) {
          if (type === 'display') {
            const repositoryUrl = repositoryUrlTemplate
              .replace('_repository_', data)
            return `<a href="${repositoryUrl}">${data}</a>`
          }
          return data
        }
      },
      {
        'data': 'architecture',
        'orderable': true,
        'searchable': true,
        'className': 'd-none d-xl-table-cell'
      },
      {
        'data': 'name',
        'orderable': true,
        'searchable': true,
        'render': function (data, type, row) {
          if (type === 'display') {
            const packageUrl = packageUrlTemplate
              .replace('_repository_', row.repository.name)
              .replace('_architecture_', row.repository.architecture)
              .replace('_package_', data)
            return `<a href="${packageUrl}">${data}</a>`
          }
          return data
        },
        'className': 'text-nowrap'
      },
      {
        'data': 'version',
        'orderable': false,
        'searchable': false,
        'className': 'break-word mw-10vw'
      },
      {
        'data': 'description',
        'orderable': false,
        'searchable': true,
        'className': 'mw-50vw d-none d-sm-table-cell'
      },
      {
        'data': 'builddate',
        'orderable': true,
        'searchable': false,
        'render': function (data, type) {
          if (type === 'display') {
            const date = new Date(data)
            return `${date.toLocaleDateString('de-DE')}
                <span class="d-none d-xl-inline text-nowrap">, ${date.toLocaleTimeString('de-DE')}</span>`
          }
          return data
        },
        'className': 'd-none d-lg-table-cell'
      },
      {
        'data': 'groups',
        'orderable': false,
        'searchable': true,
        'visible': false
      }
    ]
  })
})
