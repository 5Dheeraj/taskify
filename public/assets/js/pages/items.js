
'use strict';
function queryParams(p) {
    return {
        "unit_id": $('#unit_filter').val(),
        page: p.offset / p.limit + 1,
        limit: p.limit,
        sort: p.sort,
        order: p.order,
        offset: p.offset,
        search: p.search
    };
}
$(document).ready(function () {
    $('#unit_filter').select2();
})
$('#unit_filter').on('change', function (e) {
    e.preventDefault();
    $('#table').bootstrapTable('refresh');
});


window.icons = {
    refresh: 'bx-refresh',
    toggleOn: 'bx-toggle-right',
    toggleOff: 'bx-toggle-left'
}

function loadingTemplate(message) {
    return '<i class="bx bx-loader-alt bx-spin bx-flip-vertical" ></i>'
}

function actionsFormatter(value, row, index) {
    return [
        '<a href="javascript:void(0);" class="edit-item" data-id=' + row.id + ' title=' + label_update + ' class="card-link"><i class="bx bx-edit mx-1"></i></a>' +
        '<button title=' + label_delete + ' type="button" class="btn delete" data-id=' + row.id + ' data-type="items">' +
        '<i class="bx bx-trash text-danger mx-1"></i>' +
        '</button>'
    ]
}

$(document).on('click', '.edit-item', function () {
    var id = $(this).data('id');
    $('#edit_item_modal').modal('show');
    var routePrefix = window.location.pathname.split('/')[1];
    $.ajax({
        url: '/' + routePrefix + '/items/get/' + id,
        type: 'get',
        headers: {
            'X-CSRF-TOKEN': $('input[name="_token"]').attr('value') // Replace with your method of getting the CSRF token
        },
        dataType: 'json',
        success: function (response) {
            var price = parseFloat(response.item.price);
            console.log('test');
            $('#item_id').val(response.item.id);
            $('#item_title').val(response.item.title);
            $('#item_price').val(price.toFixed(decimal_points));
            $('#item_unit_id').val(response.item.unit_id);
            $('#item_description').val(response.item.description);
        },


    });
});
