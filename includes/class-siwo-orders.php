<?php
if (!defined('ABSPATH')) {
    exit;
}

class SIWO_Orders {
    public function render() {
        if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['order_id'])) {
            wp_delete_post(intval($_GET['order_id']));
            echo '<div class="updated"><p>' . __('Order deleted.', 'siteiran-wholesale') . '</p></div>';
        }

        // Filter parameters
        $filter_status = isset($_GET['filter_status']) ? sanitize_text_field($_GET['filter_status']) : '';
        $filter_customer = isset($_GET['filter_customer']) ? sanitize_text_field($_GET['filter_customer']) : '';

        // Query arguments
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

        $orders = get_posts($args);
        $users = get_users(['fields' => ['ID', 'display_name']]); // برای لیست مشتریان
        ?>
        <div class="wrap siwo-wrap">
            <h1 class="mb-4"><?php _e('Wholesale Orders', 'siteiran-wholesale'); ?></h1>

            <!-- Filter Form -->
            <form method="get" class="mb-4">
                <input type="hidden" name="page" value="siwo-orders">
                <div class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label"><?php _e('Filter by Status', 'siteiran-wholesale'); ?></label>
                        <select name="filter_status" class="form-select">
                            <option value=""><?php _e('All Statuses', 'siteiran-wholesale'); ?></option>
                            <option value="pending" <?php selected($filter_status, 'pending'); ?>><?php _e('Pending', 'siteiran-wholesale'); ?></option>
                            <option value="processing" <?php selected($filter_status, 'processing'); ?>><?php _e('Processing', 'siteiran-wholesale'); ?></option>
                            <option value="completed" <?php selected($filter_status, 'completed'); ?>><?php _e('Completed', 'siteiran-wholesale'); ?></option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label"><?php _e('Filter by Customer', 'siteiran-wholesale'); ?></label>
                        <select name="filter_customer" class="form-select">
                            <option value=""><?php _e('All Customers', 'siteiran-wholesale'); ?></option>
                            <?php foreach ($users as $user) : ?>
                                <option value="<?php echo esc_attr($user->ID); ?>" <?php selected($filter_customer, $user->ID); ?>><?php echo esc_html($user->display_name); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100"><?php _e('Apply Filter', 'siteiran-wholesale'); ?></button>
                    </div>
                </div>
            </form>

            <!-- Orders Table -->
            <div class="table-responsive">
                <table class="table table-striped table-bordered">
                    <thead class="table-light">
                        <tr>
                            <th><?php _e('Order ID', 'siteiran-wholesale'); ?></th>
                            <th><?php _e('Customer', 'siteiran-wholesale'); ?></th>
                            <th><?php _e('Status', 'siteiran-wholesale'); ?></th>
                            <th><?php _e('Actions', 'siteiran-wholesale'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($orders) : ?>
                            <?php foreach ($orders as $order) : ?>
                                <tr>
                                    <td><?php echo esc_html($order->ID); ?></td>
                                    <td><?php echo esc_html(get_userdata(get_post_meta($order->ID, 'siwo_customer', true))->display_name); ?></td>
                                    <td><?php echo esc_html(get_post_meta($order->ID, 'siwo_status', true)); ?></td>
                                    <td>
                                        <a href="?page=siwo-add-order&edit_order=<?php echo $order->ID; ?>" class="btn btn-sm btn-primary"><?php _e('Edit', 'siteiran-wholesale'); ?></a>
                                        <a href="?page=siwo-orders&action=delete&order_id=<?php echo $order->ID; ?>" class="btn btn-sm btn-danger" onclick="return confirm('<?php _e('Are you sure?', 'siteiran-wholesale'); ?>');"><?php _e('Delete', 'siteiran-wholesale'); ?></a>
                                        <a href="#" class="btn btn-sm btn-secondary siwo-print-order" data-order-id="<?php echo $order->ID; ?>"><?php _e('Print', 'siteiran-wholesale'); ?></a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <tr>
                                <td colspan="4" class="text-center"><?php _e('No orders found.', 'siteiran-wholesale'); ?></td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
    }
}