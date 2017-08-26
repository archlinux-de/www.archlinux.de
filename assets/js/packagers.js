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
                }
            },
            {
                "data": "packages",
                "searchable": false,
            },
            {
                "data": "lastbuilddate",
                "searchable": false,
                "render": function (data, type, row) {
                    if (type == 'display' && data) {
                        return new Date(data * 1000).toLocaleDateString('de-DE');
                    }
                    return data;
                }
            }
        ]
    });
});
