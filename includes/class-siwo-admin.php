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
    
        if (isset($_POST['siwo_save_order'])) {
            $this->save_order($_POST, $order_id);
        }
    
        $title = $is_edit ? __('Edit Wholesale Order', 'siteiran-wholesale') : __('Add New Wholesale Order', 'siteiran-wholesale');
        ?>
        <div class="wrap siwo-wrap">
            <h1 class="mb-4"><?php echo esc_html($title); ?></h1>
            <form method="post" class="siwo-order-form">
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
                                                <select class="siwo-product-search form-select" name="products[]">
                                                    <option value="<?php echo esc_attr($product_id); ?>" selected><?php echo esc_html($product->get_name()); ?></option>
                                                </select>
                                            </td>
                                            <td><input type="number" class="form-control" name="quantity[]" min="1" value="<?php echo esc_attr($quantity); ?>" /></td>
                                            <td><?php echo wc_price($product->get_price()); ?></td>
                                            <td><button type="button" class="btn btn-danger siwo-remove-row"><?php _e('Remove', 'siteiran-wholesale'); ?></button></td>
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
                        <select name="order_status" class="form-select w-25">
                            <option value="pending" <?php selected($order_status, 'pending'); ?>><?php _e('Pending', 'siteiran-wholesale'); ?></option>
                            <option value="processing" <?php selected($order_status, 'processing'); ?>><?php _e('Processing', 'siteiran-wholesale'); ?></option>
                            <option value="completed" <?php selected($order_status, 'completed'); ?>><?php _e('Completed', 'siteiran-wholesale'); ?></option>
                        </select>
                    </div>
                <?php endif; ?>
                <button type="button" id="siwo-add-row" class="btn btn-secondary"><?php _e('Add Product', 'siteiran-wholesale'); ?></button>
                <input type="submit" name="siwo_save_order" class="btn btn-primary" value="<?php _e('Save Order', 'siteiran-wholesale'); ?>" />
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
                ],
            ];
            if ($order_id) {
                $order_data['ID'] = $order_id;
                wp_update_post($order_data);
                // Redirect to edit page after update
                wp_redirect(admin_url('admin.php?page=siwo-add-order&edit_order=' . $order_id));
                exit;
            } else {
                $new_order_id = wp_insert_post($order_data);
                // Redirect to edit page after creating new order
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

        $product_names = [];
        foreach ($products as $product_id => $quantity) {
            $product = wc_get_product($product_id);
            $product_names[$product->get_name()] = $quantity;
        }

        wp_send_json_success([
            'products' => $product_names,
            'status' => $status,
            'customer' => $customer,
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
    
}