<?php
/*
 * Plugin Name: SiteIran Wholesale Order
 * Plugin URI: https://siteiran.com
 * Description: A WooCommerce plugin for managing wholesale orders, with features like order filtering, editing, canceling, printing, and converting to WooCommerce. It supports email/SMS notifications, customizable SMS settings, and a user-friendly interface with pagination and AJAX order details.
 * Version: 1.1.0
 * Author: Aryanpour
 * Author URI: https://siteiran.com
 * License: GPL-2.0+
 * Text Domain: siteiran-wholesale
 */

// بارگذاری فایل‌های ترجمه
function siteiran_wholesale_load_textdomain() {
    load_plugin_textdomain('siteiran-wholesale', false, dirname(plugin_basename(__FILE__)) . '/languages/');
}
add_action('plugins_loaded', 'siteiran_wholesale_load_textdomain');

if (!defined('ABSPATH')) {
    exit;
}

define('SIWO_PATH', plugin_dir_path(__FILE__));
define('SIWO_URL', plugin_dir_url(__FILE__));

require_once SIWO_PATH . 'includes/class-siwo-admin.php';
require_once SIWO_PATH . 'includes/class-siwo-settings.php';
require_once SIWO_PATH . 'includes/class-siwo-orders.php';

// Initialize plugin on 'init' hook
function siwo_init() {
    $admin = new SIWO_Admin();
    $admin->init();
}
add_action('init', 'siwo_init');

// Register custom post type for orders
function siwo_register_post_type() {
    register_post_type('siwo_order', [
        'labels' => [
            'name' => __('Wholesale Orders', 'siteiran-wholesale'),
            'singular_name' => __('Order', 'siteiran-wholesale'),
        ],
        'public' => false,
        'show_ui' => false,
        'rewrite' => false,
        'supports' => ['title', 'custom-fields'],
    ]);
}
add_action('init', 'siwo_register_post_type');

// Admin assets
function siwo_enqueue_assets() {
    wp_enqueue_style('bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css', [], '5.3.3');
    wp_enqueue_style('select2', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css');
    wp_enqueue_style('bootstrap-icons', 'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css');
    wp_enqueue_style('siwo-styles', SIWO_URL . 'assets/css/style.css', ['bootstrap', 'bootstrap-icons'], '1.1.0');

    wp_enqueue_script('popper', 'https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js', [], '2.11.8', true);
    wp_enqueue_script('bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js', ['popper'], '5.3.3', true);
    wp_enqueue_script('select2', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js', ['jquery'], '4.0.13', true);
    wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js', [], '3.9.1', true);
    wp_enqueue_script('siwo-scripts', SIWO_URL . 'assets/js/scripts.js', ['jquery', 'select2', 'bootstrap', 'chart-js'], '1.1.0', true);

    $translations = [
        'select_customer' => __('Select a customer', 'siteiran-wholesale'),
        'search_product' => __('Search for a product', 'siteiran-wholesale'),
        'order_id' => __('Order ID:', 'siteiran-wholesale'),
        'customer' => __('Customer:', 'siteiran-wholesale'),
        'status' => __('Status:', 'siteiran-wholesale'),
        'date' => __('Date:', 'siteiran-wholesale'),
        'products' => __('Products:', 'siteiran-wholesale'),
        'product' => __('Product', 'siteiran-wholesale'),
        'quantity' => __('Quantity', 'siteiran-wholesale'),
        'price' => __('Price', 'siteiran-wholesale'),
        'total' => __('Total', 'siteiran-wholesale'),
        'notes' => __('Notes:', 'siteiran-wholesale'),
        'subtotal' => __('Subtotal:', 'siteiran-wholesale'),
        'discount' => __('Discount:', 'siteiran-wholesale'),
        'failed_load_order' => __('Failed to load order details.', 'siteiran-wholesale'),
        'error_fetching_order' => __('An error occurred while fetching order details.', 'siteiran-wholesale'),
        'order_invoice' => __('Order Invoice', 'siteiran-wholesale'),
        'failed_load_order_print' => __('Failed to load order details for printing.', 'siteiran-wholesale'),
        'error_fetching_order_print' => __('An error occurred while fetching order details for printing.', 'siteiran-wholesale'),
        'upload_logo' => __('Upload Logo', 'siteiran-wholesale'),
        'select_logo' => __('Select Logo', 'siteiran-wholesale'),
        'logo_preview' => __('Logo Preview', 'siteiran-wholesale'),
        'remove_logo' => __('Remove Logo', 'siteiran-wholesale'),
        'param_name' => __('Parameter Name', 'siteiran-wholesale'),
        'param_value' => __('Parameter Value', 'siteiran-wholesale'),
    ];

    wp_localize_script('siwo-scripts', 'siwo_ajax', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('siwo_nonce')
    ]);

    wp_localize_script('siwo-scripts', 'siwo_translations', $translations);
    wp_localize_script('siwo-scripts', 'siwo_settings', [
        'logo' => get_option('siwo_logo', '')
    ]);
}
add_action('admin_enqueue_scripts', 'siwo_enqueue_assets');

// Frontend assets
// توی siteiran-wholesale-order.php
function siwo_enqueue_frontend_assets() {
    wp_enqueue_style('bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css');
    wp_enqueue_style('bootstrap-icons', 'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css');
    wp_enqueue_style('siwo-style', SIWO_URL . 'assets/css/style.css');
    wp_enqueue_script('bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js', ['jquery'], '5.3.3', true);
    wp_enqueue_script('siwo-scripts', SIWO_URL . 'assets/js/scripts.js', ['jquery', 'bootstrap'], '1.1.0', true);

    $translations = [
        'select_customer' => __('Select a customer', 'siteiran-wholesale'),
        'search_product' => __('Search for a product', 'siteiran-wholesale'),
        'order_id' => __('Order ID:', 'siteiran-wholesale'),
        'customer' => __('Customer:', 'siteiran-wholesale'),
        'status' => __('Status:', 'siteiran-wholesale'),
        'date' => __('Date:', 'siteiran-wholesale'),
        'products' => __('Products:', 'siteiran-wholesale'),
        'product' => __('Product', 'siteiran-wholesale'),
        'quantity' => __('Quantity', 'siteiran-wholesale'),
        'price' => __('Price', 'siteiran-wholesale'),
        'total' => __('Total', 'siteiran-wholesale'),
        'notes' => __('Notes:', 'siteiran-wholesale'),
        'subtotal' => __('Subtotal:', 'siteiran-wholesale'),
        'discount' => __('Discount:', 'siteiran-wholesale'),
        'failed_load_order' => __('Failed to load order details.', 'siteiran-wholesale'),
        'error_fetching_order' => __('An error occurred while fetching order details.', 'siteiran-wholesale'),
        'order_invoice' => __('Order Invoice', 'siteiran-wholesale'),
        'failed_load_order_print' => __('Failed to load order details for printing.', 'siteiran-wholesale'),
        'error_fetching_order_print' => __('An error occurred while fetching order details for printing.', 'siteiran-wholesale'),
        'upload_logo' => __('Upload Logo', 'siteiran-wholesale'),
        'select_logo' => __('Select Logo', 'siteiran-wholesale'),
        'logo_preview' => __('Logo Preview', 'siteiran-wholesale'),
        'remove_logo' => __('Remove Logo', 'siteiran-wholesale'),
        'param_name' => __('Parameter Name', 'siteiran-wholesale'),
        'param_value' => __('Parameter Value', 'siteiran-wholesale'),
    ];

    wp_localize_script('siwo-scripts', 'siwo_ajax', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('siwo_nonce')
    ]);

    wp_localize_script('siwo-scripts', 'siwo_translations', $translations);
}

add_action('wp_enqueue_scripts', 'siwo_enqueue_frontend_assets');

// Check if WooCommerce is active
function siwo_check_woocommerce() {
    if (!class_exists('WooCommerce')) {
        add_action('admin_notices', function() {
            echo '<div class="error"><p>' . __('SiteIran Wholesale Order requires WooCommerce to be installed and active.', 'siteiran-wholesale') . '</p></div>';
        });
        return false;
    }
    return true;
}
add_action('plugins_loaded', 'siwo_check_woocommerce');

// Create order items table on activation
register_activation_hook(__FILE__, 'siwo_create_order_items_table');
function siwo_create_order_items_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'siwo_order_items';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        item_id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        order_id BIGINT(20) UNSIGNED NOT NULL,
        product_id BIGINT(20) UNSIGNED NOT NULL,
        quantity INT NOT NULL,
        price DECIMAL(10, 2) NOT NULL,
        PRIMARY KEY (item_id),
        INDEX idx_order_id (order_id),
        INDEX idx_product_id (product_id)
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);
}

// Migrate old order items
function siwo_migrate_order_items() {
    global $wpdb;
    $orders = get_posts([
        'post_type' => 'siwo_order',
        'posts_per_page' => -1,
        'meta_key' => 'siwo_products',
    ]);

    foreach ($orders as $order) {
        $order_id = $order->ID;
        $products = get_post_meta($order_id, 'siwo_products', true);
        if ($products && is_array($products)) {
            foreach ($products as $product_id => $quantity) {
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
            delete_post_meta($order_id, 'siwo_products');
        }
    }
}

// Add menu item to My Account
add_filter('woocommerce_account_menu_items', 'siwo_add_my_account_menu');
function siwo_add_my_account_menu($items) {
    $items['wholesale-orders'] = __('سفارشات عمده‌فروشی', 'siteiran-wholesale');
    return $items;
}

// Define content for wholesale orders page
add_action('woocommerce_account_wholesale-orders_endpoint', 'siwo_wholesale_orders_content');
// توی siteiran-wholesale-order.php
function siwo_wholesale_orders_content() {
    ?>
    <h2><?php _e('ثبت سفارش جدید', 'siteiran-wholesale'); ?></h2>
    <form id="siwo-order-form" method="post">
        <div class="mb-3">
            <label for="siwo_status"><?php _e('وضعیت', 'siteiran-wholesale'); ?></label>
            <select name="siwo_status" id="siwo_status" class="form-select">
                <option value="pending"><?php _e('در انتظار', 'siteiran-wholesale'); ?></option>
                <option value="processing"><?php _e('در حال پردازش', 'siteiran-wholesale'); ?></option>
                <option value="completed"><?php _e('تکمیل شده', 'siteiran-wholesale'); ?></option>
            </select>
        </div>
        <div class="mb-3">
            <label for="siwo_notes"><?php _e('یادداشت', 'siteiran-wholesale'); ?></label>
            <textarea name="siwo_notes" id="siwo_notes" class="form-control" rows="3"></textarea>
        </div>
        <div class="mb-3">
            <label for="siwo_discount"><?php _e('تخفیف', 'siteiran-wholesale'); ?></label>
            <input type="number" name="siwo_discount" id="siwo_discount" class="form-control" step="0.01" min="0" value="0" />
        </div>
        <div id="siwo-products">
            <!-- محصولات به صورت داینامیک با جاوااسکریپت اضافه می‌شن -->
        </div>
        <button type="button" id="siwo-add-product" class="btn btn-outline-primary"><?php _e('اضافه کردن محصول', 'siteiran-wholesale'); ?></button>
        <button type="submit" class="btn btn-primary"><?php _e('ثبت سفارش', 'siteiran-wholesale'); ?></button>
    </form>
    <hr>
    <h2><?php _e('لیست سفارشات', 'siteiran-wholesale'); ?></h2>
    <?php siwo_display_user_orders(); ?>

    <!-- مودال برای نمایش جزئیات سفارش -->
    <div class="modal fade" id="siwoOrderModal" tabindex="-1" aria-labelledby="siwoOrderModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="siwoOrderModalLabel"><?php _e('جزئیات سفارش', 'siteiran-wholesale'); ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="siwo-order-details"></div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php _e('بستن', 'siteiran-wholesale'); ?></button>
                </div>
            </div>
        </div>
    </div>
    <?php
}

// Register endpoint
add_action('init', 'siwo_register_endpoints');
function siwo_register_endpoints() {
    add_rewrite_endpoint('wholesale-orders', EP_ROOT | EP_PAGES);
}

// Display user orders
function siwo_display_user_orders() {
    global $wpdb;
    $user_id = get_current_user_id();

    $orders = get_posts([
        'post_type' => 'siwo_order',
        'post_status' => 'publish',
        'meta_query' => [
            [
                'key' => 'siwo_customer',
                'value' => $user_id,
                'compare' => '='
            ]
        ],
        'numberposts' => -1,
        'orderby' => 'date',
        'order' => 'DESC'
    ]);

    if ($orders) {
        echo '<table class="siwo-orders-table">';
        echo '<thead><tr><th>شناسه سفارش</th><th>تاریخ</th><th>وضعیت</th><th>جزئیات</th></tr></thead>';
        echo '<tbody>';
        foreach ($orders as $order) {
            $status = get_post_meta($order->ID, 'siwo_status', true);
            echo '<tr>';
            echo '<td>' . esc_html($order->ID) . '</td>';
            echo '<td>' . esc_html(get_the_date('Y-m-d H:i:s', $order->ID)) . '</td>';
            echo '<td>' . esc_html($status) . '</td>';
            echo '<td><button class="siwo-order-details" data-order-id="' . esc_attr($order->ID) . '">مشاهده جزئیات</button></td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
    } else {
        echo '<p>هیچ سفارشی یافت نشد.</p>';
    }
}

// AJAX handler for saving order
add_action('wp_ajax_siwo_save_order', 'siwo_ajax_save_order');
function siwo_ajax_save_order() {
    check_ajax_referer('siwo_nonce', 'nonce');

    parse_str($_POST['form_data'], $order_data);
    $order_id = SIWO_Orders::save_ajax_order($order_data);

    if ($order_id) {
        wp_send_json_success(['message' => __('سفارش ثبت شد', 'siteiran-wholesale'), 'order_id' => $order_id]);
    } else {
        wp_send_json_error(['message' => __('خطا در ثبت سفارش', 'siteiran-wholesale')]);
    }
}

// AJAX handler for order details
add_action('wp_ajax_siwo_get_order_details', 'siwo_ajax_get_order_details');
function siwo_ajax_get_order_details() {
    check_ajax_referer('siwo_nonce', 'nonce');

    $order_id = intval($_POST['order_id']);
    global $wpdb;

    $order = get_post($order_id);
    if (!$order || $order->post_type !== 'siwo_order') {
        wp_send_json_error(['message' => __('سفارش یافت نشد', 'siteiran-wholesale')]);
    }

    $items = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}siwo_order_items WHERE order_id = %d",
        $order_id
    ), ARRAY_A);

    $details = [
        'status' => get_post_meta($order_id, 'siwo_status', true),
        'notes' => get_post_meta($order_id, 'siwo_notes', true),
        'discount' => get_post_meta($order_id, 'siwo_discount', true),
        'items' => []
    ];

    foreach ($items as $item) {
        $product = wc_get_product($item['product_id']);
        $details['items'][] = [
            'product_name' => $product ? $product->get_name() : __('محصول حذف شده', 'siteiran-wholesale'),
            'quantity' => $item['quantity'],
            'price' => $item['price']
        ];
    }

    wp_send_json_success($details);
}