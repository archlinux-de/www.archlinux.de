require('../css/mirrors.scss');
var $ = require('jquery');
require('datatables.net');
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
                        return '<a href="' + row.url + '" rel="nofollow">' + data + '</a>';
                    }
                    return data;
                }
            },
            {
                "data": "country"
            },
            {
                "data": "durationAvg",
                "searchable": false
            },
            {
                "data": "delay",
                "searchable": false
            },
            {
                "data": "lastsync",
                "searchable": false,
                "render": function (data, type, row) {
                    if (type == 'display' && data) {
                        return new Date(data * 1000).toLocaleString('de-DE');
                    }
                    return data;
                }
            }
        ]
    });
});