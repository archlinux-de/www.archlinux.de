require('../css/packages.scss');
var $ = require('jquery');
require('datatables.net');
var language = require('./lang-loader!datatables.net-plugins/i18n/German.lang');

$(document).ready(function () {
        $('#packages').DataTable({
            "language": language,
            "lengthMenu": [25, 50, 100],
            "pageLength": 25,
            "processing": false,
            "serverSide": true,
            "order": [[5, "desc"]],
            "ajax": ajax,
            "search": search,
            "searchDelay": 100,
            "searchCols": [
                null,
                {"search": default_architecture},
                null,
                null,
                null,
                null
            ],
            "columns": [
                {
                    "data": "repository",
                    "orderable": true,
                    "searchable": true
                },
                {
                    "data": "architecture",
                    "orderable": true,
                    "searchable": true
                },
                {
                    "data": "name",
                    "orderable": true,
                    "searchable": true,
                    "render": function (data, type, row) {
                        return '<a href="' + row.url + '">' + data + '</a>';
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
                    "className": "packages-description"
                },
                {
                    "data": "builddate",
                    "orderable": true,
                    "searchable": false,
                    "render": function (data, type, row) {
                        return new Date(data * 1000).toLocaleString('de-DE');
                    }
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
