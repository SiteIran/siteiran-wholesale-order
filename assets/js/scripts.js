jQuery(document).ready(function($) {
    // فعال‌سازی Select2 برای انتخاب مشتری
    $('.siwo-select2').select2({
        placeholder: siwo_translations.select_customer,
        allowClear: true
    });

    // فعال‌سازی Select2 برای جستجوی محصولات
    $('.siwo-select2-product').select2({
        ajax: {
            url: siwo_ajax.ajax_url,
            dataType: 'json',
            delay: 250,
            data: function(params) {
                return {
                    action: 'siwo_search_products',
                    term: params.term || '',
                    selected_id: $(this).val() || ''
                };
            },
            processResults: function(data) {
                return {
                    results: data
                };
            },
            cache: true
        },
        placeholder: siwo_translations.search_product,
        minimumInputLength: 1,
        allowClear: true
    });

    // افزودن محصول جدید در فرم سفارش
    $('#siwo-add-product').on('click', function(e) {
        e.preventDefault();
        var productRow = `
            <div class="siwo-product-row mb-3 d-flex align-items-center">
                <select name="siwo_products[]" class="form-select siwo-select2-product w-50 me-2">
                    <option value="">${siwo_translations.search_product}</option>
                </select>
                <input type="number" name="siwo_products_quantity[]" class="form-control w-25 me-2" value="1" min="1" />
                <button type="button" class="btn btn-danger btn-sm siwo-remove-product"><i class="bi bi-trash"></i></button>
            </div>
        `;
        $('#siwo-products').append(productRow);

        // فعال‌سازی Select2 برای ردیف جدید
        $('#siwo-products .siwo-select2-product').last().select2({
            ajax: {
                url: siwo_ajax.ajax_url,
                dataType: 'json',
                delay: 250,
                data: function(params) {
                    return {
                        action: 'siwo_search_products',
                        term: params.term || ''
                    };
                },
                processResults: function(data) {
                    return {
                        results: data
                    };
                },
                cache: true
            },
            placeholder: siwo_translations.search_product,
            minimumInputLength: 1,
            allowClear: true
        });
    });

    // حذف ردیف محصول
    $(document).on('click', '.siwo-remove-product', function(e) {
        e.preventDefault();
        $(this).closest('.siwo-product-row').remove();
    });

    // آپلود لوگو
    $('#siwo_upload_logo').on('click', function(e) {
        e.preventDefault();
        var frame;
        if (frame) {
            frame.open();
            return;
        }

        frame = wp.media({
            title: siwo_translations.upload_logo,
            button: {
                text: siwo_translations.select_logo
            },
            multiple: false
        });

        frame.on('select', function() {
            var attachment = frame.state().get('selection').first().toJSON();
            $('#siwo_logo').val(attachment.url);
            var preview = '<div class="mt-2"><img src="' + attachment.url + '" alt="' + siwo_translations.logo_preview + '" style="max-width: 200px; max-height: 200px;" /></div>';
            $('#siwo_logo').siblings('.mt-2').remove();
            $('#siwo_logo').after(preview);
            if (!$('#siwo_remove_logo').length) {
                $('#siwo_upload_logo').after('<button type="button" id="siwo_remove_logo" class="btn btn-danger ms-2">' + siwo_translations.remove_logo + '</button>');
            }
        });

        frame.open();
    });

    // حذف لوگو
    $(document).on('click', '#siwo_remove_logo', function(e) {
        e.preventDefault();
        $('#siwo_logo').val('');
        $('#siwo_logo').siblings('.mt-2').remove();
        $(this).remove();
    });

    // افزودن پارامتر SMS
    $('#siwo-add-param').on('click', function(e) {
        e.preventDefault();
        var paramRow = `
            <div class="siwo-sms-param-row mb-2 d-flex align-items-center">
                <input type="text" name="siwo_sms_param_name[]" class="form-control w-25 me-2" placeholder="${siwo_translations.param_name}" />
                <input type="text" name="siwo_sms_param_value[]" class="form-control w-25 me-2" placeholder="${siwo_translations.param_value}" />
                <button type="button" class="btn btn-danger btn-sm siwo-remove-param"><i class="bi bi-trash"></i></button>
            </div>
        `;
        $('#siwo-sms-params').append(paramRow);
    });

    // حذف پارامتر SMS
    $(document).on('click', '.siwo-remove-param', function(e) {
        e.preventDefault();
        $(this).closest('.siwo-sms-param-row').remove();
    });

    // نمایش جزئیات سفارش در مودال
    $('.siwo-view-order').on('click', function(e) {
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
                    var data = response.data;
                    var html = '<p><strong>' + siwo_translations.order_id + '</strong> ' + data.invoice_number + '</p>';
                    html += '<p><strong>' + siwo_translations.customer + '</strong> ' + data.customer + '</p>';
                    html += '<p><strong>' + siwo_translations.status + '</strong> ' + data.status + '</p>';
                    html += '<p><strong>' + siwo_translations.date + '</strong> ' + data.date + '</p>';
                    html += '<h5>' + siwo_translations.products + '</h5>';
                    html += '<table class="table table-bordered"><thead><tr><th>' + siwo_translations.product + '</th><th>' + siwo_translations.quantity + '</th><th>' + siwo_translations.price + '</th><th>' + siwo_translations.total + '</th></tr></thead><tbody>';
                    var grandTotal = 0;
                    $.each(data.products, function(productName, details) {
                        var total = details.quantity * details.price;
                        grandTotal += total;
                        html += '<tr><td>' + productName + '</td><td>' + details.quantity + '</td><td>' + details.price_formatted + '</td><td>' + details.total_formatted + '</td></tr>';
                    });
                    html += '</tbody></table>';
                    if (data.notes) {
                        html += '<p><strong>' + siwo_translations.notes + '</strong> ' + data.notes + '</p>';
                    }
                    var discount = parseFloat(data.discount) || 0;
                    var finalTotal = grandTotal - discount;
                    html += '<p><strong>' + siwo_translations.subtotal + '</strong> ' + grandTotal.toFixed(2) + ' ' + data.currency + '</p>';
                    if (discount > 0) {
                        html += '<p><strong>' + siwo_translations.discount + '</strong> -' + discount.toFixed(2) + ' ' + data.currency + '</p>';
                    }
                    html += '<p><strong>' + siwo_translations.total + '</strong> ' + finalTotal.toFixed(2) + ' ' + data.currency + '</p>';

                    $('#siwo-order-details').html(html);
                    $('#siwoOrderModal').modal('show');
                } else {
                    alert(siwo_translations.failed_load_order);
                }
            },
            error: function() {
                alert(siwo_translations.error_fetching_order);
            }
        });
    });

    // پرینت سفارش
    $('.siwo-print-order').on('click', function(e) {
        e.preventDefault();
        var orderId = $(this).data('order-id');
        var printWindow = window.open('', '_blank');
        $.ajax({
            url: siwo_ajax.ajax_url,
            method: 'POST',
            data: {
                action: 'siwo_get_order_data',
                order_id: orderId
            },
            success: function(response) {
                if (response.success) {
                    var data = response.data;
                    var printContent = '<html><head><title>' + siwo_translations.order_invoice + ' #' + data.invoice_number + '</title>';
                    printContent += '<style>body { font-family: Arial, sans-serif; margin: 20px; direction: rtl; } table { width: 100%; border-collapse: collapse; } th, td { border: 1px solid #ddd; padding: 8px; text-align: right; } th { background-color: #f2f2f2; } .logo { max-width: 150px; max-height: 150px; margin-bottom: 20px; float: left; }</style>';
                    printContent += '</head><body>';
                    // اضافه کردن لوگو
                    if (siwo_settings.logo) {
                        printContent += '<img src="' + siwo_settings.logo + '" class="logo" alt="Logo" />';
                    }
                    printContent += '<h2>' + siwo_translations.order_invoice + ' #' + data.invoice_number + '</h2>';
                    printContent += '<p><strong>' + siwo_translations.customer + '</strong> ' + data.customer + '</p>';
                    printContent += '<p><strong>' + siwo_translations.status + '</strong> ' + data.status + '</p>';
                    printContent += '<p><strong>' + siwo_translations.date + '</strong> ' + data.date + '</p>';
                    printContent += '<h4>' + siwo_translations.products + '</h4>';
                    printContent += '<table><thead><tr><th>' + siwo_translations.product + '</th><th>' + siwo_translations.quantity + '</th><th>' + siwo_translations.price + '</th><th>' + siwo_translations.total + '</th></tr></thead><tbody>';
                    var grandTotal = 0;
                    $.each(data.products, function(productName, details) {
                        var total = details.quantity * details.price;
                        grandTotal += total;
                        printContent += '<tr><td>' + productName + '</td><td>' + details.quantity + '</td><td>' + details.price_formatted + '</td><td>' + details.total_formatted + '</td></tr>';
                    });
                    printContent += '</tbody></table>';
                    if (data.notes) {
                        printContent += '<p><strong>' + siwo_translations.notes + '</strong> ' + data.notes + '</p>';
                    }
                    var discount = parseFloat(data.discount) || 0;
                    var finalTotal = grandTotal - discount;
                    printContent += '<p><strong>' + siwo_translations.subtotal + '</strong> ' + grandTotal.toFixed(2) + ' ' + data.currency + '</p>';
                    if (discount > 0) {
                        printContent += '<p><strong>' + siwo_translations.discount + '</strong> -' + discount.toFixed(2) + ' ' + data.currency + '</p>';
                    }
                    printContent += '<p><strong>' + siwo_translations.total + '</strong> ' + finalTotal.toFixed(2) + ' ' + data.currency + '</p>';
                    printContent += '</body></html>';

                    printWindow.document.write(printContent);
                    printWindow.document.close();
                    printWindow.print();
                } else {
                    alert(siwo_translations.failed_load_order_print);
                    printWindow.close();
                }
            },
            error: function() {
                alert(siwo_translations.error_fetching_order_print);
                printWindow.close();
            }
        });
    });
});