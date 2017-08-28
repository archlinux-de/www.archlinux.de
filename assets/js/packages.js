import 'jquery';
import 'datatables.net';
import 'datatables.net-bs4';
import language from './lang-loader!datatables.net-plugins/i18n/German.lang';

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
                        if (type === 'display') {
                            return '<a href="' + row.url + '">' + data + '</a>';
                        }
                        return data;
                    },
                    "className": "text-nowrap"
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
                    "className": "mw-50vw d-none d-sm-table-cell"
                },
                {
                    "data": "builddate",
                    "orderable": true,
                    "searchable": false,
                    "render": function (data, type, row) {
                        if (type === 'display') {
                            const date = new Date(data * 1000);
                            return date.toLocaleDateString('de-DE')
                                + '<span class="d-none d-xl-inline text-nowrap">, ' + date.toLocaleTimeString('de-DE') + '</span>';
                        }
                        return data;
                    },
                    "className": "d-none d-lg-table-cell"
                },
                {
                    "data": "packager",
                    "orderable": false,
                    "searchable": true,
                    "visible": false
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
