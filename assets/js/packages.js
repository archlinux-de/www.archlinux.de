import $ from 'jquery'
import 'datatables.net'
import 'datatables.net-bs4'
import language from 'datatables.net-plugins/i18n/German.lang'

class Renderer {
  static renderTime (data, type) {
    if (data) {
      const date = new Date(data)
      if (type === 'display') {
        return `${date.toLocaleDateString('de-DE')}
                <span class="d-none d-xl-inline text-nowrap">, ${date.toLocaleTimeString('de-DE')}</span>`
      }
      return date.getTime()
    }
    return data
  }

  static renderRepository (repositoryUrlTemplate) {
    return function (data, type) {
      if (type === 'display' && data) {
        const repositoryUrl = repositoryUrlTemplate
          .replace('_repository_', data)
        return `<a href="${repositoryUrl}">${data}</a>`
      }
      return data
    }
  }

  static renderName (packageUrlTemplate) {
    return function (data, type, row) {
      if (type === 'display' && data) {
        const packageUrl = packageUrlTemplate
          .replace('_repository_', row.repository.name)
          .replace('_architecture_', row.repository.architecture)
          .replace('_package_', data)
        return `<a href="${packageUrl}">${data}</a>`
      }
      return data
    }
  }
}

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
    'order': [[6, 'desc']],
    'searchDelay': 100,
    'pagingType': 'numbers',
    'columns': [
      {
        'data': 'repository.name',
        'orderable': true,
        'searchable': true,
        'className': 'd-none d-lg-table-cell',
        'render': Renderer.renderRepository(repositoryUrlTemplate)
      },
      {
        'data': 'repository.architecture',
        'orderable': false,
        'searchable': false,
        'visible': false
      },
      {
        'data': 'architecture',
        'orderable': false,
        'searchable': false,
        'className': 'd-none d-xl-table-cell'
      },
      {
        'data': 'name',
        'orderable': true,
        'searchable': true,
        'render': Renderer.renderName(packageUrlTemplate),
        'className': 'text-break'
      },
      {
        'data': 'version',
        'orderable': false,
        'searchable': false,
        'className': 'text-break'
      },
      {
        'data': 'description',
        'orderable': false,
        'searchable': true,
        'className': 'text-break d-none d-sm-table-cell'
      },
      {
        'data': 'builddate',
        'orderable': true,
        'searchable': false,
        'render': Renderer.renderTime,
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
