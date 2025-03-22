<?php
if (!defined('ABSPATH')) {
    exit;
}

class SIWO_Settings {
    public function render() {
        if (isset($_POST['siwo_save_settings'])) {
            update_option('siwo_order_status', sanitize_text_field($_POST['order_status']));
            update_option('siwo_discount_percent', floatval($_POST['discount_percent']));
            update_option('siwo_notification_method', sanitize_text_field($_POST['notification_method']));
            echo '<div class="updated"><p>' . __('Settings saved.', 'siteiran-wholesale') . '</p></div>';
        }

        $order_status = get_option('siwo_order_status', 'pending');
        $discount_percent = get_option('siwo_discount_percent', 0);
        $notification_method = get_option('siwo_notification_method', 'email');
        ?>
        <div class="wrap siwo-wrap">
            <h1><?php _e('Wholesale Settings', 'siteiran-wholesale'); ?></h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th><?php _e('Default Order Status', 'siteiran-wholesale'); ?></th>
                        <td>
                            <select name="order_status">
                                <option value="pending" <?php selected($order_status, 'pending'); ?>><?php _e('Pending', 'siteiran-wholesale'); ?></option>
                                <option value="processing" <?php selected($order_status, 'processing'); ?>><?php _e('Processing', 'siteiran-wholesale'); ?></option>
                                <option value="completed" <?php selected($order_status, 'completed'); ?>><?php _e('Completed', 'siteiran-wholesale'); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('Discount Percent', 'siteiran-wholesale'); ?></th>
                        <td><input type="number" name="discount_percent" value="<?php echo esc_attr($discount_percent); ?>" step="0.1" />%</td>
                    </tr>
                    <tr>
                        <th><?php _e('Notification Method', 'siteiran-wholesale'); ?></th>
                        <td>
                            <select name="notification_method">
                                <option value="email" <?php selected($notification_method, 'email'); ?>><?php _e('Email', 'siteiran-wholesale'); ?></option>
                                <option value="sms" <?php selected($notification_method, 'sms'); ?>><?php _e('SMS', 'siteiran-wholesale'); ?></option>
                            </select>
                        </td>
                    </tr>
                </table>
                <input type="submit" name="siwo_save_settings" class="button button-primary" value="<?php _e('Save Settings', 'siteiran-wholesale'); ?>" />
            </form>
        </div>
        <?php
    }
}