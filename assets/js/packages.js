import '@/js/base'
import '@/js/_datatables'

import $ from 'jquery'
import language from 'datatables.net-plugins/i18n/German.lang'

$(document).ready(() => {
  const dataTable = $('#packages')
  const packageUrlTemplate = dataTable.data('packageUrlTemplate')
  const repositoryUrlTemplate = dataTable.data('repositoryUrlTemplate')

  const renderTime = (data, type) => {
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

  const createRenderRepository = repositoryUrlTemplate => (data, type) => {
    if (type === 'display' && data) {
      const repositoryUrl = repositoryUrlTemplate
        .replace('_repository_', encodeURI(data))
      return `<a href="${repositoryUrl}">${data}</a>`
    }
    return data
  }

  const createRenderName = packageUrlTemplate => (data, type, row) => {
    if (type === 'display' && data) {
      const packageUrl = packageUrlTemplate
        .replace('_repository_', row.repository.name)
        .replace('_architecture_', row.repository.architecture)
        .replace('_package_', encodeURI(data))
      return `<a href="${packageUrl}">${data}</a>`
    }
    return data
  }

  dataTable.DataTable({
    language: language,
    lengthMenu: [25, 50, 100],
    pageLength: 25,
    processing: false,
    serverSide: true,
    order: [[6, 'desc']],
    searchDelay: 100,
    pagingType: 'numbers',
    ajax: {
      cache: true,
      url: dataTable.data('ajaxUrl')
    },
    columns: [
      {
        data: 'repository.name',
        orderable: true,
        searchable: true,
        className: 'd-none d-lg-table-cell',
        render: createRenderRepository(repositoryUrlTemplate)
      },
      {
        data: 'repository.architecture',
        orderable: false,
        searchable: false,
        visible: false
      },
      {
        data: 'architecture',
        orderable: false,
        searchable: false,
        className: 'd-none d-xl-table-cell'
      },
      {
        data: 'name',
        orderable: true,
        searchable: true,
        render: createRenderName(packageUrlTemplate),
        className: 'text-break'
      },
      {
        data: 'version',
        orderable: false,
        searchable: false,
        className: 'text-break'
      },
      {
        data: 'description',
        orderable: false,
        searchable: true,
        className: 'text-break d-none d-sm-table-cell'
      },
      {
        data: 'builddate',
        orderable: true,
        searchable: false,
        render: renderTime,
        className: 'd-none d-lg-table-cell'
      },
      {
        data: 'groups',
        orderable: false,
        searchable: true,
        visible: false
      }
    ]
  })
})
