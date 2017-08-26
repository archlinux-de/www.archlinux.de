require('../css/packages.scss');
var $ = require('jquery');
require('datatables.net');
require('datatables.net-bs4');
var language = require('./lang-loader!datatables.net-plugins/i18n/German.lang');

$(document).ready(function () {
        $('#packages').DataTable({
            "language": language,
            "lengthMenu": [25, 50, 100],
            "pageLength": 25,
            "processing": false,
            "serverSide": true,
            "order": [[5, "desc"]],
            "searchDelay": 100,
            "columns": [
                {
                    "data": "repository",
                    "orderable": true,
                    "searchable": true,
                    "className": "d-none d-lg-table-cell"
                },
                {
                    "data": "architecture",
                    "orderable": true,
                    "searchable": true,
                    "className": "d-none d-xl-table-cell"
                },
                {
                    "data": "name",
                    "orderable": true,
                    "searchable": true,
                    "render": function (data, type, row) {
                        if (type == 'display') {
                            return '<a href="' + row.url + '">' + data + '</a>';
                        }
                        return data;
                    }
                },
                {
                    "data": "version",
                    "orderable": false,
                    "searchable": false
                },
                {
                    "data": "description",
                    "orderable": false,
                    "searchable": true,
                    "className": "text-truncate"
                },
                {
                    "data": "builddate",
                    "orderable": true,
                    "searchable": false,
                    "render": function (data, type, row) {
                        if (type == 'display') {
                            return new Date(data * 1000).toLocaleString('de-DE');
                        }
                        return data;
                    },
                    "className": "d-none d-lg-table-cell"
                }
            ],
            "createdRow": function (row, data, index) {
                if (row.testing) {
                    $(row).addClass('less');
                }
            }
        })
    }
);
