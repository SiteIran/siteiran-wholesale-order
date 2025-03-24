<?php
if (!defined('ABSPATH')) {
    exit;
}

class SIWO_Orders {
    public function render() {
        global $wpdb;

        // نمایش پیام موفقیت - تبدیل به ووکامرس
        if (isset($_GET['converted']) && $_GET['converted'] == 1) {
            echo '<div class="updated"><p>' . __('Order successfully converted to WooCommerce.', 'siteiran-wholesale') . '</p></div>';
        }

        // حذف سفارش
        if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['order_id'])) {
            $order_id = intval($_GET['order_id']);
            wp_delete_post($order_id, true);
            // پاک کردن آیتم‌های مربوط به سفارش از جدول siwo_order_items
            $wpdb->delete($wpdb->prefix . 'siwo_order_items', ['order_id' => $order_id], ['%d']);
            echo '<div class="updated"><p>' . __('Order deleted.', 'siteiran-wholesale') . '</p></div>';
        }

        // پارامترهای فیلتر
        $filter_status = isset($_GET['filter_status']) ? sanitize_text_field($_GET['filter_status']) : '';
        $filter_customer = isset($_GET['filter_customer']) ? sanitize_text_field($_GET['filter_customer']) : '';
        $filter_date_start = isset($_GET['filter_date_start']) ? sanitize_text_field($_GET['filter_date_start']) : '';
        $filter_date_end = isset($_GET['filter_date_end']) ? sanitize_text_field($_GET['filter_date_end']) : '';
        $filter_search = isset($_GET['filter_search']) ? sanitize_text_field($_GET['filter_search']) : '';

        // آرگومان‌های پایه برای کوئری
        $args = [
            'post_type' => 'siwo_order',
            'posts_per_page' => -1,
            'meta_query' => [],
        ];

        if ($filter_status) {
            $args['meta_query'][] = [
                'key' => 'siwo_status',
                'value' => $filter_status,
                'compare' => '=',
            ];
        }

        if ($filter_customer) {
            $args['meta_query'][] = [
                'key' => 'siwo_customer',
                'value' => $filter_customer,
                'compare' => '=',
            ];
        }

        if ($filter_date_start || $filter_date_end) {
            $args['date_query'] = [];
            if ($filter_date_start) {
                $args['date_query']['after'] = $filter_date_start;
            }
            if ($filter_date_end) {
                $args['date_query']['before'] = $filter_date_end;
            }
            $args['date_query']['inclusive'] = true;
        }

        // گرفتن سفارش‌ها بر اساس فیلترهای اولیه
        $orders = get_posts($args);

        // فیلتر جست‌وجوی سفارشی برای عنوان و محصولات
        if ($filter_search) {
            $filtered_orders = [];
            foreach ($orders as $order) {
                $title_match = stripos($order->post_title, $filter_search) !== false;
                $items = $wpdb->get_results($wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}siwo_order_items WHERE order_id = %d",
                    $order->ID
                ), ARRAY_A);
                $product_match = false;

                if ($items) {
                    foreach ($items as $item) {
                        $product = wc_get_product($item['product_id']);
                        if ($product && stripos($product->get_name(), $filter_search) !== false) {
                            $product_match = true;
                            break;
                        }
                    }
                }

                if ($title_match || $product_match) {
                    $filtered_orders[] = $order;
                }
            }
            $orders = $filtered_orders; // جایگزینی سفارش‌ها با نتایج فیلترشده
        }

        $users = get_users(['fields' => ['ID', 'display_name']]);
        ?>
        <div class="wrap siwo-wrap">
            <h1 class="mb-4"><?php _e('Wholesale Orders', 'siteiran-wholesale'); ?></h1>

            <!-- فرم فیلتر -->
            <form method="get" class="mb-4">
                <input type="hidden" name="page" value="siwo-orders">
                <div class="row g-3 align-items-end">
                    <div class="col-md-2">
                        <label class="form-label"><?php _e('Status', 'siteiran-wholesale'); ?></label>
                        <select name="filter_status" class="form-select">
                            <option value=""><?php _e('All Statuses', 'siteiran-wholesale'); ?></option>
                            <option value="pending" <?php selected($filter_status, 'pending'); ?>><?php _e('Pending', 'siteiran-wholesale'); ?></option>
                            <option value="processing" <?php selected($filter_status, 'processing'); ?>><?php _e('Processing', 'siteiran-wholesale'); ?></option>
                            <option value="completed" <?php selected($filter_status, 'completed'); ?>><?php _e('Completed', 'siteiran-wholesale'); ?></option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label"><?php _e('Customer', 'siteiran-wholesale'); ?></label>
                        <select name="filter_customer" class="form-select">
                            <option value=""><?php _e('All Customers', 'siteiran-wholesale'); ?></option>
                            <?php foreach ($users as $user) : ?>
                                <option value="<?php echo esc_attr($user->ID); ?>" <?php selected($filter_customer, $user->ID); ?>><?php echo esc_html($user->display_name); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label"><?php _e('Date Start', 'siteiran-wholesale'); ?></label>
                        <input type="date" name="filter_date_start" class="form-control" value="<?php echo esc_attr($filter_date_start); ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label"><?php _e('Date End', 'siteiran-wholesale'); ?></label>
                        <input type="date" name="filter_date_end" class="form-control" value="<?php echo esc_attr($filter_date_end); ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label"><?php _e('Search', 'siteiran-wholesale'); ?></label>
                        <input type="text" name="filter_search" class="form-control" value="<?php echo esc_attr($filter_search); ?>" placeholder="<?php _e('Order or Product', 'siteiran-wholesale'); ?>">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100"><?php _e('Apply Filter', 'siteiran-wholesale'); ?></button>
                    </div>
                </div>
            </form>

            <!-- جدول سفارش‌ها -->
            <div class="table-responsive">
                <table class="table table-striped table-bordered">
                    <thead class="table-light">
                        <tr>
                            <th><?php _e('Order ID', 'siteiran-wholesale'); ?></th>
                            <th><?php _e('Customer', 'siteiran-wholesale'); ?></th>
                            <th><?php _e('Products', 'siteiran-wholesale'); ?></th>
                            <th><?php _e('Date', 'siteiran-wholesale'); ?></th>
                            <th><?php _e('Status', 'siteiran-wholesale'); ?></th>
                            <th><?php _e('Actions', 'siteiran-wholesale'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($orders) : ?>
                            <?php foreach ($orders as $order) : ?>
                                <?php
                                // گرفتن محصولات سفارش
                                $items = $wpdb->get_results($wpdb->prepare(
                                    "SELECT * FROM {$wpdb->prefix}siwo_order_items WHERE order_id = %d",
                                    $order->ID
                                ), ARRAY_A);
                                $product_count = count($items);
                                $product_list = [];
                                if ($items) {
                                    foreach ($items as $item) {
                                        $product = wc_get_product($item['product_id']);
                                        if ($product) {
                                            $product_list[] = esc_html($product->get_name()) . ': ' . esc_html($item['quantity']);
                                        }
                                    }
                                }
                                ?>
                                <tr>
                                    <td><?php echo esc_html($order->ID); ?></td>
                                    <td><?php echo esc_html(get_userdata(get_post_meta($order->ID, 'siwo_customer', true))->display_name); ?></td>
                                    <td>
                                        <?php if ($product_count > 0) : ?>
                                            <?php echo esc_html($product_count) . ' ' . __('product(s)', 'siteiran-wholesale'); ?>
                                            <a href="#" class="btn btn-link btn-sm view-products" data-bs-toggle="modal" data-bs-target="#productsModal-<?php echo esc_attr($order->ID); ?>"><?php _e('View', 'siteiran-wholesale'); ?></a>
                                        <?php else : ?>
                                            <?php _e('No products', 'siteiran-wholesale'); ?>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo esc_html(get_the_date('', $order->ID)); ?></td>
                                    <td><?php echo esc_html(get_post_meta($order->ID, 'siwo_status', true)); ?></td>
                                    <td>
                                        <?php if (!get_post_meta($order->ID, 'siwo_converted_to_wc', true)) : ?>
                                            <a href="?page=siwo-add-order&edit_order=<?php echo $order->ID; ?>" class="btn btn-sm btn-primary"><?php _e('Edit', 'siteiran-wholesale'); ?></a>
                                            <a href="?page=siwo-orders&action=delete&order_id=<?php echo $order->ID; ?>" class="btn btn-sm btn-danger" onclick="return confirm('<?php _e('Are you sure?', 'siteiran-wholesale'); ?>');"><?php _e('Delete', 'siteiran-wholesale'); ?></a>
                                        <?php endif; ?>
                                        <a href="#" class="btn btn-sm btn-secondary siwo-print-order" data-order-id="<?php echo $order->ID; ?>"><?php _e('Print', 'siteiran-wholesale'); ?></a>
                                        <?php if (!get_post_meta($order->ID, 'siwo_converted_to_wc', true)) : ?>
                                            <a href="?page=siwo-orders&action=convert&order_id=<?php echo $order->ID; ?>" class="btn btn-sm btn-success siwo-convert-order"><?php _e('Convert to WC Order', 'siteiran-wholesale'); ?></a>
                                        <?php else : ?>
                                            <a href="<?php echo admin_url('post.php?post=' . get_post_meta($order->ID, 'siwo_converted_to_wc', true) . '&action=edit'); ?>" class="btn btn-sm btn-info"><?php _e('View WC Order', 'siteiran-wholesale'); ?></a>
                                        <?php endif; ?>
                                    </td>
                                </tr>

                                <!-- مودال برای نمایش محصولات -->
                                <div class="modal fade" id="productsModal-<?php echo esc_attr($order->ID); ?>" tabindex="-1" aria-labelledby="productsModalLabel-<?php echo esc_attr($order->ID); ?>" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="productsModalLabel-<?php echo esc_attr($order->ID); ?>"><?php _e('Products in Order #', 'siteiran-wholesale'); ?><?php echo esc_html($order->ID); ?></h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <?php if (!empty($product_list)) : ?>
                                                    <ul>
                                                        <?php foreach ($product_list as $product) : ?>
                                                            <li><?php echo $product; ?></li>
                                                        <?php endforeach; ?>
                                                    </ul>
                                                <?php else : ?>
                                                    <p><?php _e('No products found.', 'siteiran-wholesale'); ?></p>
                                                <?php endif; ?>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php _e('Close', 'siteiran-wholesale'); ?></button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <tr>
                                <td colspan="6" class="text-center"><?php _e('No orders found.', 'siteiran-wholesale'); ?></td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
    }
}