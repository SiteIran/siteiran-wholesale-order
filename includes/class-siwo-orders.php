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

        $orders = get_posts([
            'post_type' => 'siwo_order',
            'posts_per_page' => -1,
        ]);
        ?>
        <div class="wrap siwo-wrap">
            <h1><?php _e('Wholesale Orders', 'siteiran-wholesale'); ?></h1>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Order ID', 'siteiran-wholesale'); ?></th>
                        <th><?php _e('Customer', 'siteiran-wholesale'); ?></th>
                        <th><?php _e('Status', 'siteiran-wholesale'); ?></th>
                        <th><?php _e('Actions', 'siteiran-wholesale'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order) : ?>
                        <tr>
                            <td><?php echo esc_html($order->ID); ?></td>
                            <td><?php echo esc_html(get_userdata(get_post_meta($order->ID, 'siwo_customer', true))->display_name); ?></td>
                            <td><?php echo esc_html(get_post_meta($order->ID, 'siwo_status', true)); ?></td>
                            <td>
                                <a href="?page=siwo-add-order&edit_order=<?php echo $order->ID; ?>" class="button"><?php _e('Edit', 'siteiran-wholesale'); ?></a>
                                <a href="?page=siwo-orders&action=delete&order_id=<?php echo $order->ID; ?>" class="button" onclick="return confirm('<?php _e('Are you sure?', 'siteiran-wholesale'); ?>');"><?php _e('Delete', 'siteiran-wholesale'); ?></a>
                                <a href="#" class="button siwo-print-order" data-order-id="<?php echo $order->ID; ?>"><?php _e('Print', 'siteiran-wholesale'); ?></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
}