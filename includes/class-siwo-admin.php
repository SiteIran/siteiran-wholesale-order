<?php
if (!defined('ABSPATH')) {
    exit;
}

class SIWO_Admin {
    public function init() {
        add_action('admin_menu', [$this, 'register_menu']);
        add_action('wp_ajax_siwo_search_products', [$this, 'search_products']);
        add_action('wp_ajax_siwo_get_order_data', [$this, 'get_order_data_for_print']);
        add_action('wp_ajax_siwo_get_product_price', [$this, 'get_product_price']);
        add_action('admin_init', [$this, 'handle_order_conversion']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_report_scripts']);
    }

    public function register_menu() {
        add_menu_page(
            __('SiteIran Wholesale', 'siteiran-wholesale'),
            __('Wholesale Orders', 'siteiran-wholesale'),
            'manage_options',
            'siwo-dashboard',
            [$this, 'dashboard_page'],
            'dashicons-cart',
            30
        );
    
        add_submenu_page('siwo-dashboard', __('Add Order', 'siteiran-wholesale'), __('Add Order', 'siteiran-wholesale'), 'manage_options', 'siwo-add-order', [$this, 'add_order_page']);
        add_submenu_page('siwo-dashboard', __('Settings', 'siteiran-wholesale'), __('Settings', 'siteiran-wholesale'), 'manage_options', 'siwo-settings', [$this, 'settings_page']);
        add_submenu_page('siwo-dashboard', __('Order List', 'siteiran-wholesale'), __('Order List', 'siteiran-wholesale'), 'manage_options', 'siwo-orders', [$this, 'orders_page']);
        add_submenu_page('siwo-dashboard', __('Reports', 'siteiran-wholesale'), __('Reports', 'siteiran-wholesale'), 'manage_options', 'siwo-reports', [$this, 'reports_page']);
    }

    public function dashboard_page() {
        ?>
        <div class="wrap siwo-wrap">
            <h1><?php _e('SiteIran Wholesale Dashboard', 'siteiran-wholesale'); ?></h1>
            <div class="siwo-dashboard">
                <a href="?page=siwo-add-order" class="button button-primary"><?php _e('Add New Order', 'siteiran-wholesale'); ?></a>
                <a href="?page=siwo-orders" class="button"><?php _e('View Orders', 'siteiran-wholesale'); ?></a>
                <a href="?page=siwo-settings" class="button"><?php _e('Settings', 'siteiran-wholesale'); ?></a>
            </div>
        </div>
        <?php
    }

    public function add_order_page() {
        $is_edit = isset($_GET['edit_order']) && is_numeric($_GET['edit_order']);
        $order_id = $is_edit ? intval($_GET['edit_order']) : 0;
        $order_data = $is_edit ? get_post_meta($order_id, 'siwo_products', true) : [];
        $order_status = $is_edit ? get_post_meta($order_id, 'siwo_status', true) : '';
        $order_notes = $is_edit ? get_post_meta($order_id, 'siwo_notes', true) : '';
        $is_converted = $is_edit && get_post_meta($order_id, 'siwo_converted_to_wc', true);
    
        if (isset($_POST['siwo_save_order']) && !$is_converted) { // فقط اگه تبدیل نشده باشه ذخیره کنه
            $this->save_order($_POST, $order_id);
        }
    
        $title = $is_edit ? __('Edit Wholesale Order', 'siteiran-wholesale') : __('Add New Wholesale Order', 'siteiran-wholesale');
        ?>
        <div class="wrap siwo-wrap">
            <h1 class="mb-4"><?php echo esc_html($title); ?></h1>
            <?php if ($is_converted) : ?>
                <div class="alert alert-info"><?php _e('This order has been converted to WooCommerce and cannot be edited.', 'siteiran-wholesale'); ?></div>
            <?php endif; ?>
            <form method="post" class="siwo-order-form" <?php echo $is_converted ? 'disabled' : ''; ?>>
                <div class="table-responsive">
                    <table class="table table-bordered siwo-order-table">
                        <thead class="table-light">
                            <tr>
                                <th><?php _e('Product', 'siteiran-wholesale'); ?></th>
                                <th><?php _e('Quantity', 'siteiran-wholesale'); ?></th>
                                <th><?php _e('Price', 'siteiran-wholesale'); ?></th>
                                <th><?php _e('Action', 'siteiran-wholesale'); ?></th>
                            </tr>
                        </thead>
                        <tbody id="siwo-order-items">
                            <?php
                            if ($is_edit && !empty($order_data)) {
                                foreach ($order_data as $product_id => $quantity) {
                                    $product = wc_get_product($product_id);
                                    if ($product) {
                                        ?>
                                        <tr>
                                            <td>
                                                <?php if ($is_converted) : ?>
                                                    <?php echo esc_html($product->get_name()); ?>
                                                <?php else : ?>
                                                    <select class="siwo-product-search form-select" name="products[]">
                                                        <option value="<?php echo esc_attr($product_id); ?>" selected><?php echo esc_html($product->get_name()); ?></option>
                                                    </select>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($is_converted) : ?>
                                                    <?php echo esc_html($quantity); ?>
                                                <?php else : ?>
                                                    <input type="number" class="form-control" name="quantity[]" min="1" value="<?php echo esc_attr($quantity); ?>" />
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo wc_price($product->get_price()); ?></td>
                                            <td>
                                                <?php if (!$is_converted) : ?>
                                                    <button type="button" class="btn btn-danger siwo-remove-row"><?php _e('Remove', 'siteiran-wholesale'); ?></button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php
                                    }
                                }
                            } else {
                                ?>
                                <tr>
                                    <td>
                                        <select class="siwo-product-search form-select" name="products[]">
                                            <option value=""><?php _e('Search product...', 'siteiran-wholesale'); ?></option>
                                        </select>
                                    </td>
                                    <td><input type="number" class="form-control" name="quantity[]" min="1" value="1" /></td>
                                    <td>-</td>
                                    <td><button type="button" class="btn btn-danger siwo-remove-row"><?php _e('Remove', 'siteiran-wholesale'); ?></button></td>
                                </tr>
                                <?php
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
                <?php if ($is_edit) : ?>
                    <div class="mb-3">
                        <label class="form-label"><?php _e('Order Status:', 'siteiran-wholesale'); ?></label>
                        <?php if ($is_converted) : ?>
                            <?php echo esc_html($order_status); ?>
                        <?php else : ?>
                            <select name="order_status" class="form-select w-25">
                                <option value="pending" <?php selected($order_status, 'pending'); ?>><?php _e('Pending', 'siteiran-wholesale'); ?></option>
                                <option value="processing" <?php selected($order_status, 'processing'); ?>><?php _e('Processing', 'siteiran-wholesale'); ?></option>
                                <option value="completed" <?php selected($order_status, 'completed'); ?>><?php _e('Completed', 'siteiran-wholesale'); ?></option>
                            </select>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                <div class="mb-3">
                    <label class="form-label"><?php _e('Order Notes:', 'siteiran-wholesale'); ?></label>
                    <?php if ($is_converted) : ?>
                        <?php echo esc_html($order_notes); ?>
                    <?php else : ?>
                        <textarea name="order_notes" class="form-control" rows="3"><?php echo esc_textarea($order_notes); ?></textarea>
                    <?php endif; ?>
                </div>
                <?php if (!$is_converted) : ?>
                    <button type="button" id="siwo-add-row" class="btn btn-secondary"><?php _e('Add Product', 'siteiran-wholesale'); ?></button>
                    <input type="submit" name="siwo_save_order" class="btn btn-primary" value="<?php _e('Save Order', 'siteiran-wholesale'); ?>" />
                <?php endif; ?>
            </form>
        </div>
        <?php
    }

    public function search_products() {
        $search_term = sanitize_text_field($_GET['term']);
        $products = wc_get_products([
            's' => $search_term,
            'limit' => 10,
            'status' => 'publish',
        ]);

        $results = [];
        foreach ($products as $product) {
            $results[] = [
                'id' => $product->get_id(),
                'text' => $product->get_name(),
            ];
        }
        wp_send_json($results);
    }

    private function save_order($data, $order_id = 0) {
        $products = array_filter($data['products']);
        $quantities = array_filter($data['quantity']);
        if (!empty($products)) {
            $order_data = [
                'post_title' => $order_id ? get_the_title($order_id) : 'Wholesale Order #' . time(),
                'post_type' => 'siwo_order',
                'post_status' => 'publish',
                'meta_input' => [
                    'siwo_products' => array_combine($products, $quantities),
                    'siwo_status' => isset($data['order_status']) ? sanitize_text_field($data['order_status']) : get_option('siwo_order_status', 'pending'),
                    'siwo_customer' => get_current_user_id(),
                    'siwo_notes' => isset($data['order_notes']) ? sanitize_textarea_field($data['order_notes']) : '',
                ],
            ];
    
            $is_converted = $order_id && get_post_meta($order_id, 'siwo_converted_to_wc', true);
            if ($is_converted) {
                echo '<div class="error"><p>' . __('This order has been converted to WooCommerce and cannot be edited.', 'siteiran-wholesale') . '</p></div>';
                return;
            }
    
            if ($order_id) {
                $old_status = get_post_meta($order_id, 'siwo_status', true);
                $order_data['ID'] = $order_id;
                wp_update_post($order_data);
                if ($old_status !== $data['order_status']) {
                    $this->send_order_notification($order_id, 'status_updated');
                }
                wp_redirect(admin_url('admin.php?page=siwo-add-order&edit_order=' . $order_id));
                exit;
            } else {
                $new_order_id = wp_insert_post($order_data);
                $this->send_order_notification($new_order_id, 'created');
                wp_redirect(admin_url('admin.php?page=siwo-add-order&edit_order=' . $new_order_id));
                exit;
            }
        } else {
            echo '<div class="error"><p>' . __('No products selected!', 'siteiran-wholesale') . '</p></div>';
        }
    }

    public function settings_page() {
        $settings = new SIWO_Settings();
        $settings->render();
    }

    public function orders_page() {
        $orders = new SIWO_Orders();
        $orders->render();
    }

    public function get_order_data_for_print() {
        $order_id = intval($_POST['order_id']);
        $products = get_post_meta($order_id, 'siwo_products', true);
        $status = get_post_meta($order_id, 'siwo_status', true);
        $customer_id = get_post_meta($order_id, 'siwo_customer', true);
        $customer = get_userdata($customer_id)->display_name;
        $date = get_the_date('Y-m-d', $order_id);
        $logo_id = get_option('siwo_logo_id', 0);
        $logo_url = $logo_id ? wp_get_attachment_url($logo_id) : '';
        $discount_percent = get_option('siwo_discount_percent', 0);
        $notes = get_post_meta($order_id, 'siwo_notes', true);
        $invoice_number = 'INV-' . str_pad($order_id, 6, '0', STR_PAD_LEFT);
    
        $product_details = [];
        foreach ($products as $product_id => $quantity) {
            $product = wc_get_product($product_id);
            if ($product) {
                $price = $product->get_price();
                $product_details[$product->get_name()] = [
                    'quantity' => $quantity,
                    'price' => $price,
                    'price_formatted' => wc_price($price),
                    'total_formatted' => wc_price($price * $quantity),
                ];
            }
        }
    
        wp_send_json_success([
            'products' => $product_details,
            'status' => $status,
            'customer' => $customer,
            'date' => $date,
            'logo_url' => $logo_url,
            'discount' => $discount_percent,
            'currency' => get_woocommerce_currency(),
            'notes' => $notes,
            'invoice_number' => $invoice_number,
        ]);
    }


    public function get_product_price() {
        $product_id = intval($_POST['product_id']);
        $product = wc_get_product($product_id);
        if ($product) {
            wp_send_json_success(['price' => wc_price($product->get_price())]);
        } else {
            wp_send_json_error();
        }
    }




// تابع جدید برای تبدیل سفارش

public function handle_order_conversion() {
    if (isset($_GET['page']) && $_GET['page'] === 'siwo-orders' && isset($_GET['action']) && $_GET['action'] === 'convert' && isset($_GET['order_id'])) {
        $order_id = intval($_GET['order_id']);
        $products = get_post_meta($order_id, 'siwo_products', true);
        $customer_id = get_post_meta($order_id, 'siwo_customer', true);
        $discount_percent = get_option('siwo_discount_percent', 0);

        if ($products && $customer_id) {
            $order = wc_create_order(['customer_id' => $customer_id]);
            foreach ($products as $product_id => $quantity) {
                $product = wc_get_product($product_id);
                if ($product) {
                    $order->add_product($product, $quantity);
                }
            }
            if ($discount_percent > 0) {
                $subtotal = $order->get_subtotal();
                $discount_amount = $subtotal * ($discount_percent / 100);
                $order->set_discount_total($discount_amount);
                $order->set_total($subtotal - $discount_amount);
            }
            $siwo_status = get_post_meta($order_id, 'siwo_status', true);
            $wc_status = $siwo_status === 'completed' ? 'completed' : ($siwo_status === 'processing' ? 'processing' : 'pending');
            $order->set_status($wc_status);
            $order->save();

            update_post_meta($order_id, 'siwo_converted_to_wc', $order->get_id());
            $this->send_order_notification($order_id, 'converted'); // اعلان تبدیل

            wp_redirect(admin_url('admin.php?page=siwo-orders&converted=1'));
            exit;
        }
    }
}


//ایجاد تابع گزارشات 

public function enqueue_report_scripts($hook) {
    // مطمئن بشیم فقط توی صفحه گزارشات لود بشه
    if ($hook === 'wholesale-orders_page_siwo-reports') {
        wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js', [], '4.4.2', true);
        wp_enqueue_script('siwo-reports', SIWO_URL . 'assets/js/reports.js', ['chart-js'], '1.0.1', true);
        wp_localize_script('siwo-reports', 'siwo_report_data', [
            'sales_by_date' => $this->get_sales_by_date(), // داده‌ها رو جداگانه می‌فرستیم
            'currency' => get_woocommerce_currency(),
        ]);
    }
}

private function get_sales_by_date() {
    $filter_period = isset($_GET['filter_period']) ? sanitize_text_field($_GET['filter_period']) : 'all';
    $filter_date_start = isset($_GET['filter_date_start']) ? sanitize_text_field($_GET['filter_date_start']) : '';
    $filter_date_end = isset($_GET['filter_date_end']) ? sanitize_text_field($_GET['filter_date_end']) : '';

    $args = [
        'post_type' => 'siwo_order',
        'posts_per_page' => -1,
        'meta_query' => [['key' => 'siwo_converted_to_wc', 'compare' => 'NOT EXISTS']],
    ];

    if ($filter_period !== 'all' || $filter_date_start || $filter_date_end) {
        $args['date_query'] = [];
        if ($filter_period === 'today') {
            $args['date_query']['after'] = date('Y-m-d', strtotime('today'));
        } elseif ($filter_period === 'week') {
            $args['date_query']['after'] = date('Y-m-d', strtotime('-7 days'));
        } elseif ($filter_period === 'month') {
            $args['date_query']['after'] = date('Y-m-d', strtotime('-30 days'));
        } elseif ($filter_period === 'custom' && $filter_date_start) {
            $args['date_query']['after'] = $filter_date_start;
            if ($filter_date_end) {
                $args['date_query']['before'] = $filter_date_end;
            }
        }
        $args['date_query']['inclusive'] = true;
    }

    $orders = get_posts($args);
    $sales_by_date = [];

    foreach ($orders as $order) {
        $products = get_post_meta($order->ID, 'siwo_products', true);
        $order_date = get_the_date('Y-m-d', $order->ID);
        $sales_by_date[$order_date] = ($sales_by_date[$order_date] ?? 0);

        foreach ($products as $product_id => $quantity) {
            $product = wc_get_product($product_id);
            if ($product) {
                $sales_by_date[$order_date] += $product->get_price() * $quantity;
            }
        }
    }

    return $sales_by_date;
}

public function reports_page() {
    $filter_period = isset($_GET['filter_period']) ? sanitize_text_field($_GET['filter_period']) : 'all';
    $filter_date_start = isset($_GET['filter_date_start']) ? sanitize_text_field($_GET['filter_date_start']) : '';
    $filter_date_end = isset($_GET['filter_date_end']) ? sanitize_text_field($_GET['filter_date_end']) : '';

    $args = [
        'post_type' => 'siwo_order',
        'posts_per_page' => -1,
        'meta_query' => [['key' => 'siwo_converted_to_wc', 'compare' => 'NOT EXISTS']],
    ];

    if ($filter_period !== 'all' || $filter_date_start || $filter_date_end) {
        $args['date_query'] = [];
        if ($filter_period === 'today') {
            $args['date_query']['after'] = date('Y-m-d', strtotime('today'));
        } elseif ($filter_period === 'week') {
            $args['date_query']['after'] = date('Y-m-d', strtotime('-7 days'));
        } elseif ($filter_period === 'month') {
            $args['date_query']['after'] = date('Y-m-d', strtotime('-30 days'));
        } elseif ($filter_period === 'custom' && $filter_date_start) {
            $args['date_query']['after'] = $filter_date_start;
            if ($filter_date_end) {
                $args['date_query']['before'] = $filter_date_end;
            }
        }
        $args['date_query']['inclusive'] = true;
    }

    $orders = get_posts($args);
    $total_orders = count($orders);
    $total_sales = 0;
    $products_sold = [];
    $customers = [];

    foreach ($orders as $order) {
        $products = get_post_meta($order->ID, 'siwo_products', true);
        $customer_id = get_post_meta($order->ID, 'siwo_customer', true);

        $customers[$customer_id] = true;
        foreach ($products as $product_id => $quantity) {
            $product = wc_get_product($product_id);
            if ($product) {
                $total_sales += $product->get_price() * $quantity;
                $products_sold[$product->get_name()] = ($products_sold[$product->get_name()] ?? 0) + $quantity;
            }
        }
    }

    $discount_percent = get_option('siwo_discount_percent', 0);
    $discounted_sales = $total_sales * (1 - $discount_percent / 100);
    $total_customers = count($customers);
    $average_order = $total_orders > 0 ? $total_sales / $total_orders : 0;

    ?>
    <div class="wrap siwo-wrap">
        <h1 class="mb-4"><?php _e('Wholesale Reports', 'siteiran-wholesale'); ?></h1>

        <!-- Filter Form -->
        <form method="get" class="mb-4">
            <input type="hidden" name="page" value="siwo-reports">
            <div class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label"><?php _e('Period', 'siteiran-wholesale'); ?></label>
                    <select name="filter_period" class="form-select" id="filter-period">
                        <option value="all" <?php selected($filter_period, 'all'); ?>><?php _e('All Time', 'siteiran-wholesale'); ?></option>
                        <option value="today" <?php selected($filter_period, 'today'); ?>><?php _e('Today', 'siteiran-wholesale'); ?></option>
                        <option value="week" <?php selected($filter_period, 'week'); ?>><?php _e('Last 7 Days', 'siteiran-wholesale'); ?></option>
                        <option value="month" <?php selected($filter_period, 'month'); ?>><?php _e('Last 30 Days', 'siteiran-wholesale'); ?></option>
                        <option value="custom" <?php selected($filter_period, 'custom'); ?>><?php _e('Custom Range', 'siteiran-wholesale'); ?></option>
                    </select>
                </div>
                <div class="col-md-3 custom-date" <?php echo $filter_period !== 'custom' ? 'style="display:none;"' : ''; ?>>
                    <label class="form-label"><?php _e('Start Date', 'siteiran-wholesale'); ?></label>
                    <input type="date" name="filter_date_start" class="form-control" value="<?php echo esc_attr($filter_date_start); ?>">
                </div>
                <div class="col-md-3 custom-date" <?php echo $filter_period !== 'custom' ? 'style="display:none;"' : ''; ?>>
                    <label class="form-label"><?php _e('End Date', 'siteiran-wholesale'); ?></label>
                    <input type="date" name="filter_date_end" class="form-control" value="<?php echo esc_attr($filter_date_end); ?>">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100"><?php _e('Apply', 'siteiran-wholesale'); ?></button>
                </div>
            </div>
        </form>

        <!-- Stats -->
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title"><?php _e('Total Orders', 'siteiran-wholesale'); ?></h5>
                        <p class="card-text"><?php echo esc_html($total_orders); ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title"><?php _e('Total Sales', 'siteiran-wholesale'); ?></h5>
                        <p class="card-text"><?php echo wc_price($total_sales); ?></p>
                        <?php if ($discount_percent > 0) : ?>
                            <p class="card-text text-muted"><?php echo wc_price($discounted_sales); ?> (<?php echo esc_html($discount_percent); ?>% off)</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title"><?php _e('Total Customers', 'siteiran-wholesale'); ?></h5>
                        <p class="card-text"><?php echo esc_html($total_customers); ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title"><?php _e('Average Order Value', 'siteiran-wholesale'); ?></h5>
                        <p class="card-text"><?php echo wc_price($average_order); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Chart -->
        <div class="card">
            <div class="card-body">
                <h5 class="card-title"><?php _e('Sales Trend', 'siteiran-wholesale'); ?></h5>
                <canvas id="salesChart" height="100"></canvas>
            </div>
        </div>

        <!-- Products Sold -->
        <div class="card mt-4">
            <div class="card-body">
                <h5 class="card-title"><?php _e('Products Sold', 'siteiran-wholesale'); ?></h5>
                <ul class="list-unstyled">
                    <?php foreach ($products_sold as $name => $qty) : ?>
                        <li><?php echo esc_html($name) . ': ' . esc_html($qty); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>

        <script>
            document.getElementById('filter-period').addEventListener('change', function() {
                var customFields = document.querySelectorAll('.custom-date');
                if (this.value === 'custom') {
                    customFields.forEach(function(field) { field.style.display = 'block'; });
                } else {
                    customFields.forEach(function(field) { field.style.display = 'none'; });
                }
            });
        </script>
    </div>
    <?php
    }


    //اضافه کردن اعلانات
    private function send_order_notification($order_id, $event = 'created') {
        $products = get_post_meta($order_id, 'siwo_products', true);
        $status = get_post_meta($order_id, 'siwo_status', true);
        $customer_id = get_post_meta($order_id, 'siwo_customer', true);
        $notes = get_post_meta($order_id, 'siwo_notes', true);
        $customer = get_userdata($customer_id);
        $notify_customer = get_option('siwo_notify_customer', 1);
        $notify_admin = get_option('siwo_notify_admin', 1);
        $notification_method = get_option('siwo_notification_method', 'email');
        $email_header = get_option('siwo_email_header', 'Wholesale Order Notification');
        $logo_id = get_option('siwo_logo_id', 0);
        $logo_url = $logo_id ? wp_get_attachment_url($logo_id) : '';
        $sms_provider = get_option('siwo_sms_provider', 'sms_ir');
        $sms_params_config = get_option('siwo_sms_params', ['ORDER_ID', 'STATUS', 'PRODUCTS', 'NOTES', 'FULLNAME']);
    
        if (!$products || !$customer) return;
    
        $subject = $event === 'created' ? sprintf(__('New Wholesale Order #%s', 'siteiran-wholesale'), $order_id) :
                   ($event === 'status_updated' ? sprintf(__('Order #%s Status Updated', 'siteiran-wholesale'), $order_id) :
                   sprintf(__('Order #%s Converted to WooCommerce', 'siteiran-wholesale'), $order_id));
    
        $message = '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body style="font-family: Arial, sans-serif; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">';
        $message .= $logo_url ? '<img src="' . esc_url($logo_url) . '" alt="Logo" style="max-width: 150px; display: block; margin-bottom: 20px;">' : '';
        $message .= '<h2 style="color: #007bff;">' . esc_html($email_header) . '</h2>';
        $message .= '<p><strong>' . __('Order ID:', 'siteiran-wholesale') . '</strong> ' . $order_id . '</p>';
        $message .= '<p><strong>' . __('Status:', 'siteiran-wholesale') . '</strong> ' . ucfirst($status) . '</p>';
        $message .= '<p><strong>' . __('Customer:', 'siteiran-wholesale') . '</strong> ' . esc_html($customer->display_name) . '</p>';
        $message .= '<h3 style="margin-top: 20px;">' . __('Order Details:', 'siteiran-wholesale') . '</h3>';
        $message .= '<table style="width: 100%; border-collapse: collapse; margin: 10px 0;">';
        $message .= '<thead><tr style="background: #f5f5f5;"><th style="padding: 10px; border: 1px solid #ddd;">' . __('Product', 'siteiran-wholesale') . '</th><th style="padding: 10px; border: 1px solid #ddd;">' . __('Qty', 'siteiran-wholesale') . '</th><th style="padding: 10px; border: 1px solid #ddd;">' . __('Total', 'siteiran-wholesale') . '</th></tr></thead><tbody>';
        foreach ($products as $product_id => $quantity) {
            $product = wc_get_product($product_id);
            if ($product) {
                $message .= '<tr><td style="padding: 10px; border: 1px solid #ddd;">' . esc_html($product->get_name()) . '</td><td style="padding: 10px; border: 1px solid #ddd;">' . $quantity . '</td><td style="padding: 10px; border: 1px solid #ddd;">' . wc_price($product->get_price() * $quantity) . '</td></tr>';
            }
        }
        $message .= '</tbody></table>';
        if ($notes) {
            $message .= '<p style="margin-top: 20px;"><strong>' . __('Notes:', 'siteiran-wholesale') . '</strong> ' . esc_html($notes) . '</p>';
        }
        $message .= '<p style="color: #777; font-size: 0.9em; margin-top: 20px;">' . __('Sent from', 'siteiran-wholesale') . ' ' . get_bloginfo('name') . '</p>';
        $message .= '</body></html>';
    
        $headers = ['Content-Type: text/html; charset=UTF-8'];
    
        // Prepare SMS parameters dynamically
        $sms_params = [];
        foreach ($sms_params_config as $param) {
            switch ($param) {
                case 'ORDER_ID':
                    $sms_params[] = ['name' => 'ORDER_ID', 'value' => $order_id];
                    break;
                case 'STATUS':
                    $sms_params[] = ['name' => 'STATUS', 'value' => ucfirst($status)];
                    break;
                case 'PRODUCTS':
                    $product_list = '';
                    foreach ($products as $product_id => $quantity) {
                        $product = wc_get_product($product_id);
                        if ($product) {
                            $product_list .= $product->get_name() . ": $quantity, ";
                        }
                    }
                    $sms_params[] = ['name' => 'PRODUCTS', 'value' => rtrim($product_list, ', ')];
                    break;
                case 'NOTES':
                    if ($notes) {
                        $sms_params[] = ['name' => 'NOTES', 'value' => $notes];
                    }
                    break;
                case 'FULLNAME':
                    $sms_params[] = ['name' => 'FULLNAME', 'value' => $customer->first_name . ' ' . $customer->last_name];
                    break;
            }
        }
    
        if ($notification_method === 'email' || $notification_method === 'both') {
            if ($notify_customer && $customer->user_email) {
                wp_mail($customer->user_email, $subject, $message, $headers);
            }
            if ($notify_admin) {
                $admin_email = get_option('admin_email');
                wp_mail($admin_email, $subject, $message, $headers);
            }
        }
    
        if ($notification_method === 'sms' || $notification_method === 'both') {
            $phone = $this->get_customer_phone($customer_id);
            if ($notify_customer && $phone) {
                $this->send_sms_notification($phone, $sms_params, $sms_provider);
            }
            if ($notify_admin) {
                $admin_phone = get_option('admin_phone', '');
                if ($admin_phone) {
                    $this->send_sms_notification($admin_phone, $sms_params, $sms_provider);
                }
            }
        }
    }
    
    private function get_customer_phone($customer_id) {
        $phone = get_user_meta($customer_id, 'billing_phone', true);
        return $phone ?: '';
    }
    
    private function send_sms_notification($phone, $params, $provider) {
        switch ($provider) {
            case 'sms_ir':
                $api_key = get_option('siwo_sms_ir_api_key', '');
                $template_id = get_option('siwo_sms_ir_template_id', '');
                if (!$api_key || !$template_id || !$phone) {
                    error_log("SMS.ir: Missing API key, template ID, or phone number.");
                    return;
                }
    
                $curl = curl_init();
                curl_setopt_array($curl, [
                    CURLOPT_URL => 'https://api.sms.ir/v1/send/verify',
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => '',
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 0,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => 'POST',
                    CURLOPT_POSTFIELDS => json_encode([
                        'mobile' => $phone,
                        'templateId' => $template_id,
                        'parameters' => $params,
                    ]),
                    CURLOPT_HTTPHEADER => [
                        'Content-Type: application/json',
                        'Accept: text/plain',
                        'x-api-key: ' . $api_key,
                    ],
                ]);
                $response = curl_exec($curl);
                $error = curl_error($curl);
                curl_close($curl);
    
                if ($error) {
                    error_log("SMS.ir Error: $error");
                } else {
                    error_log("SMS.ir Response: $response");
                }
                break;
    
            default:
                error_log("SMS provider '$provider' not supported.");
                break;
        }
    }
    

    
}