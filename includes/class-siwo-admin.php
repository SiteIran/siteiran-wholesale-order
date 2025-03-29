<?php
if (!defined('ABSPATH')) {
    exit; // جلوگیری از دسترسی مستقیم به فایل
}

/**
 * کلاس SIWO_Admin برای مدیریت بخش ادمین
 */
class SIWO_Admin {
    public function init() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('wp_ajax_siwo_search_products', [$this, 'search_products']);
    }

    public function add_admin_menu() {
        add_menu_page(
            __('Wholesale Orders', 'siteiran-wholesale'),
            __('Wholesale Orders', 'siteiran-wholesale'),
            'manage_options',
            'siwo-orders',
            [$this, 'orders_page'],
            'dashicons-cart',
            56
        );

        add_submenu_page(
            'siwo-orders',
            __('Add Order', 'siteiran-wholesale'),
            __('Add Order', 'siteiran-wholesale'),
            'manage_options',
            'siwo-add-order',
            [$this, 'add_order_page']
        );

        add_submenu_page(
            'siwo-orders',
            __('Reports', 'siteiran-wholesale'),
            __('Reports', 'siteiran-wholesale'),
            'manage_options',
            'siwo-reports',
            [$this, 'reports_page']
        );

        add_submenu_page(
            'siwo-orders',
            __('Settings', 'siteiran-wholesale'),
            __('Settings', 'siteiran-wholesale'),
            'manage_options',
            'siwo-settings',
            [$this, 'settings_page']
        );
    }


    /**
     * متد برای رندر هدر
     */
    private function render_header() {
        $logo_url = plugin_dir_url(__FILE__) . '../assets/images/logo.png'; // مسیر لوگو
        ?>
        <div class="siwo-header">
            <div class="siwo-header-content">
                <img src="<?php echo esc_url($logo_url); ?>" alt="<?php _e('Logo', 'siteiran-wholesale'); ?>" class="siwo-logo" />
                <h1><?php _e('Wholesale Management', 'siteiran-wholesale'); ?></h1>
            </div>
        </div>
        <?php
    }

    /**
     * متد برای رندر فوتر
     */
    private function render_footer() {
        $current_year = date('Y');
        ?>
        <div class="siwo-footer">
            <p>
                <?php
                printf(
                    /* translators: %1$s: Current year, %2$s: Company name */
                    __('&copy; %1$s %2$s. All rights reserved.', 'siteiran-wholesale'),
                    esc_html($current_year),
                    //esc_html__('Your Company Name', 'siteiran-wholesale') // نام شرکت یا برندت رو اینجا وارد کن
                    esc_html__('SiteIran (Aryanpour)', 'siteiran-wholesale') // نام شرکت یا برندت رو اینجا وارد کن
                );
                ?>
            </p>
        </div>
        <?php
    }

    public function orders_page() {
        $orders = new SIWO_Orders();
        $orders->handle_actions();
        ?>
        <div class="siwo-page-wrapper">
            <?php $this->render_header(); ?>
            <div class="siwo-content">
                <?php $orders->render(); ?>
            </div>
            <?php $this->render_footer(); ?>
        </div>
        <?php
    }

    public function add_order_page() {
        global $wpdb;
    
        $order_id = isset($_GET['edit_order']) ? intval($_GET['edit_order']) : 0;
        $order = $order_id ? get_post($order_id) : null;
        $is_edit = $order && $order->post_type === 'siwo_order';
    
        if (isset($_POST['siwo_save_order'])) {
            $customer_id = intval($_POST['siwo_customer']);
            $status = sanitize_text_field($_POST['siwo_status']);
            $notes = sanitize_textarea_field($_POST['siwo_notes']);
            $discount = floatval($_POST['siwo_discount']);
            $products = isset($_POST['siwo_products']) ? (array) $_POST['siwo_products'] : [];
            $quantities = isset($_POST['siwo_products_quantity']) ? (array) $_POST['siwo_products_quantity'] : [];
        
            $order_data = [
                'post_title' => sprintf(__('Order #%s', 'siteiran-wholesale'), $order_id ? $order_id : 'New'),
                'post_type' => 'siwo_order',
                'post_status' => 'publish',
            ];
        
            if ($is_edit) {
                $order_data['ID'] = $order_id;
                wp_update_post($order_data);
            } else {
                $order_id = wp_insert_post($order_data);
            }
        
            // ذخیره متادیتا
            update_post_meta($order_id, 'siwo_customer', $customer_id);
            update_post_meta($order_id, 'siwo_status', $status);
            update_post_meta($order_id, 'siwo_notes', $notes);
            update_post_meta($order_id, 'siwo_discount', $discount);
        
            // حذف و اضافه آیتم‌های سفارش
            $wpdb->delete($wpdb->prefix . 'siwo_order_items', ['order_id' => $order_id], ['%d']);
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
        
            // فراخوانی تابع اعلان‌ها بعد از ذخیره همه داده‌ها
            $orders_handler = new SIWO_Orders();
            $orders_handler->send_notifications($order_id, $is_edit ? 'updated' : 'created');
        
            wp_redirect(admin_url('admin.php?page=siwo-orders'));
            exit;
        }
    
        // گرفتن لیست همه کاربران
        $customers = get_users([
            'fields' => ['ID', 'display_name'],
        ]);
    
        $customer_id = $is_edit ? get_post_meta($order_id, 'siwo_customer', true) : 0;
        $status = $is_edit ? get_post_meta($order_id, 'siwo_status', true) : get_option('siwo_order_status', 'pending');
        $notes = $is_edit ? get_post_meta($order_id, 'siwo_notes', true) : '';
        $discount = $is_edit ? get_post_meta($order_id, 'siwo_discount', true) : get_option('siwo_discount_percent', 0);
    
        $items = $is_edit ? $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}siwo_order_items WHERE order_id = %d",
            $order_id
        ), ARRAY_A) : [];
        ?>
        <div class="siwo-page-wrapper">
            <?php $this->render_header(); ?>
            <div class="siwo-content">
                <div class="wrap siwo-wrap">
                    <h1 class="mb-4"><?php echo $is_edit ? __('Edit Order', 'siteiran-wholesale') : __('Add New Order', 'siteiran-wholesale'); ?></h1>
                    <form method="post" class="siwo-add-order-form">
                        <div class="card shadow-sm mb-4">
                            <div class="card-body">
                                <h5 class="card-title"><?php _e('Order Details', 'siteiran-wholesale'); ?></h5>
                                <div class="mb-3">
                                    <label for="siwo_customer" class="form-label"><?php _e('Customer', 'siteiran-wholesale'); ?></label>
                                    <select name="siwo_customer" id="siwo_customer" class="form-select siwo-select2" required>
                                        <option value=""><?php _e('Select Customer', 'siteiran-wholesale'); ?></option>
                                        <?php foreach ($customers as $customer) : ?>
                                            <option value="<?php echo esc_attr($customer->ID); ?>" <?php selected($customer_id, $customer->ID); ?>>
                                                <?php echo esc_html($customer->display_name); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="siwo_status" class="form-label"><?php _e('Status', 'siteiran-wholesale'); ?></label>
                                    <select name="siwo_status" id="siwo_status" class="form-select">
                                        <option value="pending" <?php selected($status, 'pending'); ?>><?php _e('Pending', 'siteiran-wholesale'); ?></option>
                                        <option value="processing" <?php selected($status, 'processing'); ?>><?php _e('Processing', 'siteiran-wholesale'); ?></option>
                                        <option value="completed" <?php selected($status, 'completed'); ?>><?php _e('Completed', 'siteiran-wholesale'); ?></option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="siwo_notes" class="form-label"><?php _e('Notes', 'siteiran-wholesale'); ?></label>
                                    <textarea name="siwo_notes" id="siwo_notes" class="form-control" rows="3"><?php echo esc_textarea($notes); ?></textarea>
                                </div>
                                <div class="mb-3">
                                    <label for="siwo_discount" class="form-label"><?php _e('Discount', 'siteiran-wholesale'); ?></label>
                                    <input type="number" name="siwo_discount" id="siwo_discount" class="form-control w-25" value="<?php echo esc_attr($discount); ?>" step="0.01" min="0" />
                                </div>
                            </div>
                        </div>
    
                        <div class="card shadow-sm mb-4">
                            <div class="card-body">
                                <h5 class="card-title"><?php _e('Products', 'siteiran-wholesale'); ?></h5>
                                <div id="siwo-products">
                                    <?php if ($is_edit && !empty($items)) : ?>
                                        <?php foreach ($items as $index => $item) : ?>
                                            <?php $product = wc_get_product($item['product_id']); ?>
                                            <?php if ($product) : ?>
                                                <div class="siwo-product-row mb-3 d-flex align-items-center">
                                                    <select name="siwo_products[]" class="form-select siwo-select2-product w-50 me-2">
                                                        <option value="<?php echo esc_attr($item['product_id']); ?>" selected>
                                                            <?php echo esc_html($product->get_name()); ?>
                                                        </option>
                                                    </select>
                                                    <input type="number" name="siwo_products_quantity[]" class="form-control w-25 me-2" value="<?php echo esc_attr($item['quantity']); ?>" min="1" />
                                                    <button type="button" class="btn btn-danger btn-sm siwo-remove-product"><i class="bi bi-trash"></i></button>
                                                </div>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                                <button type="button" id="siwo-add-product" class="btn btn-outline-primary"><i class="bi bi-plus-circle"></i> <?php _e('Add Product', 'siteiran-wholesale'); ?></button>
                            </div>
                        </div>
    
                        <button type="submit" name="siwo_save_order" class="btn btn-primary"><?php _e('Save Order', 'siteiran-wholesale'); ?></button>
                    </form>
                </div>
            </div>
            <?php $this->render_footer(); ?>
        </div>
        <?php
    }

    public function reports_page() {
        global $wpdb;

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
        $report_type = isset($_GET['report_type']) ? sanitize_text_field($_GET['report_type']) : '';

        // تنظیم بازه زمانی بر اساس نوع گزارش
        if ($report_type) {
            $args['date_query'] = [];
            $today = current_time('Y-m-d');
            switch ($report_type) {
                case 'today':
                    $args['date_query']['after'] = $today;
                    $args['date_query']['before'] = $today;
                    break;
                case 'yesterday':
                    $yesterday = date('Y-m-d', strtotime('-1 day', strtotime($today)));
                    $args['date_query']['after'] = $yesterday;
                    $args['date_query']['before'] = $yesterday;
                    break;
                case 'last_week':
                    $last_week_start = date('Y-m-d', strtotime('-7 days', strtotime($today)));
                    $last_week_end = date('Y-m-d', strtotime('-1 day', strtotime($today)));
                    $args['date_query']['after'] = $last_week_start;
                    $args['date_query']['before'] = $last_week_end;
                    break;
                case 'last_month':
                    $last_month_start = date('Y-m-d', strtotime('-30 days', strtotime($today)));
                    $last_month_end = date('Y-m-d', strtotime('-1 day', strtotime($today)));
                    $args['date_query']['after'] = $last_month_start;
                    $args['date_query']['before'] = $last_month_end;
                    break;
            }
            $args['date_query']['inclusive'] = true;
        }

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

        // اجرای کوئری با فیلترها
        $orders_query = new WP_Query($args);

        // گرفتن لیست همه کاربران برای فیلتر
        $customers = get_users([
            'fields' => ['ID', 'display_name'],
        ]);

        // محاسبه مجموع کل سفارشات
        $total_orders = 0;
        $total_amount = 0;
        $total_discount = 0;
        $orders_data = [];
        $customer_orders = [];
        $chart_data = [
            'labels' => [],
            'data' => [],
        ];

        if ($orders_query->have_posts()) {
            while ($orders_query->have_posts()) {
                $orders_query->the_post();
                $order_id = get_the_ID();
                $customer_id = get_post_meta($order_id, 'siwo_customer', true);
                $customer = get_userdata($customer_id);
                $status = get_post_meta($order_id, 'siwo_status', true);
                $discount = get_post_meta($order_id, 'siwo_discount', true) ?: 0;
                $order_date = get_the_date('Y-m-d');

                // محاسبه جمع کل سفارش
                $items = $wpdb->get_results($wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}siwo_order_items WHERE order_id = %d",
                    $order_id
                ), ARRAY_A);

                $subtotal = 0;
                foreach ($items as $item) {
                    $subtotal += $item['price'] * $item['quantity'];
                }
                $order_total = $subtotal - $discount;

                $total_orders++;
                $total_amount += $order_total;
                $total_discount += $discount;

                $orders_data[] = [
                    'order_id' => $order_id,
                    'customer' => $customer ? $customer->display_name : __('Unknown', 'siteiran-wholesale'),
                    'status' => $status,
                    'date' => get_the_date(),
                    'subtotal' => $subtotal,
                    'discount' => $discount,
                    'total' => $order_total,
                ];

                // جمع‌آوری داده‌ها برای گزارش بیشترین سفارشات
                if ($customer) {
                    if (!isset($customer_orders[$customer_id])) {
                        $customer_orders[$customer_id] = [
                            'name' => $customer->display_name,
                            'order_count' => 0,
                            'total_amount' => 0,
                        ];
                    }
                    $customer_orders[$customer_id]['order_count']++;
                    $customer_orders[$customer_id]['total_amount'] += $order_total;
                }

                // جمع‌آوری داده‌ها برای نمودار
                if (!isset($chart_data['labels'][$order_date])) {
                    $chart_data['labels'][$order_date] = $order_date;
                    $chart_data['data'][$order_date] = 0;
                }
                $chart_data['data'][$order_date] += $order_total;
            }
        }

        wp_reset_postdata();

        // مرتب‌سازی مشتریان بر اساس تعداد سفارشات
        usort($customer_orders, function($a, $b) {
            return $b['order_count'] - $a['order_count'];
        });

        // مرتب‌سازی داده‌های نمودار بر اساس تاریخ
        ksort($chart_data['labels']);
        ksort($chart_data['data']);
        $chart_labels = array_values($chart_data['labels']);
        $chart_values = array_values($chart_data['data']);
        ?>
        <div class="siwo-page-wrapper">
            <?php $this->render_header(); ?>
            <div class="siwo-content">
                <div class="wrap siwo-wrap">
                    <h1 class="mb-4"><?php _e('Reports', 'siteiran-wholesale'); ?></h1>

                    <!-- فرم فیلترها -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-body">
                            <h5 class="card-title"><?php _e('Filter Reports', 'siteiran-wholesale'); ?></h5>
                            <form method="get" class="row g-3">
                                <input type="hidden" name="page" value="siwo-reports" />
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
                                    <label for="report_type" class="form-label"><?php _e('Report Type', 'siteiran-wholesale'); ?></label>
                                    <select name="report_type" id="report_type" class="form-select">
                                        <option value=""><?php _e('Custom Range', 'siteiran-wholesale'); ?></option>
                                        <option value="today" <?php selected($report_type, 'today'); ?>><?php _e('Today', 'siteiran-wholesale'); ?></option>
                                        <option value="yesterday" <?php selected($report_type, 'yesterday'); ?>><?php _e('Yesterday', 'siteiran-wholesale'); ?></option>
                                        <option value="last_week" <?php selected($report_type, 'last_week'); ?>><?php _e('Last Week', 'siteiran-wholesale'); ?></option>
                                        <option value="last_month" <?php selected($report_type, 'last_month'); ?>><?php _e('Last Month', 'siteiran-wholesale'); ?></option>
                                    </select>
                                </div>
                                <div class="col-12 mt-3">
                                    <button type="submit" class="btn btn-primary me-2"><?php _e('Filter', 'siteiran-wholesale'); ?></button>
                                    <a href="<?php echo admin_url('admin.php?page=siwo-reports'); ?>" class="btn btn-outline-secondary"><?php _e('Reset', 'siteiran-wholesale'); ?></a>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- خلاصه گزارشات -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-body">
                            <h5 class="card-title"><?php _e('Summary', 'siteiran-wholesale'); ?></h5>
                            <div class="row">
                                <div class="col-md-4">
                                    <p><strong><?php _e('Total Orders:', 'siteiran-wholesale'); ?></strong> <?php echo esc_html($total_orders); ?></p>
                                </div>
                                <div class="col-md-4">
                                    <p><strong><?php _e('Total Amount:', 'siteiran-wholesale'); ?></strong> <?php echo wc_price($total_amount); ?></p>
                                </div>
                                <div class="col-md-4">
                                    <p><strong><?php _e('Total Discount:', 'siteiran-wholesale'); ?></strong> <?php echo wc_price($total_discount); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- نمودار سفارشات -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-body">
                            <h5 class="card-title"><?php _e('Order Trends', 'siteiran-wholesale'); ?></h5>
                            <canvas id="siwoOrderChart" height="100"></canvas>
                        </div>
                    </div>

                    <!-- گزارش بیشترین سفارشات -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-body">
                            <h5 class="card-title"><?php _e('Top Customers', 'siteiran-wholesale'); ?></h5>
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead class="table-dark">
                                        <tr>
                                            <th><?php _e('Customer', 'siteiran-wholesale'); ?></th>
                                            <th><?php _e('Number of Orders', 'siteiran-wholesale'); ?></th>
                                            <th><?php _e('Total Amount', 'siteiran-wholesale'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($customer_orders)) : ?>
                                            <?php $top_customers = array_slice($customer_orders, 0, 5); // نمایش 5 مشتری برتر ?>
                                            <?php foreach ($top_customers as $customer) : ?>
                                                <tr>
                                                    <td><?php echo esc_html($customer['name']); ?></td>
                                                    <td><?php echo esc_html($customer['order_count']); ?></td>
                                                    <td><?php echo wc_price($customer['total_amount']); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else : ?>
                                            <tr>
                                                <td colspan="3"><?php _e('No customers found.', 'siteiran-wholesale'); ?></td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- جدول گزارشات -->
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title"><?php _e('Order Reports', 'siteiran-wholesale'); ?></h5>
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead class="table-dark">
                                        <tr>
                                            <th><?php _e('Order ID', 'siteiran-wholesale'); ?></th>
                                            <th><?php _e('Customer', 'siteiran-wholesale'); ?></th>
                                            <th><?php _e('Subtotal', 'siteiran-wholesale'); ?></th>
                                            <th><?php _e('Discount', 'siteiran-wholesale'); ?></th>
                                            <th><?php _e('Total', 'siteiran-wholesale'); ?></th>
                                            <th><?php _e('Status', 'siteiran-wholesale'); ?></th>
                                            <th><?php _e('Date', 'siteiran-wholesale'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($orders_data)) : ?>
                                            <?php foreach ($orders_data as $order) : ?>
                                                <?php
                                                // ترجمه وضعیت‌ها
                                                $status_labels = [
                                                    'pending' => __('Pending', 'siteiran-wholesale'),
                                                    'processing' => __('Processing', 'siteiran-wholesale'),
                                                    'completed' => __('Completed', 'siteiran-wholesale'),
                                                    'cancelled' => __('Cancelled', 'siteiran-wholesale'),
                                                ];

                                                // رنگ‌بندی وضعیت‌ها
                                                $status_class = '';
                                                switch ($order['status']) {
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
                                                    <td>#<?php echo esc_html($order['order_id']); ?></td>
                                                    <td><?php echo esc_html($order['customer']); ?></td>
                                                    <td><?php echo wc_price($order['subtotal']); ?></td>
                                                    <td><?php echo wc_price($order['discount']); ?></td>
                                                    <td><?php echo wc_price($order['total']); ?></td>
                                                    <td><span class="<?php echo esc_attr($status_class); ?>"><?php echo esc_html($status_labels[$order['status']] ?? ucfirst($order['status'])); ?></span></td>
                                                    <td><?php echo esc_html($order['date']); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else : ?>
                                            <tr>
                                                <td colspan="7"><?php _e('No orders found.', 'siteiran-wholesale'); ?></td>
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
                </div>
            </div>
            <?php $this->render_footer(); ?>
        </div>

        <!-- اسکریپت برای نمودار -->
        <script>
            jQuery(document).ready(function($) {
                var ctx = document.getElementById('siwoOrderChart').getContext('2d');
                var chart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: <?php echo json_encode($chart_labels); ?>,
                        datasets: [{
                            label: '<?php _e("Order Amount", "siteiran-wholesale"); ?>',
                            data: <?php echo json_encode($chart_values); ?>,
                            backgroundColor: 'rgba(54, 162, 235, 0.2)',
                            borderColor: 'rgba(54, 162, 235, 1)',
                            borderWidth: 1,
                            fill: true,
                        }]
                    },
                    options: {
                        scales: {
                            y: {
                                beginAtZero: true,
                                title: {
                                    display: true,
                                    text: '<?php _e("Amount", "siteiran-wholesale"); ?> (<?php echo get_woocommerce_currency(); ?>)'
                                }
                            },
                            x: {
                                title: {
                                    display: true,
                                    text: '<?php _e("Date", "siteiran-wholesale"); ?>'
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                display: true,
                                position: 'top',
                            }
                        }
                    }
                });
            });
        </script>
        <?php
    }

    public function settings_page() {
        $settings = new SIWO_Settings();
        ?>
        <div class="siwo-page-wrapper">
            <?php $this->render_header(); ?>
            <div class="siwo-content">
                <?php $settings->render(); ?>
            </div>
            <?php $this->render_footer(); ?>
        </div>
        <?php
    }

    public function search_products() {
        $term = isset($_GET['term']) ? sanitize_text_field($_GET['term']) : '';
        $selected_id = isset($_GET['selected_id']) ? sanitize_text_field($_GET['selected_id']) : '';
    
        $results = [];
    
        // اگر selected_id وجود داشته باشه، اطلاعات محصول انتخاب‌شده رو برگردون
        if ($selected_id) {
            $product = wc_get_product($selected_id);
            if ($product) {
                $results[] = [
                    'id' => $product->ID,
                    'text' => $product->get_name(),
                ];
            }
        }
    
        // جستجوی محصولات بر اساس term
        if ($term) {
            $products = get_posts([
                'post_type' => 'product',
                'posts_per_page' => 10,
                's' => $term,
                'post_status' => 'publish',
            ]);
    
            foreach ($products as $product) {
                // فقط محصولاتی که با selected_id یکی نیستن رو اضافه کن
                if (!$selected_id || $product->ID != $selected_id) {
                    $results[] = [
                        'id' => $product->ID,
                        'text' => $product->post_title,
                    ];
                }
            }
        }
    
        wp_send_json($results);
    }
}