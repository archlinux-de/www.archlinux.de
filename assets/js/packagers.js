require('../css/packagers.scss');
var $ = require('jquery');
require('datatables.net');
var language = require('./lang-loader!datatables.net-plugins/i18n/German.lang');

$(document).ready(function () {
    var table = $('#packagers').DataTable({
        "language": language,
        "paging": false,
        "order": [[2, "desc"]],
        "ajax": ajax,
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
                "render": function (data, type, row) {
                    if (type == 'display') {
                        return '<div title="' + data + '" style="width:100px;">\n' +
                            '<div style="background-color:#1793d1;width:'
                            + (Math.ceil(100 * data / table.ajax.json().packages)) + 'px;">&nbsp;</div></div>';
                    }
                    return data;
                }
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
