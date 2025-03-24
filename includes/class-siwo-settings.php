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
            update_option('siwo_notify_customer', isset($_POST['notify_customer']) ? 1 : 0);
            update_option('siwo_notify_admin', isset($_POST['notify_admin']) ? 1 : 0);
            update_option('siwo_email_header', sanitize_text_field($_POST['email_header']));
            update_option('siwo_sms_provider', sanitize_text_field($_POST['sms_provider']));
            update_option('siwo_sms_ir_api_key', sanitize_text_field($_POST['sms_ir_api_key']));
            update_option('siwo_sms_ir_template_id', sanitize_text_field($_POST['sms_ir_template_id']));
            update_option('admin_phone', sanitize_text_field($_POST['admin_phone']));
            update_option('siwo_sms_params', array_map('sanitize_text_field', $_POST['sms_params'] ?? []));
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
        $notify_customer = get_option('siwo_notify_customer', 1);
        $notify_admin = get_option('siwo_notify_admin', 1);
        $email_header = get_option('siwo_email_header', 'Wholesale Order Notification');
        $sms_provider = get_option('siwo_sms_provider', 'sms_ir');
        $sms_ir_api_key = get_option('siwo_sms_ir_api_key', '');
        $sms_ir_template_id = get_option('siwo_sms_ir_template_id', '');
        $admin_phone = get_option('admin_phone', '');
        $sms_params = get_option('siwo_sms_params', ['ORDER_ID', 'STATUS', 'PRODUCTS', 'NOTES', 'FULLNAME']);
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
                            <option value="both" <?php selected($notification_method, 'both'); ?>><?php _e('Email & SMS', 'siteiran-wholesale'); ?></option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label"><?php _e('Notify On Order Events', 'siteiran-wholesale'); ?></label>
                        <div class="form-check">
                            <input type="checkbox" name="notify_customer" class="form-check-input" value="1" <?php checked($notify_customer, 1); ?>>
                            <label class="form-check-label"><?php _e('Notify Customer', 'siteiran-wholesale'); ?></label>
                        </div>
                        <div class="form-check">
                            <input type="checkbox" name="notify_admin" class="form-check-input" value="1" <?php checked($notify_admin, 1); ?>>
                            <label class="form-check-label"><?php _e('Notify Admin', 'siteiran-wholesale'); ?></label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label"><?php _e('Email Header', 'siteiran-wholesale'); ?></label>
                        <input type="text" name="email_header" class="form-control" value="<?php echo esc_attr($email_header); ?>" placeholder="Wholesale Order Notification">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label"><?php _e('SMS Provider', 'siteiran-wholesale'); ?></label>
                        <select name="sms_provider" class="form-select">
                            <option value="sms_ir" <?php selected($sms_provider, 'sms_ir'); ?>><?php _e('sms.ir', 'siteiran-wholesale'); ?></option>
                        </select>
                    </div>
                    <!-- فیلدهای API Key و Template ID -->
                    <div class="col-md-6 sms-provider sms-ir">
                        <label class="form-label"><?php _e('sms.ir API Key', 'siteiran-wholesale'); ?></label>
                        <input type="text" name="sms_ir_api_key" class="form-control" value="<?php echo esc_attr($sms_ir_api_key); ?>" placeholder="Enter your sms.ir API key here" required>
                        <small class="form-text text-muted"><?php _e('Get this from your sms.ir panel under API settings.', 'siteiran-wholesale'); ?></small>
                    </div>
                    <div class="col-md-6 sms-provider sms-ir">
                        <label class="form-label"><?php _e('sms.ir Template ID', 'siteiran-wholesale'); ?></label>
                        <input type="text" name="sms_ir_template_id" class="form-control" value="<?php echo esc_attr($sms_ir_template_id); ?>" placeholder="Enter your sms.ir template ID here" required>
                        <small class="form-text text-muted"><?php _e('Define a template in sms.ir with parameters like ORDER_ID, STATUS, etc.', 'siteiran-wholesale'); ?></small>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label"><?php _e('Admin Phone Number', 'siteiran-wholesale'); ?></label>
                        <input type="text" name="admin_phone" class="form-control" value="<?php echo esc_attr($admin_phone); ?>" placeholder="Enter admin phone number (e.g., 09123456789)">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label"><?php _e('SMS Parameters', 'siteiran-wholesale'); ?></label>
                        <div id="sms-params">
                            <?php foreach ($sms_params as $param) : ?>
                                <div class="input-group mb-2">
                                    <input type="text" name="sms_params[]" class="form-control" value="<?php echo esc_attr($param); ?>">
                                    <button type="button" class="btn btn-danger remove-param"><?php _e('Remove', 'siteiran-wholesale'); ?></button>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <button type="button" id="add-sms-param" class="btn btn-secondary mt-2"><?php _e('Add Parameter', 'siteiran-wholesale'); ?></button>
                        <small class="form-text text-muted"><?php _e('Match these with your sms.ir template parameters.', 'siteiran-wholesale'); ?></small>
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
        <style>
            .sms-provider.sms-ir {
                display: block !important; /* برای اطمینان از نمایش */
            }
            .sms-provider label {
                font-weight: bold;
                color: #0056b3;
            }
            .form-text {
                font-size: 0.9em;
                color: #666;
            }
        </style>
        <script>
            document.querySelector('[name="sms_provider"]').addEventListener('change', function() {
                document.querySelectorAll('.sms-provider').forEach(function(el) {
                    el.style.display = 'none';
                });
                document.querySelectorAll('.sms-' + this.value).forEach(function(el) {
                    el.style.display = 'block';
                });
            });
            // اطمینان از نمایش اولیه
            document.querySelectorAll('.sms-provider.sms-ir').forEach(function(el) {
                el.style.display = 'block';
            });

            document.getElementById('add-sms-param').addEventListener('click', function() {
                const container = document.getElementById('sms-params');
                const newParam = document.createElement('div');
                newParam.className = 'input-group mb-2';
                newParam.innerHTML = '<input type="text" name="sms_params[]" class="form-control" value=""><button type="button" class="btn btn-danger remove-param"><?php _e('Remove', 'siteiran-wholesale'); ?></button>';
                container.appendChild(newParam);
            });

            document.addEventListener('click', function(e) {
                if (e.target.classList.contains('remove-param')) {
                    e.target.parentElement.remove();
                }
            });
        </script>
        <?php
    }
}