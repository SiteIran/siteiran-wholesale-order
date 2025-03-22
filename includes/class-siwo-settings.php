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
            if (!empty($_FILES['siwo_logo']['name'])) {
                $uploaded = media_handle_upload('siwo_logo', 0);
                if (!is_wp_error($uploaded)) {
                    update_option('siwo_logo_id', $uploaded);
                }
            }
            echo '<div class="updated"><p>' . __('Settings saved.', 'siteiran-wholesale') . '</p></div>';
        }

        $order_status = get_option('siwo_order_status', 'pending');
        $discount_percent = get_option('siwo_discount_percent', 0);
        $notification_method = get_option('siwo_notification_method', 'email');
        $logo_id = get_option('siwo_logo_id', 0);
        $logo_url = $logo_id ? wp_get_attachment_url($logo_id) : '';
        ?>
        <div class="wrap siwo-wrap">
            <h1 class="mb-4"><?php _e('Wholesale Settings', 'siteiran-wholesale'); ?></h1>
            <form method="post" enctype="multipart/form-data">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label"><?php _e('Default Order Status', 'siteiran-wholesale'); ?></label>
                        <select name="order_status" class="form-select">
                            <option value="pending" <?php selected($order_status, 'pending'); ?>><?php _e('Pending', 'siteiran-wholesale'); ?></option>
                            <option value="processing" <?php selected($order_status, 'processing'); ?>><?php _e('Processing', 'siteiran-wholesale'); ?></option>
                            <option value="completed" <?php selected($order_status, 'completed'); ?>><?php _e('Completed', 'siteiran-wholesale'); ?></option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label"><?php _e('Discount Percent', 'siteiran-wholesale'); ?></label>
                        <input type="number" name="discount_percent" class="form-control" value="<?php echo esc_attr($discount_percent); ?>" step="0.1" />%
                    </div>
                    <div class="col-md-6">
                        <label class="form-label"><?php _e('Notification Method', 'siteiran-wholesale'); ?></label>
                        <select name="notification_method" class="form-select">
                            <option value="email" <?php selected($notification_method, 'email'); ?>><?php _e('Email', 'siteiran-wholesale'); ?></option>
                            <option value="sms" <?php selected($notification_method, 'sms'); ?>><?php _e('SMS', 'siteiran-wholesale'); ?></option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label"><?php _e('Invoice Logo', 'siteiran-wholesale'); ?></label>
                        <input type="file" name="siwo_logo" class="form-control" accept="image/*">
                        <?php if ($logo_url) : ?>
                            <img src="<?php echo esc_url($logo_url); ?>" alt="Logo" style="max-width: 200px; margin-top: 10px;">
                        <?php endif; ?>
                    </div>
                    <div class="col-12">
                        <input type="submit" name="siwo_save_settings" class="btn btn-primary mt-3" value="<?php _e('Save Settings', 'siteiran-wholesale'); ?>" />
                    </div>
                </div>
            </form>
        </div>
        <?php
    }
}