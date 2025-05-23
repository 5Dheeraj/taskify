document.addEventListener('DOMContentLoaded', function () {
    // Initialize the date range picker
    $('#filter_date_range').daterangepicker({
        opens: 'left',
        startDate: moment().startOf('month'),
        endDate: moment().endOf('month'),
        locale: {
            format: 'YYYY-MM-DD',
            cancelLabel: 'Clear'
        }
    });

    // Function to get current filters
    function getFilters() {
        return {
            start_date: $('#filter_date_range').data('daterangepicker').startDate.format('YYYY-MM-DD'),
            end_date: $('#filter_date_range').data('daterangepicker').endDate.format('YYYY-MM-DD'),
            // Add other filters if needed
        };
    }

    // Function to fetch and update the report data
    function updateReport() {
        var url = window.location.href;

        // Create a URL object to parse the URL
        var parsedUrl = new URL(url);

        // Get the hostname from the URL, which is the first main segment
        var hostname = parsedUrl.hostname;

        $.ajax({
            url: '/master-panel/reports/income-vs-expense-report-data',
            method: 'GET',
            data: getFilters(),
            success: function (data) {
                // Update total income and expenses
                $('#total_income').text(data.total_income || '0');
                $('#total_expenses').text(data.total_expenses || '0');
                $('#profit_or_loss').text(data.profit_or_loss || '0');

                // Update invoice details
                var invoicesHtml = '';
                if (data.invoices.length > 0) {
                    data.invoices.forEach(function (invoice) {
                        invoicesHtml += `
                        <tr>
                            <td><a href="${invoice.view_route}">${invoice.id}</a></td>
                            <td>${invoice.from_date} - ${invoice.to_date}</td>
                            <td>${invoice.amount}</td>
                        </tr>
                    `;
                    });
                } else {
                    invoicesHtml = `
                    <tr>
                        <td colspan="3" class="text-center">No data available</td>
                    </tr>
                `;
                }
                $('#invoices_table tbody').html(invoicesHtml);

                // Update expense details
                var expensesHtml = '';
                if (data.expenses.length > 0) {
                    data.expenses.forEach(function (expense) {
                        expensesHtml += `
                        <tr>
                            <td>${expense.id}</td>
                            <td>${expense.title}</td>
                            <td>${expense.amount}</td>
                            <td>${expense.expense_date}</td>
                        </tr>
                    `;
                    });
                } else {
                    expensesHtml = `
                    <tr>
                        <td colspan="4" class="text-center">No data available</td>
                    </tr>
                `;
                }
                $('#expenses_table tbody').html(expensesHtml);
            },
            error: function () {
                // Handle errors
                alert('Error fetching report data.');
            }
        });
    }


    // Handle filter changes
    $('#filter_date_range').on('change', function () {
        updateReport();
    });

    // Initialize report with default filters
    updateReport();
});

$('#export_button').on('click', function () {
    var startDate = $('#filter_date_range').data('daterangepicker').startDate.format('YYYY-MM-DD');
    var endDate = $('#filter_date_range').data('daterangepicker').endDate.format('YYYY-MM-DD');


    var exportUrl = `${export_income_vs_expense_url}?start_date=${startDate}&end_date=${endDate}`;
    window.open(exportUrl, '_blank');

});
