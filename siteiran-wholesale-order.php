<?php
/*
 * Plugin Name: SiteIran Wholesale Order
 * Plugin URI: https://siteiran.com
 * Description: A WooCommerce wholesale order management plugin for SiteIran.
 * Version: 1.0.0
 * Author: SiteIran Team
 * Author URI: https://siteiran.com
 * License: GPL-2.0+
 * Text Domain: siteiran-wholesale
 */

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
        'rewrite' => false, // غیرفعال کردن بازنویسی URL برای جلوگیری از خطا
        'supports' => ['title'], // فقط عنوان رو نگه می‌داریم
    ]);
}
add_action('init', 'siwo_register_post_type'); // ثبت پست تایپ در 'init'

// Enqueue styles and scripts
function siwo_enqueue_assets() {
    wp_enqueue_style('siwo-styles', SIWO_URL . 'assets/css/style.css', [], '1.0.0');
    wp_enqueue_style('select2', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css');
    wp_enqueue_script('select2', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js', ['jquery'], '4.0.13', true);
    wp_enqueue_script('siwo-scripts', SIWO_URL . 'assets/js/scripts.js', ['jquery', 'select2'], '1.0.0', true);
    wp_localize_script('siwo-scripts', 'siwo_ajax', ['ajax_url' => admin_url('admin-ajax.php')]);
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