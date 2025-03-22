jQuery(document).ready(function($) {
    // Initialize Select2
    function initSelect2($element) {
        $element.select2({
            ajax: {
                url: siwo_ajax.ajax_url,
                dataType: 'json',
                delay: 250,
                data: function(params) {
                    return {
                        term: params.term,
                        action: 'siwo_search_products'
                    };
                },
                processResults: function(data) {
                    return {
                        results: data
                    };
                },
                cache: true
            },
            placeholder: 'Search product...',
            minimumInputLength: 2,
        }).on('select2:select', function(e) {
            var productId = e.params.data.id;
            $.ajax({
                url: siwo_ajax.ajax_url,
                method: 'POST',
                data: {
                    action: 'siwo_get_product_price',
                    product_id: productId
                },
                success: function(response) {
                    if (response.success) {
                        $(e.target).closest('tr').find('td:eq(2)').html(response.data.price);
                    }
                }
            });
        });
    }

    $('.siwo-product-search').each(function() {
        initSelect2($(this));
    });

    // Add new row
    $('#siwo-add-row').on('click', function() {
        var row = '<tr>' +
            '<td><select class="siwo-product-search form-select" name="products[]"><option value="">Search product...</option></select></td>' +
            '<td><input type="number" class="form-control" name="quantity[]" min="1" value="1" /></td>' +
            '<td>-</td>' +
            '<td><button type="button" class="btn btn-danger siwo-remove-row">Remove</button></td>' +
            '</tr>';
        $('#siwo-order-items').append(row);
        initSelect2($('#siwo-order-items tr:last .siwo-product-search'));
    });

    // Remove row
    $(document).on('click', '.siwo-remove-row', function() {
        $(this).closest('tr').remove();
    });

    // Print order
    $('.siwo-print-order').on('click', function(e) {
        e.preventDefault();
        var orderId = $(this).data('order-id');
        $.ajax({
            url: siwo_ajax.ajax_url,
            method: 'POST',
            data: {
                action: 'siwo_get_order_data',
                order_id: orderId
            },
            success: function(response) {
                if (response.success) {
                    var printWindow = window.open('', '_blank');
                    printWindow.document.write('<html><head><title>Order #' + orderId + '</title>');
                    printWindow.document.write('<style>table { width: 100%; border-collapse: collapse; } th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }</style>');
                    printWindow.document.write('</head><body>');
                    printWindow.document.write('<h1>Order #' + orderId + '</h1>');
                    printWindow.document.write('<p><strong>Customer:</strong> ' + response.data.customer + '</p>');
                    printWindow.document.write('<p><strong>Status:</strong> ' + response.data.status + '</p>');
                    printWindow.document.write('<table><thead><tr><th>Product</th><th>Quantity</th></tr></thead><tbody>');
                    $.each(response.data.products, function(productId, quantity) {
                        printWindow.document.write('<tr><td>' + productId + '</td><td>' + quantity + '</td></tr>');
                    });
                    printWindow.document.write('</tbody></table>');
                    printWindow.document.write('</body></html>');
                    printWindow.document.close();
                    printWindow.print();
                }
            }
        });
    });
});