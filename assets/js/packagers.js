require('../css/packagers.scss');
var $ = require('jquery');
require('datatables.net');
require('datatables.net-bs4');
var language = require('./lang-loader!datatables.net-plugins/i18n/German.lang');

$(document).ready(function () {
    $('#packagers').DataTable({
        "language": language,
        "paging": false,
        "order": [[2, "desc"]],
        "columns": [
            {
                "data": "name"
            },
            {
                "data": "email",
                "render": function (data, type, row) {
                    if (type == 'display' && data) {
                        return '<a href="mailto:' + data + '">' + data + '</a>';
                    }
                    return data;
                },
                "className": "d-none d-md-table-cell"
            },
            {
                "data": "packages",
                "searchable": false
            },
            {
                "data": "lastbuilddate",
                "searchable": false,
                "render": function (data, type, row) {
                    if (type == 'display' && data) {
                        var date = new Date(data * 1000);
                        return date.toLocaleDateString('de-DE')
                            + '<span class="d-none d-xl-inline text-nowrap">, ' + date.toLocaleTimeString('de-DE') + '</span>';
                    }
                    return data;
                },
                "className": "d-none d-lg-table-cell"
            }
        ]
    });
});
