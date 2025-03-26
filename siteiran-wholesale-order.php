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


// Initialize plugin on 'init' hook instead of 'plugins_loaded'
function siwo_init() {
    $admin = new SIWO_Admin();
    $admin->init();
}
add_action('init', 'siwo_init'); // تغییر از 'plugins_loaded' به 'init'

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
        'supports' => ['title', 'custom-fields'], // 'custom-fields' برای متادیتا
    ]);
}
add_action('init', 'siwo_register_post_type'); // ثبت پست تایپ در 'init'

function siwo_enqueue_assets() {
    // لود استایل‌ها
    wp_enqueue_style('bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css', [], '5.3.3');
    wp_enqueue_style('select2', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css');
    wp_enqueue_style('bootstrap-icons', 'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css');
    wp_enqueue_style('siwo-styles', SIWO_URL . 'assets/css/style.css', ['bootstrap', 'bootstrap-icons'], '1.1.0');

    // لود اسکریپت‌ها
    wp_enqueue_script('popper', 'https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js', [], '2.11.8', true);
    wp_enqueue_script('bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js', ['popper'], '5.3.3', true);
    wp_enqueue_script('select2', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js', ['jquery'], '4.0.13', true);
    wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js', [], '3.9.1', true);
    wp_enqueue_script('siwo-scripts', SIWO_URL . 'assets/js/scripts.js', ['jquery', 'select2', 'bootstrap', 'chart-js'], '1.1.0', true);

    // پاس دادن داده‌های AJAX و ترجمه‌ها به جاوااسکریپت
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
        'ajax_url' => admin_url('admin-ajax.php')
    ]);

    wp_localize_script('siwo-scripts', 'siwo_translations', $translations);

    // پاس دادن لوگو به جاوااسکریپت
    wp_localize_script('siwo-scripts', 'siwo_settings', [
        'logo' => get_option('siwo_logo', '')
    ]);
}
add_action('admin_enqueue_scripts', 'siwo_enqueue_assets');

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

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

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
            // بعد از انتقال، متا رو پاک می‌کنیم
            delete_post_meta($order_id, 'siwo_products');
        }
    }
}

// موقع فعال‌سازی پلاگین اجرا بشه
register_activation_hook(__FILE__, 'siwo_migrate_order_items');