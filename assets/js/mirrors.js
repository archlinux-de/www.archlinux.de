import '@/js/base'
import '@/js/_datatables'

import $ from 'jquery'
import language from 'datatables.net-plugins/i18n/German.lang'

const dataTable = $('#mirrors')

const renderDuration = (data, type) => {
  if (type === 'display' && data) {
    if (data < 0) {
      data = 0
    }

    let unit = 's'
    const secondsPerMinute = 60
    const secondsPerHour = secondsPerMinute * 60
    const secondsPerDay = secondsPerHour * 24
    if (data >= secondsPerDay) {
      unit = 'd'
      data = data / secondsPerDay
    } else if (data >= secondsPerHour) {
      unit = 'h'
      data = data / secondsPerHour
    } else if (data >= secondsPerMinute) {
      unit = 'min'
      data = data / secondsPerMinute
    }

    return new Intl.NumberFormat('de-DE').format(data) + ' ' + unit
  }
  return data
}

const renderBoolean = (data, type) => {
  if (type === 'display' && data != null) {
    return (data ? '<span class="text-success">✓</span>' : '<span class="text-danger">×</span>')
  }
  return data
}

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

const renderUrl = (data, type) => {
  if (type === 'display' && data) {
    return `<a href="${data}" rel="nofollow">${new window.URL(data).hostname}</a>`
  }
  return data
}

const renderCountry = data => {
  if (data && data.name) {
    return data.name
  }
  return data
}

dataTable.DataTable({
  language: language,
  lengthMenu: [25, 50, 100],
  pageLength: 25,
  order: [[4, 'desc']],
  pagingType: 'numbers',
  ajax: {
    cache: true,
    url: dataTable.data('ajaxUrl')
  },
  columns: [
    {
      data: 'url',
      orderable: false,
      render: renderUrl
    },
    {
      data: 'country',
      className: 'd-none d-md-table-cell',
      render: renderCountry
    },
    {
      data: 'durationAvg',
      searchable: false,
      className: 'd-none d-lg-table-cell',
      render: renderDuration
    },
    {
      data: 'delay',
      searchable: false,
      className: 'd-none d-lg-table-cell',
      render: renderDuration
    },
    {
      data: 'lastsync',
      searchable: false,
      className: 'd-none d-sm-table-cell',
      render: renderTime
    },
    {
      data: 'isos',
      searchable: false,
      className: 'd-none d-md-table-cell text-center',
      render: renderBoolean
    },
    {
      data: 'ipv4',
      searchable: false,
      className: 'd-none d-xl-table-cell text-center',
      render: renderBoolean
    },
    {
      data: 'ipv6',
      searchable: false,
      className: 'd-none d-md-table-cell text-center',
      render: renderBoolean
    },
    {
      data: 'active',
      searchable: false,
      className: 'd-none d-xl-table-cell text-center',
      render: renderBoolean
    }
  ]
})
