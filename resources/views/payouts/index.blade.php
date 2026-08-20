<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Payout Management</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        body { font-family: Arial, sans-serif; margin: 2rem auto; max-width: 1100px; color: #263238; }
        form, table { width: 100%; margin-bottom: 1.5rem; }
        input, select, button { padding: .6rem; margin: .25rem; }
        table { border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: .7rem; text-align: left; }
        th { background: #f3f5f7; }
        .message { padding: .7rem; margin-bottom: 1rem; display: none; }
        .success { background: #e1f5e4; color: #155724; }
        .error { background: #fde2e2; color: #842029; }
        .pagination button { cursor: pointer; }
    </style>
</head>
<body>
    <h1>Payout Management</h1>
    <div id="message" class="message"></div>

    <form id="filter-form">
        <label>Transaction ID <input type="text" id="filter-transaction-id"></label>
        <label>Status
            <select id="filter-status">
                <option value="">All</option><option>PENDING</option><option>SUCCESS</option><option>FAILED</option>
            </select>
        </label>
        <button type="submit">Search</button>
    </form>

    <table>
        <thead><tr><th>Transaction ID</th><th>Beneficiary</th><th>Amount</th><th>Status</th><th>Action</th></tr></thead>
        <tbody id="payout-rows"></tbody>
    </table>
    <div id="pagination" class="pagination"></div>

    <h2>Create Payout</h2>
    <form id="create-form">
        <input name="transaction_id" placeholder="Transaction ID" required maxlength="100">
        <input name="beneficiary_name" placeholder="Beneficiary Name" required maxlength="150">
        <input name="amount" type="number" min="0.01" step="0.01" placeholder="Amount" required>
        <button type="submit">Create Payout</button>
    </form>

    <script>
        var csrfToken = $('meta[name="csrf-token"]').attr('content');
        $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' } });

        function showMessage(text, type) {
            $('#message').removeClass('success error').addClass(type).text(text).show();
        }

        function apiError(xhr) {
            var response = xhr.responseJSON || {};
            var errors = response.errors ? ' ' + Object.keys(response.errors).map(function (key) { return response.errors[key].join(' '); }).join(' ') : '';
            showMessage((response.message || 'Request failed.') + errors, 'error');
        }

        function loadPayouts(page) {
            $.getJSON('/api/payouts', {
                page: page || 1,
                transaction_id: $('#filter-transaction-id').val(),
                status: $('#filter-status').val()
            }).done(function (response) {
                var rows = response.data || [];
                var pagination = response.meta || { current_page: 1, last_page: 1 };
                $('#payout-rows').html(rows.length ? rows.map(function (payout) {
                    var action = payout.status === 'PENDING' ? '<button class="status-button" data-id="' + payout.id + '" data-status="SUCCESS">Mark success</button><button class="status-button" data-id="' + payout.id + '" data-status="FAILED">Mark failed</button>' : '';
                    return '<tr><td>' + payout.transaction_id + '</td><td>' + payout.beneficiary_name + '</td><td>' + payout.amount + '</td><td>' + payout.status + '</td><td>' + action + '</td></tr>';
                }).join('') : '<tr><td colspan="5">No payouts found.</td></tr>');
                $('#pagination').html('<button data-page="' + (pagination.current_page - 1) + '" ' + (pagination.current_page <= 1 ? 'disabled' : '') + '>Previous</button> Page ' + pagination.current_page + ' of ' + pagination.last_page + ' <button data-page="' + (pagination.current_page + 1) + '" ' + (pagination.current_page >= pagination.last_page ? 'disabled' : '') + '>Next</button>');
            }).fail(apiError);
        }

        $('#filter-form').on('submit', function (event) { event.preventDefault(); loadPayouts(1); });
        $('#create-form').on('submit', function (event) {
            event.preventDefault();
            $.post('/api/payouts', $(this).serialize()).done(function (response) {
                showMessage(response.message, 'success'); $('#create-form')[0].reset(); loadPayouts(1);
            }).fail(apiError);
        });
        $(document).on('click', '.pagination button:not(:disabled)', function () { loadPayouts($(this).data('page')); });
        $(document).on('click', '.status-button', function () {
            var button = $(this);
            $.ajax({ url: '/api/payouts/' + button.data('id') + '/status', method: 'PATCH', data: { status: button.data('status') } }).done(function (response) { showMessage(response.message, 'success'); loadPayouts(1); }).fail(apiError);
        });
        loadPayouts(1);
    </script>
</body>
</html>
