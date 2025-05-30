
'use strict';
function queryParams(p) {
    return {
        page: p.offset / p.limit + 1,
        limit: p.limit,
        sort: p.sort,
        order: p.order,
        offset: p.offset,
        search: p.search
    };
}


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
        '<a href="javascript:void(0);" class="edit-deduction" data-id=' + row.id + ' title=' + label_update + ' class="card-link"><i class="bx bx-edit mx-1"></i></a>' +
        '<button title=' + label_delete + ' type="button" class="btn delete" data-id=' + row.id + ' data-type="deductions">' +
        '<i class="bx bx-trash text-danger mx-1"></i>' +
        '</button>'
    ]
}

$(document).on('click', '.edit-deduction', function () {
    var id = $(this).data('id');
    var routePrefix = $('#table').data('routePrefix');
    $('#edit_deduction_modal').modal('show');
    $.ajax({
        url: routePrefix+'/deductions/get/' + id,
        type: 'get',
        headers: {
            'X-CSRF-TOKEN': $('input[name="_token"]').attr('value') // Replace with your method of getting the CSRF token
        },
        dataType: 'json',
        success: function (response) {
            $('#deduction_id').val(response.deduction.id);
            $('#deduction_title').val(response.deduction.title);
            $('#update_deduction_type').val(response.deduction.type);
            if (response.deduction.type == 'amount') {
                $('#update_amount_div').removeClass('d-none');
                $('#update_percentage_div').addClass('d-none');
                $('#deduction_amount').val(response.deduction.amount);
            } else {
                $('#update_amount_div').addClass('d-none');
                $('#update_percentage_div').removeClass('d-none');
                $('#deduction_percentage').val(response.deduction.percentage);
            }
        },

    });
});