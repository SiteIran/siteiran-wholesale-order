jQuery(document).ready(function($) {
    // بررسی وجود siwo_data
    if (typeof siwo_data === 'undefined') {
        console.log('siwo_data is not defined. Skipping initialization.');
        return;
    }

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
            placeholder: siwo_data.search_product_placeholder,
            minimumInputLength: 2,
            width: '100%'
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
                        var $row = $(e.target).closest('tr');
                        $row.find('.product-price').html(response.data.price);
                        $row.find('select').data('price', response.data.raw_price);
                        updateSubtotal($row);
                        updateOrderTotal();
                    }
                }
            });
        });
    }

    // مقداردهی اولیه Select2 برای همه ردیف‌ها
    $('.siwo-product-search').each(function() {
        initSelect2($(this));
    });

    // اضافه کردن ردیف جدید
    $('#siwo-add-row').on('click', function() {
        var row = '<tr class="order-item-row">' +
            '<td><select class="siwo-product-search form-select" name="products[]"><option value="">' + siwo_data.search_product_placeholder + '</option></select></td>' +
            '<td><input type="number" class="form-control quantity-input" name="quantity[]" min="1" value="1" /></td>' +
            '<td class="product-price">-</td>' +
            '<td class="subtotal">' + siwo_data.currency_symbol + '0.00</td>' +
            '<td><button type="button" class="btn btn-danger btn-sm siwo-remove-row"><i class="bi bi-trash"></i> ' + siwo_data.remove_label + '</button></td>' +
            '</tr>';
        $('#siwo-order-items').append(row);
        initSelect2($('#siwo-order-items tr:last .siwo-product-search'));
        updateOrderTotal();
    });

    // حذف ردیف
    $(document).on('click', '.siwo-remove-row', function() {
        if ($('#siwo-order-items tr').length > 1) {
            $(this).closest('tr').remove();
            updateOrderTotal();
        }
    });

    // آپدیت زیرجمع و جمع کل هنگام تغییر تعداد یا انتخاب محصول
    $(document).on('input change', '.quantity-input', function() {
        var $row = $(this).closest('tr');
        updateSubtotal($row);
        updateOrderTotal();
    });

    $(document).on('change', '.siwo-product-search', function() {
        var $row = $(this).closest('tr');
        updateSubtotal($row);
        updateOrderTotal();
    });

    // محاسبه زیرجمع برای یک ردیف
    function updateSubtotal($row) {
        var quantity = parseInt($row.find('.quantity-input').val()) || 0;
        var price = parseFloat($row.find('.siwo-product-search').data('price')) || 0;
        var subtotal = price * quantity;
        $row.find('.subtotal').text(siwo_data.currency_symbol + subtotal.toFixed(2));
    }

    // محاسبه جمع کل
    function updateOrderTotal() {
        var total = 0;
        $('#siwo-order-items tr').each(function() {
            var $row = $(this);
            var quantity = parseInt($row.find('.quantity-input').val()) || 0;
            var price = parseFloat($row.find('.siwo-product-search').data('price')) || 0;
            total += price * quantity;
        });
        $('.order-total').text(siwo_data.currency_symbol + total.toFixed(2));
    }

    // محاسبه اولیه جمع کل
    updateOrderTotal();

    // پرینت سفارش
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

                    printWindow.document.write('<table><thead><tr><th>Product</th><th>Quantity</th><th>Price</th><th>Total</th></tr></thead><tbody>');
                    var grandTotal = 0;
                    $.each(response.data.products, function(productName, details) {
                        var total = details.quantity * details.price;
                        grandTotal += total;
                        printWindow.document.write('<tr><td>' + productName + '</td><td>' + details.quantity + '</td><td>' + details.price_formatted + '</td><td>' + details.total_formatted + '</td></tr>');
                    });
                    printWindow.document.write('</tbody></table>');

                    var notes = response.data.notes || '';
                    if (notes.trim() !== '') {
                        printWindow.document.write('<div class="notes"><strong>Notes:</strong> ' + notes + '</div>');
                    }

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