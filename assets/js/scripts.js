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
                        $(e.target).closest('tr').find('td:eq(2)').text(response.data.price);
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
                    printWindow.document.write('<html><head><title>Invoice #' + response.data.invoice_number + '</title>');
                    printWindow.document.write('<style>');
                    printWindow.document.write('body { font-family: Arial, sans-serif; margin: 30px; color: #333; }');
                    printWindow.document.write('.invoice-container { max-width: 800px; margin: auto; border: 1px solid #ddd; padding: 20px; border-radius: 5px; }');
                    printWindow.document.write('.invoice-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #007bff; padding-bottom: 15px; margin-bottom: 20px; }');
                    printWindow.document.write('.invoice-logo { max-width: 150px; }');
                    printWindow.document.write('.invoice-details { text-align: right; }');
                    printWindow.document.write('.invoice-details h1 { margin: 0; color: #007bff; }');
                    printWindow.document.write('table { width: 100%; border-collapse: collapse; margin: 20px 0; }');
                    printWindow.document.write('th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }');
                    printWindow.document.write('th { background-color: #007bff; color: white; }');
                    printWindow.document.write('.total { font-weight: bold; font-size: 1.2em; margin-top: 20px; text-align: right; }');
                    printWindow.document.write('.notes { margin-top: 20px; padding: 10px; background: #f8f9fa; border-radius: 5px; }');
                    printWindow.document.write('</style>');
                    printWindow.document.write('</head><body>');

                    printWindow.document.write('<div class="invoice-container">');
                    // Header
                    printWindow.document.write('<div class="invoice-header">');
                    if (response.data.logo_url) {
                        printWindow.document.write('<img src="' + response.data.logo_url + '" class="invoice-logo" alt="Logo">');
                    } else {
                        printWindow.document.write('<h2>SiteIran</h2>');
                    }
                    printWindow.document.write('<div class="invoice-details">');
                    printWindow.document.write('<h1>' + response.data.invoice_number + '</h1>');
                    printWindow.document.write('<p><strong>Date:</strong> ' + response.data.date + '</p>');
                    printWindow.document.write('<p><strong>Customer:</strong> ' + response.data.customer + '</p>');
                    printWindow.document.write('<p><strong>Status:</strong> ' + response.data.status + '</p>');
                    printWindow.document.write('</div></div>');

                    // Products table
                    printWindow.document.write('<table><thead><tr><th>Product</th><th>Quantity</th><th>Price</th><th>Total</th></tr></thead><tbody>');
                    var grandTotal = 0;
                    $.each(response.data.products, function(productName, details) {
                        var total = details.quantity * details.price;
                        grandTotal += total;
                        printWindow.document.write('<tr><td>' + productName + '</td><td>' + details.quantity + '</td><td>' + details.price_formatted + '</td><td>' + details.total_formatted + '</td></tr>');
                    });
                    printWindow.document.write('</tbody></table>');

                    // Notes
                    var notes = response.data.notes || '';
                    if (notes.trim() !== '') {
                        printWindow.document.write('<div class="notes"><strong>Notes:</strong> ' + notes + '</div>');
                    }

                    // Total
                    var discount = response.data.discount || 0;
                    var finalTotal = grandTotal * (1 - discount / 100);
                    printWindow.document.write('<div class="total">');
                    printWindow.document.write('<p>Subtotal: ' + grandTotal.toFixed(2) + ' ' + response.data.currency + '</p>');
                    if (discount > 0) {
                        printWindow.document.write('<p>Discount (' + discount + '%): -' + (grandTotal * discount / 100).toFixed(2) + ' ' + response.data.currency + '</p>');
                    }
                    printWindow.document.write('<p>Total: ' + finalTotal.toFixed(2) + ' ' + response.data.currency + '</p>');
                    printWindow.document.write('</div>');

                    printWindow.document.write('</div></body></html>');
                    printWindow.document.close();
                    printWindow.print();
                }
            }
        });
    });
});