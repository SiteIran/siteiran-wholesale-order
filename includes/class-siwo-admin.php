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
        add_action('admin_init', [$this, 'handle_order_conversion']); // برای تبدیل سفارش
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
            if ($order_id) {
                $order_data['ID'] = $order_id;
                wp_update_post($order_data);
                wp_redirect(admin_url('admin.php?page=siwo-add-order&edit_order=' . $order_id));
                exit;
            } else {
                $new_order_id = wp_insert_post($order_data);
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
                // Create new WooCommerce order
                $order = wc_create_order([
                    'customer_id' => $customer_id,
                ]);
    
                // Add products to order
                foreach ($products as $product_id => $quantity) {
                    $product = wc_get_product($product_id);
                    if ($product) {
                        $order->add_product($product, $quantity);
                    }
                }
    
                // Apply discount if set
                if ($discount_percent > 0) {
                    $subtotal = $order->get_subtotal();
                    $discount_amount = $subtotal * ($discount_percent / 100);
                    $order->set_discount_total($discount_amount);
                    $order->set_total($subtotal - $discount_amount);
                }
    
                // Set status based on SIWO status
                $siwo_status = get_post_meta($order_id, 'siwo_status', true);
                $wc_status = $siwo_status === 'completed' ? 'completed' : ($siwo_status === 'processing' ? 'processing' : 'pending');
                $order->set_status($wc_status);
                $order->save();
    
                // Mark SIWO order as converted
                update_post_meta($order_id, 'siwo_converted_to_wc', $order->get_id());
    
                // Redirect with success message
                wp_redirect(admin_url('admin.php?page=siwo-orders&converted=1'));
                exit;
            }
        }
    }


//ایجاد تابع گزارشات 
    public function reports_page() {
        $args = [
            'post_type' => 'siwo_order',
            'posts_per_page' => -1,
            'meta_query' => [['key' => 'siwo_converted_to_wc', 'compare' => 'NOT EXISTS']], // فقط سفارشات تبدیل‌نشده
        ];
        $orders = get_posts($args);
        $total_orders = count($orders);
        $total_sales = 0;
        $products_sold = [];
    
        foreach ($orders as $order) {
            $products = get_post_meta($order->ID, 'siwo_products', true);
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
    
        ?>
        <div class="wrap siwo-wrap">
            <h1 class="mb-4"><?php _e('Wholesale Reports', 'siteiran-wholesale'); ?></h1>
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title"><?php _e('Total Orders', 'siteiran-wholesale'); ?></h5>
                            <p class="card-text"><?php echo esc_html($total_orders); ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
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
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title"><?php _e('Products Sold', 'siteiran-wholesale'); ?></h5>
                            <ul class="list-unstyled">
                                <?php foreach ($products_sold as $name => $qty) : ?>
                                    <li><?php echo esc_html($name) . ': ' . esc_html($qty); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
}