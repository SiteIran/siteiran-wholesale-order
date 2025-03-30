<?php
if (!defined('ABSPATH')) {
    exit; // جلوگیری از دسترسی مستقیم به فایل
}

/**
 * کلاس SIWO_Orders برای مدیریت لیست سفارش‌ها
 */
class SIWO_Orders {
    /**
     * متد برای رندر صفحه لیست سفارش‌ها
     */
    public function render() {
        global $wpdb;

        // بررسی پیام‌ها (موفقیت یا خطا)
        $message = '';
        $message_type = '';
        if (isset($_GET['converted']) && $_GET['converted'] == 1) {
            $message = __('Order converted to WooCommerce successfully!', 'siteiran-wholesale');
            $message_type = 'success';
        } elseif (isset($_GET['message']) && $_GET['message'] == 'cancelled') {
            $message = __('Order cancelled successfully!', 'siteiran-wholesale');
            $message_type = 'success';
        } elseif (isset($_GET['message']) && $_GET['message'] == 'created') {
            $message = __('Order created successfully!', 'siteiran-wholesale');
            $message_type = 'success';
        } elseif (isset($_GET['message']) && $_GET['message'] == 'updated') {
            $message = __('Order updated successfully!', 'siteiran-wholesale');
            $message_type = 'success';
        }

        // تنظیمات پیش‌فرض برای WP_Query
        $args = [
            'post_type' => 'siwo_order',
            'posts_per_page' => 20,
            'paged' => isset($_GET['paged']) ? intval($_GET['paged']) : 1,
            'meta_query' => [],
        ];

        // گرفتن مقادیر فیلترها از URL
        $status_filter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
        $date_from = isset($_GET['date_from']) ? sanitize_text_field($_GET['date_from']) : '';
        $date_to = isset($_GET['date_to']) ? sanitize_text_field($_GET['date_to']) : '';
        $customer_filter = isset($_GET['customer']) ? sanitize_text_field($_GET['customer']) : '';
        $product_filter = isset($_GET['product']) ? sanitize_text_field($_GET['product']) : '';
        $invoice_filter = isset($_GET['invoice']) ? sanitize_text_field($_GET['invoice']) : '';

        // اضافه کردن فیلتر وضعیت
        if ($status_filter) {
            $args['meta_query'][] = [
                'key' => 'siwo_status',
                'value' => $status_filter,
                'compare' => '=',
            ];
        }

        // اضافه کردن فیلتر تاریخ
        if ($date_from || $date_to) {
            $args['date_query'] = [];
            if ($date_from) {
                $args['date_query']['after'] = $date_from;
            }
            if ($date_to) {
                $args['date_query']['before'] = $date_to;
            }
            $args['date_query']['inclusive'] = true;
        }

        // اضافه کردن فیلتر مشتری
        if ($customer_filter) {
            $args['meta_query'][] = [
                'key' => 'siwo_customer',
                'value' => $customer_filter,
                'compare' => '=',
            ];
        }

        // اضافه کردن فیلتر شماره فاکتور
        if ($invoice_filter) {
            $args['p'] = $invoice_filter; // جستجو بر اساس ID سفارش
        }

        // اضافه کردن فیلتر محصول
        $order_ids_with_product = [];
        if ($product_filter) {
            $items = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT order_id FROM {$wpdb->prefix}siwo_order_items WHERE product_id = %d",
                    $product_filter
                ),
                ARRAY_A
            );

            if (!empty($items)) {
                $order_ids_with_product = array_column($items, 'order_id');
                $args['post__in'] = $order_ids_with_product;
            } else {
                // اگر هیچ سفارشی با این محصول پیدا نشد، post__in خالی می‌گذاریم تا هیچ نتیجه‌ای برگردانده نشود
                $args['post__in'] = [0];
            }
        }

        // اجرای کوئری با فیلترها
        $orders_query = new WP_Query($args);

        // گرفتن لیست همه کاربران
        $customers = get_users([
            'fields' => ['ID', 'display_name'],
        ]);

        // آماده‌سازی داده برای مقدار اولیه محصول انتخاب‌شده
        $selected_product = [];
        if ($product_filter) {
            $product = wc_get_product($product_filter);
            if ($product) {
                $selected_product = [
                    'id' => $product_filter,
                    'text' => $product->get_name(),
                ];
            }
        }

        ?>
        <div class="wrap siwo-wrap">
            <!-- عنوان صفحه -->
            <h1 class="mb-4"><?php _e('Wholesale Orders', 'siteiran-wholesale'); ?></h1>

            <!-- نمایش پیام موفقیت یا خطا -->
            <?php if ($message) : ?>
                <div class="notice notice-<?php echo esc_attr($message_type); ?> is-dismissible">
                    <p><?php echo esc_html($message); ?></p>
                </div>
            <?php endif; ?>

            <!-- فرم فیلترها -->
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h5 class="card-title"><?php _e('Filter Orders', 'siteiran-wholesale'); ?></h5>
                    <form method="get" class="row g-3">
                        <input type="hidden" name="page" value="siwo-orders" />
                        <div class="col-md-3 col-sm-6">
                            <label for="status" class="form-label"><?php _e('Status', 'siteiran-wholesale'); ?></label>
                            <select name="status" id="status" class="form-select">
                                <option value=""><?php _e('All Statuses', 'siteiran-wholesale'); ?></option>
                                <option value="pending" <?php selected($status_filter, 'pending'); ?>><?php _e('Pending', 'siteiran-wholesale'); ?></option>
                                <option value="processing" <?php selected($status_filter, 'processing'); ?>><?php _e('Processing', 'siteiran-wholesale'); ?></option>
                                <option value="completed" <?php selected($status_filter, 'completed'); ?>><?php _e('Completed', 'siteiran-wholesale'); ?></option>
                                <option value="cancelled" <?php selected($status_filter, 'cancelled'); ?>><?php _e('Cancelled', 'siteiran-wholesale'); ?></option>
                            </select>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <label for="date_from" class="form-label"><?php _e('Date From', 'siteiran-wholesale'); ?></label>
                            <input type="date" name="date_from" id="date_from" class="form-control" value="<?php echo esc_attr($date_from); ?>" />
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <label for="date_to" class="form-label"><?php _e('Date To', 'siteiran-wholesale'); ?></label>
                            <input type="date" name="date_to" id="date_to" class="form-control" value="<?php echo esc_attr($date_to); ?>" />
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <label for="customer" class="form-label"><?php _e('Customer', 'siteiran-wholesale'); ?></label>
                            <select name="customer" id="customer" class="form-select siwo-select2">
                                <option value=""><?php _e('All Customers', 'siteiran-wholesale'); ?></option>
                                <?php foreach ($customers as $customer) : ?>
                                    <option value="<?php echo esc_attr($customer->ID); ?>" <?php selected($customer_filter, $customer->ID); ?>>
                                        <?php echo esc_html($customer->display_name); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <label for="product" class="form-label"><?php _e('Product', 'siteiran-wholesale'); ?></label>
                            <select name="product" id="product" class="form-select siwo-select2-product">
                                <option value=""><?php _e('All Products', 'siteiran-wholesale'); ?></option>
                            </select>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <label for="invoice" class="form-label"><?php _e('Invoice Number', 'siteiran-wholesale'); ?></label>
                            <input type="text" name="invoice" id="invoice" class="form-control" value="<?php echo esc_attr($invoice_filter); ?>" placeholder="<?php _e('Enter Invoice Number', 'siteiran-wholesale'); ?>" />
                        </div>
                        <div class="col-12 mt-3">
                            <button type="submit" class="btn btn-primary me-2"><?php _e('Filter', 'siteiran-wholesale'); ?></button>
                            <a href="<?php echo admin_url('admin.php?page=siwo-orders'); ?>" class="btn btn-outline-secondary"><?php _e('Reset', 'siteiran-wholesale'); ?></a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- جدول سفارش‌ها -->
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead class="table-dark">
                                <tr>
                                    <th><?php _e('Order ID', 'siteiran-wholesale'); ?></th>
                                    <th><?php _e('Customer', 'siteiran-wholesale'); ?></th>
                                    <th><?php _e('Total', 'siteiran-wholesale'); ?></th>
                                    <th><?php _e('Status', 'siteiran-wholesale'); ?></th>
                                    <th><?php _e('Date', 'siteiran-wholesale'); ?></th>
                                    <th><?php _e('Actions', 'siteiran-wholesale'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($orders_query->have_posts()) : ?>
                                    <?php while ($orders_query->have_posts()) : $orders_query->the_post(); ?>
                                        <?php
                                        $order_id = get_the_ID();
                                        $customer_id = get_post_meta($order_id, 'siwo_customer', true);
                                        $customer = get_userdata($customer_id);
                                        $status = get_post_meta($order_id, 'siwo_status', true);
                                        $discount = get_post_meta($order_id, 'siwo_discount', true) ?: 0;
                                        $is_converted = get_post_meta($order_id, 'siwo_converted_to_wc', true);

                                        // محاسبه جمع کل با احتساب تخفیف
                                        $items = $wpdb->get_results($wpdb->prepare(
                                            "SELECT * FROM {$wpdb->prefix}siwo_order_items WHERE order_id = %d",
                                            $order_id
                                        ), ARRAY_A);
                                        $total = 0;
                                        foreach ($items as $item) {
                                            $total += $item['price'] * $item['quantity'];
                                        }
                                        $total -= $discount;

                                        // ترجمه وضعیت‌ها
                                        $status_labels = [
                                            'pending' => __('Pending', 'siteiran-wholesale'),
                                            'processing' => __('Processing', 'siteiran-wholesale'),
                                            'completed' => __('Completed', 'siteiran-wholesale'),
                                            'cancelled' => __('Cancelled', 'siteiran-wholesale'),
                                        ];

                                        // رنگ‌بندی وضعیت‌ها
                                        $status_class = '';
                                        switch ($status) {
                                            case 'pending':
                                                $status_class = 'text-warning';
                                                break;
                                            case 'processing':
                                                $status_class = 'text-info';
                                                break;
                                            case 'completed':
                                                $status_class = 'text-success';
                                                break;
                                            case 'cancelled':
                                                $status_class = 'text-danger';
                                                break;
                                        }
                                        ?>
                                        <tr>
                                            <td>#<?php echo esc_html($order_id); ?></td>
                                            <td><?php echo esc_html($customer ? $customer->display_name : __('Unknown', 'siteiran-wholesale')); ?></td>
                                            <td><?php echo wc_price($total); ?></td>
                                            <td><span class="<?php echo esc_attr($status_class); ?>"><?php echo esc_html($status_labels[$status] ?? ucfirst($status)); ?></span></td>
                                            <td><?php echo get_the_date(); ?></td>
                                            <td>
                                                <!-- دکمه مشاهده سفارش -->
                                                <a href="#" class="btn btn-sm btn-info siwo-view-order" data-order-id="<?php echo esc_attr($order_id); ?>" title="<?php _e('View Order', 'siteiran-wholesale'); ?>">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                <!-- دکمه ویرایش سفارش -->
                                                <a href="<?php echo admin_url('admin.php?page=siwo-add-order&edit_order=' . $order_id); ?>" class="btn btn-sm btn-primary" title="<?php _e('Edit Order', 'siteiran-wholesale'); ?>">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <!-- دکمه پرینت سفارش -->
                                                <a href="#" class="btn btn-sm btn-secondary siwo-print-order" data-order-id="<?php echo esc_attr($order_id); ?>" title="<?php _e('Print Order', 'siteiran-wholesale'); ?>">
                                                    <i class="bi bi-printer"></i>
                                                </a>
                                                <?php if (!$is_converted) : ?>
                                                    <!-- دکمه تبدیل به ووکامرس -->
                                                    <a href="<?php echo admin_url('admin.php?page=siwo-orders&action=convert&order_id=' . $order_id); ?>" class="btn btn-sm btn-success" title="<?php _e('Convert to WooCommerce', 'siteiran-wholesale'); ?>">
                                                        <i class="bi bi-cart-check"></i>
                                                    </a>
                                                    <?php if ($status !== 'cancelled') : ?>
                                                        <!-- دکمه لغو سفارش -->
                                                        <a href="<?php echo admin_url('admin.php?page=siwo-orders&action=cancel&order_id=' . $order_id); ?>" class="btn btn-sm btn-danger" onclick="return confirm('<?php _e('Are you sure you want to cancel this order?', 'siteiran-wholesale'); ?>');" title="<?php _e('Cancel Order', 'siteiran-wholesale'); ?>">
                                                            <i class="bi bi-x-circle"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else : ?>
                                    <tr>
                                        <td colspan="6"><?php _e('No orders found.', 'siteiran-wholesale'); ?></td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- صفحه‌بندی -->
                    <div class="siwo-pagination">
                        <?php
                        $big = 999999999;
                        echo paginate_links([
                            'base' => str_replace($big, '%#%', esc_url(get_pagenum_link($big))),
                            'format' => '?paged=%#%',
                            'current' => max(1, $args['paged']),
                            'total' => $orders_query->max_num_pages,
                            'prev_text' => __('« Previous', 'siteiran-wholesale'),
                            'next_text' => __('Next »', 'siteiran-wholesale'),
                        ]);
                        ?>
                    </div>
                </div>
            </div>

            <!-- مودال برای نمایش جزئیات سفارش -->
            <div class="modal fade" id="siwoOrderModal" tabindex="-1" aria-labelledby="siwoOrderModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="siwoOrderModalLabel"><?php _e('Order Details', 'siteiran-wholesale'); ?></h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div id="siwo-order-details"></div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php _e('Close', 'siteiran-wholesale'); ?></button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- اسکریپت برای تنظیم مقدار اولیه Select2 -->
        <script>
            jQuery(document).ready(function($) {
                var selectedProduct = <?php echo json_encode($selected_product); ?>;
                if (selectedProduct && selectedProduct.id) {
                    $('#product').val(selectedProduct.id).trigger('change');
                }
            });
        </script>
        <?php
        wp_reset_postdata();
    }

    /**
     * متد برای مدیریت اکشن‌های سفارش (تبدیل به ووکامرس و لغو سفارش)
     */
    public function handle_actions() {
        if (isset($_GET['action']) && isset($_GET['order_id'])) {
            $order_id = intval($_GET['order_id']);
            $action = sanitize_text_field($_GET['action']);

            if ($action === 'convert') {
                $this->convert_to_woocommerce($order_id);
            } elseif ($action === 'cancel') {
                $this->cancel_order($order_id);
            }
        }
    }

    /**
     * متد برای تبدیل سفارش به ووکامرس
     */
    private function convert_to_woocommerce($order_id) {
        global $wpdb;

        $order = get_post($order_id);
        if (!$order || $order->post_type !== 'siwo_order') {
            return;
        }

        $customer_id = get_post_meta($order_id, 'siwo_customer', true);
        $status = get_post_meta($order_id, 'siwo_status', true);
        $discount = get_post_meta($order_id, 'siwo_discount', true) ?: 0;

        $items = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}siwo_order_items WHERE order_id = %d",
            $order_id
        ), ARRAY_A);

        if (empty($items)) {
            return;
        }

        $wc_order = wc_create_order();
        $wc_order->set_customer_id($customer_id);

        $subtotal = 0;
        foreach ($items as $item) {
            $product = wc_get_product($item['product_id']);
            if ($product) {
                $wc_order->add_product($product, $item['quantity'], [
                    'subtotal' => $item['price'] * $item['quantity'],
                    'total' => $item['price'] * $item['quantity'],
                ]);
                $subtotal += $item['price'] * $item['quantity'];
            }
        }

        $wc_order->set_status($status);
        $wc_order->set_discount_total($discount);
        $wc_order->set_total($subtotal - $discount);
        $wc_order->save();

        update_post_meta($order_id, 'siwo_converted_to_wc', $wc_order->get_id());

        $this->send_notifications($order_id, 'converted');

        wp_redirect(admin_url('admin.php?page=siwo-orders&converted=1'));
        exit;
    }

    /**
     * متد برای لغو سفارش
     */
    private function cancel_order($order_id) {
        update_post_meta($order_id, 'siwo_status', 'cancelled');
        $this->send_notifications($order_id, 'cancelled');
        wp_redirect(admin_url('admin.php?page=siwo-orders&message=cancelled'));
        exit;
    }

    /**
     * متد برای ارسال اعلان‌ها
     */
    public function send_notifications($order_id, $action) {
        $notify_customer = get_option('siwo_notify_customer', 1);
        $notify_admin = get_option('siwo_notify_admin', 1);
        $notification_method = get_option('siwo_notification_method', 'email');

        $customer_id = get_post_meta($order_id, 'siwo_customer', true);
        $customer = get_userdata($customer_id);
        $status = get_post_meta($order_id, 'siwo_status', true);

        // ترجمه action
        $action_labels = [
            'pending' => __('Pending', 'siteiran-wholesale'),
            'processing' => __('Processing', 'siteiran-wholesale'),
            'completed' => __('Completed', 'siteiran-wholesale'),
            'cancelled' => __('Cancelled', 'siteiran-wholesale'),
            'created' => __('Created', 'siteiran-wholesale'),
            'updated' => __('Updated', 'siteiran-wholesale'),
            'converted' => __('Converted', 'siteiran-wholesale'),
        ];
        $translated_action = $action_labels[$action] ?? ucfirst($action);

        $subject = sprintf(__('Order #%s - %s', 'siteiran-wholesale'), $order_id, $translated_action);
        $message = sprintf(__('Order #%s has been %s.', 'siteiran-wholesale'), $order_id, $translated_action);

        // ارسال ایمیل به مشتری
        if ($notify_customer && in_array($notification_method, ['email', 'both'])) {
            if ($customer && $customer->user_email) {
                $email_sent = wp_mail($customer->user_email, $subject, $message);
                if (!$email_sent) {
                    error_log('SIWO: Failed to send email to customer for order #' . $order_id);
                }
            } else {
                error_log('SIWO: Customer email not found for order #' . $order_id);
            }
        }

        // ارسال ایمیل به ادمین
        if ($notify_admin && in_array($notification_method, ['email', 'both'])) {
            $admin_email = get_option('admin_email');
            if ($admin_email) {
                $email_sent = wp_mail($admin_email, $subject, $message);
                if (!$email_sent) {
                    error_log('SIWO: Failed to send email to admin for order #' . $order_id);
                }
            } else {
                error_log('SIWO: Admin email not found for order #' . $order_id);
            }
        }

        // ارسال SMS
        if (in_array($notification_method, ['sms', 'both'])) {
            $this->send_sms($order_id, $action);
        }
    }

    /**
     * متد برای ارسال SMS
     */
    private function send_sms($order_id, $action) {
        $sms_provider = get_option('siwo_sms_provider', 'sms_ir');
        if ($sms_provider === 'sms_ir') {
            $this->send_sms_ir($order_id, $action);
        }
    }

    /**
     * متد برای ارسال SMS از طریق SMS.ir
     */
    private function send_sms_ir($order_id, $action) {
        $api_key = get_option('siwo_sms_ir_api_key', '');
        $template_id = get_option('siwo_sms_ir_template_id', '');
        $service_number = get_option('siwo_sms_ir_service_number', '');

        if (empty($api_key) || empty($template_id) || empty($service_number)) {
            error_log('SIWO: SMS.ir configuration missing for order #' . $order_id);
            return;
        }
        $customer_id = get_post_meta($order_id, 'siwo_customer', true);
        $customer = get_userdata($customer_id);
        $phone = get_user_meta($customer_id, 'billing_phone', true);
        $admin_phone = get_option('siwo_admin_phone', '');

        // error_log("Error: " . $customer_id . " Order: " . $order_id);
        // die();

        $sms_params = get_option('siwo_sms_params', []);
        $parameters = [];
        foreach ($sms_params as $param) {
            $value = '';
            switch ($param['name']) {
                case 'ORDER_ID':
                case 'ORDER':
                    $value = $order_id;
                    break;
                case 'STATUS':
                    $status = get_post_meta($order_id, 'siwo_status', true);
                    $status_labels = [
                        'pending' => __('Pending', 'siteiran-wholesale'),
                        'processing' => __('Processing', 'siteiran-wholesale'),
                        'completed' => __('Completed', 'siteiran-wholesale'),
                        'cancelled' => __('Cancelled', 'siteiran-wholesale'),
                    ];
                    $value = $status_labels[$status] ?? ucfirst($status);
                    break;
                case 'PRODUCTS':
                    global $wpdb;
                    $items = $wpdb->get_results($wpdb->prepare(
                        "SELECT * FROM {$wpdb->prefix}siwo_order_items WHERE order_id = %d",
                        $order_id
                    ), ARRAY_A);
                    $products = [];
                    foreach ($items as $item) {
                        $product = wc_get_product($item['product_id']);
                        if ($product) {
                            $products[] = $product->get_name() . ' (' . $item['quantity'] . ')';
                        }
                    }
                    $value = implode(', ', $products);
                    break;
                case 'NOTES':
                    $value = get_post_meta($order_id, 'siwo_notes', true);
                    break;
                case 'FULLNAME':
                case 'NAME':
                    $full_name = trim($customer->first_name . ' ' . $customer->last_name);
                    $value = $full_name ?: 'مشتری گرامی';
                    break;
                default:
                    $value = $param['value'];
            }
            $parameters[] = [
                'name' => $param['name'],
                'value' => $value,
            ];
        }

        $notify_customer = get_option('siwo_notify_customer', 1);
        $notify_admin = get_option('siwo_notify_admin', 1);

        if ($notify_customer && $phone) {
            $this->send_sms_ir_request($api_key, $template_id, $service_number, $phone, $parameters, $order_id);
        } else {
            error_log('SIWO: Customer phone not found for order #' . $order_id);
        }

        if ($notify_admin && $admin_phone) {
            $this->send_sms_ir_request($api_key, $template_id, $service_number, $admin_phone, $parameters, $order_id);
        } else {
            error_log('SIWO: Admin phone not found for order #' . $order_id);
        }
    }

    /**
     * متد برای ارسال درخواست SMS به SMS.ir
     */
    private function send_sms_ir_request($api_key, $template_id, $service_number, $phone, $parameters, $order_id) {
        $url = 'https://api.sms.ir/v1/send/verify';
        $data = [
            'mobile' => $phone,
            'templateId' => $template_id,
            'parameters' => $parameters,
        ];
        error_log('SIWO: SMS.ir request data for order #' . $order_id . ': ' . print_r($data, true));

        $args = [
            'body' => json_encode($data),
            'headers' => [
                'Content-Type' => 'application/json',
                'x-api-key' => $api_key,
            ],
            'method' => 'POST',
        ];

        $response = wp_remote_request($url, $args);
        if (is_wp_error($response)) {
            error_log('SIWO: SMS.ir Error for order #' . $order_id . ': ' . $response->get_error_message());
        } else {
            $body = wp_remote_retrieve_body($response);
            $response_data = json_decode($body, true);
            if (isset($response_data['status']) && $response_data['status'] !== 1) {
                error_log('SIWO: SMS.ir failed for order #' . $order_id . ': ' . print_r($response_data, true));
            } else {
                error_log('SIWO: SMS.ir successfully sent for order #' . $order_id . ': ' . print_r($response_data, true));
            }
        }
    }

    /**
     * متد برای دریافت داده‌های سفارش از طریق AJAX
     */
    public function get_order_data() {
        global $wpdb;

        $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
        if (!$order_id) {
            wp_send_json_error(['message' => __('Invalid order ID.', 'siteiran-wholesale')]);
        }

        $order = get_post($order_id);
        if (!$order || $order->post_type !== 'siwo_order') {
            wp_send_json_error(['message' => __('Order not found.', 'siteiran-wholesale')]);
        }

        $customer_id = get_post_meta($order_id, 'siwo_customer', true);
        $customer = get_userdata($customer_id);
        $status = get_post_meta($order_id, 'siwo_status', true);
        $notes = get_post_meta($order_id, 'siwo_notes', true);
        $discount = get_post_meta($order_id, 'siwo_discount', true) ?: 0;

        // ترجمه وضعیت
        $status_labels = [
            'pending' => __('Pending', 'siteiran-wholesale'),
            'processing' => __('Processing', 'siteiran-wholesale'),
            'completed' => __('Completed', 'siteiran-wholesale'),
            'cancelled' => __('Cancelled', 'siteiran-wholesale'),
        ];
        $translated_status = $status_labels[$status] ?? ucfirst($status);

        $items = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}siwo_order_items WHERE order_id = %d",
            $order_id
        ), ARRAY_A);

        $products = [];
        foreach ($items as $item) {
            $product = wc_get_product($item['product_id']);
            if ($product) {
                $products[$product->get_name()] = [
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'price_formatted' => wc_price($item['price']),
                    'total_formatted' => wc_price($item['price'] * $item['quantity']),
                ];
            }
        }

        $data = [
            'invoice_number' => $order_id,
            'customer' => $customer ? $customer->display_name : __('Unknown', 'siteiran-wholesale'),
            'status' => $translated_status,
            'date' => get_the_date('', $order_id),
            'products' => $products,
            'notes' => $notes,
            'discount' => $discount,
            'currency' => get_woocommerce_currency(),
        ];

        wp_send_json_success($data);
    }

    /**
     * متد برای ارسال اعلان‌ها هنگام ثبت یا ویرایش سفارش
     */
    public function handle_order_notifications($post_id, $post, $update) {
        // فقط برای سفارش‌های نوع siwo_order
        if ($post->post_type !== 'siwo_order') {
            return;
        }

        // جلوگیری از اجرای اعلان‌ها هنگام ذخیره خودکار یا پیش‌نویس
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // بررسی اینکه آیا سفارش جدید است یا ویرایش شده
        $action = $update ? 'updated' : 'created';

        // ارسال اعلان‌ها
        $this->send_notifications($post_id, $action);
    }


    // تابع ذخیره‌سازی سفارش
    public static function save_ajax_order($order_data) {
        global $wpdb;
    
        $user_id = get_current_user_id(); // شناسه کاربر فعلی
        $customer_id = $user_id; // چون توی My Account کاربر خودش سفارش می‌ده
        $status = isset($order_data['siwo_status']) ? sanitize_text_field($order_data['siwo_status']) : 'pending';
        $notes = isset($order_data['siwo_notes']) ? sanitize_textarea_field($order_data['siwo_notes']) : '';
        $discount = isset($order_data['siwo_discount']) ? floatval($order_data['siwo_discount']) : 0;
        $products = isset($order_data['siwo_products']) ? (array) $order_data['siwo_products'] : [];
        $quantities = isset($order_data['siwo_products_quantity']) ? (array) $order_data['siwo_products_quantity'] : [];
    
        $order_data_post = [
            'post_title' => sprintf(__('Order #%s', 'siteiran-wholesale'), 'New'),
            'post_type' => 'siwo_order',
            'post_status' => 'publish',
        ];
    
        $order_id = wp_insert_post($order_data_post);
    
        if ($order_id) {
            // ذخیره متادیتا
            update_post_meta($order_id, 'siwo_customer', $customer_id);
            update_post_meta($order_id, 'siwo_status', $status);
            update_post_meta($order_id, 'siwo_notes', $notes);
            update_post_meta($order_id, 'siwo_discount', $discount);
    
            // ذخیره آیتم‌ها
            foreach ($products as $index => $product_id) {
                $product_id = intval($product_id);
                $quantity = isset($quantities[$index]) ? intval($quantities[$index]) : 0;
                if ($product_id && $quantity > 0) {
                    $product = wc_get_product($product_id);
                    if ($product) {
                        $wpdb->insert(
                            $wpdb->prefix . 'siwo_order_items',
                            [
                                'order_id' => $order_id,
                                'product_id' => $product_id,
                                'quantity' => $quantity,
                                'price' => $product->get_price(),
                            ],
                            ['%d', '%d', '%d', '%f']
                        );
                    }
                }
            }
    
            // ارسال اعلان
            $orders_handler = new SIWO_Orders();
            $orders_handler->send_notifications($order_id, 'created');
    
            return $order_id;
        }
    
        return false;
    }

}

// ثبت اکشن AJAX برای دریافت داده‌های سفارش
add_action('wp_ajax_siwo_get_order_data', [new SIWO_Orders(), 'get_order_data']);

// ثبت هوک برای ارسال اعلان‌ها هنگام ثبت یا ویرایش سفارش
//add_action('save_post_siwo_order', [new SIWO_Orders(), 'handle_order_notifications'], 10, 3);