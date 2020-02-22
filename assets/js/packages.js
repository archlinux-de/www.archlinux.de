import '@/js/base'
import '@/js/_datatables'

import $ from 'jquery'
import language from 'datatables.net-plugins/i18n/German.lang'

const dataTable = $('#packages')
const ajaxUrl = dataTable.data('ajaxUrl')

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

const createRenderRepository = (data, type, row) => {
  if (type === 'display' && data) {
    return `<a href="${row.repository._url}">${data}</a>`
  }
  return data
}

const createRenderName = (data, type, row) => {
  if (type === 'display' && data) {
    return `<a href="${row._url}">${data}</a>`
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
  ajax: { cache: true, url: ajaxUrl },
  columns: [
    {
      data: 'repository.name',
      orderable: true,
      searchable: true,
      className: 'd-none d-lg-table-cell',
      render: createRenderRepository
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
      render: createRenderName,
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
      data: 'buildDate',
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
