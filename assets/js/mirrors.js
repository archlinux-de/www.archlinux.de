require('../css/mirrors.scss');
var $ = require('jquery');
require('datatables.net');
require('datatables.net-bs4');
var language = require('./lang-loader!datatables.net-plugins/i18n/German.lang');

$(document).ready(function () {
    $('#mirrors').DataTable({
        "language": language,
        "lengthMenu": [25, 50, 100],
        "pageLength": 25,
        "order": [[4, "desc"]],
        "columns": [
            {
                "data": "url",
                "render": function (data, type, row) {
                    if (type == 'display') {
                        return '<a href="' + row.url + '" rel="nofollow">' + new URL(data).hostname + '</a>';
                    }
                    return data;
                }
            },
            {
                "data": "country",
                "className": "d-none d-md-table-cell"
            },
            {
                "data": "durationAvg",
                "searchable": false,
                "className": "d-none d-lg-table-cell"
            },
            {
                "data": "delay",
                "searchable": false,
                "className": "d-none d-lg-table-cell"
            },
            {
                "data": "lastsync",
                "searchable": false,
                "render": function (data, type, row) {
                    if (type == 'display' && data) {
                        var date = new Date(data * 1000);
                        return date.toLocaleDateString('de-DE')
                            + '<span class="d-none d-xl-inline text-nowrap">, ' + date.toLocaleTimeString('de-DE') + '</span>';
                    }
                    return data;
                },
                "className": "d-none d-sm-table-cell"
            }
        ]
    });
});
