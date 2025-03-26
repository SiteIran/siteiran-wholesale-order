<?php
if (!defined('ABSPATH')) {
    exit; // جلوگیری از دسترسی مستقیم به فایل
}

/**
 * کلاس SIWO_Settings برای مدیریت تنظیمات
 */
class SIWO_Settings {
    public function render() {
        if (isset($_POST['siwo_save_settings'])) {
            update_option('siwo_order_status', sanitize_text_field($_POST['siwo_order_status']));
            update_option('siwo_discount_percent', floatval($_POST['siwo_discount_percent']));
            update_option('siwo_notify_customer', isset($_POST['siwo_notify_customer']) ? 1 : 0);
            update_option('siwo_notify_admin', isset($_POST['siwo_notify_admin']) ? 1 : 0);
            update_option('siwo_notification_method', sanitize_text_field($_POST['siwo_notification_method']));
            update_option('siwo_admin_phone', sanitize_text_field($_POST['siwo_admin_phone']));
            update_option('siwo_sms_provider', sanitize_text_field($_POST['siwo_sms_provider']));
            update_option('siwo_sms_ir_api_key', sanitize_text_field($_POST['siwo_sms_ir_api_key']));
            update_option('siwo_sms_ir_template_id', sanitize_text_field($_POST['siwo_sms_ir_template_id']));
            update_option('siwo_sms_ir_service_number', sanitize_text_field($_POST['siwo_sms_ir_service_number']));
            update_option('siwo_logo', esc_url_raw($_POST['siwo_logo']));

            $sms_params = [];
            if (isset($_POST['siwo_sms_param_name']) && isset($_POST['siwo_sms_param_value'])) {
                $param_names = (array) $_POST['siwo_sms_param_name'];
                $param_values = (array) $_POST['siwo_sms_param_value'];
                foreach ($param_names as $index => $name) {
                    if (!empty($name)) {
                        $sms_params[] = [
                            'name' => sanitize_text_field($name),
                            'value' => sanitize_text_field($param_values[$index]),
                        ];
                    }
                }
            }
            update_option('siwo_sms_params', $sms_params);

            echo '<div class="notice notice-success is-dismissible"><p>' . __('Settings saved successfully!', 'siteiran-wholesale') . '</p></div>';
        }

        $order_status = get_option('siwo_order_status', 'pending');
        $discount_percent = get_option('siwo_discount_percent', 0);
        $notify_customer = get_option('siwo_notify_customer', 1);
        $notify_admin = get_option('siwo_notify_admin', 1);
        $notification_method = get_option('siwo_notification_method', 'email');
        $admin_phone = get_option('siwo_admin_phone', '');
        $sms_provider = get_option('siwo_sms_provider', 'sms_ir');
        $sms_ir_api_key = get_option('siwo_sms_ir_api_key', '');
        $sms_ir_template_id = get_option('siwo_sms_ir_template_id', '');
        $sms_ir_service_number = get_option('siwo_sms_ir_service_number', '');
        $logo = get_option('siwo_logo', '');
        $sms_params = get_option('siwo_sms_params', []);
        ?>
        <div class="wrap siwo-wrap">
            <h1 class="mb-4"><?php _e('Wholesale Settings', 'siteiran-wholesale'); ?></h1>
            <form method="post" class="siwo-settings-form">
                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <h5 class="card-title"><?php _e('General Settings', 'siteiran-wholesale'); ?></h5>
                        <div class="mb-3">
                            <label for="siwo_order_status" class="form-label"><?php _e('Default Order Status', 'siteiran-wholesale'); ?></label>
                            <select name="siwo_order_status" id="siwo_order_status" class="form-select">
                                <option value="pending" <?php selected($order_status, 'pending'); ?>><?php _e('Pending', 'siteiran-wholesale'); ?></option>
                                <option value="processing" <?php selected($order_status, 'processing'); ?>><?php _e('Processing', 'siteiran-wholesale'); ?></option>
                                <option value="completed" <?php selected($order_status, 'completed'); ?>><?php _e('Completed', 'siteiran-wholesale'); ?></option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="siwo_discount_percent" class="form-label"><?php _e('Default Discount (%)', 'siteiran-wholesale'); ?></label>
                            <input type="number" name="siwo_discount_percent" id="siwo_discount_percent" class="form-control w-25" value="<?php echo esc_attr($discount_percent); ?>" step="0.01" min="0" />
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <h5 class="card-title"><?php _e('Logo Settings', 'siteiran-wholesale'); ?></h5>
                        <div class="mb-3">
                            <label for="siwo_logo" class="form-label"><?php _e('Logo', 'siteiran-wholesale'); ?></label>
                            <input type="text" name="siwo_logo" id="siwo_logo" class="form-control w-50 d-inline-block me-2" value="<?php echo esc_attr($logo); ?>" readonly />
                            <button type="button" id="siwo_upload_logo" class="btn btn-primary"><?php _e('Upload Logo', 'siteiran-wholesale'); ?></button>
                            <?php if ($logo) : ?>
                                <button type="button" id="siwo_remove_logo" class="btn btn-danger"><?php _e('Remove Logo', 'siteiran-wholesale'); ?></button>
                            <?php endif; ?>
                            <?php if ($logo) : ?>
                                <div class="mt-2">
                                    <img src="<?php echo esc_url($logo); ?>" alt="<?php _e('Logo Preview', 'siteiran-wholesale'); ?>" style="max-width: 200px; max-height: 200px;" />
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <h5 class="card-title"><?php _e('Notification Settings', 'siteiran-wholesale'); ?></h5>
                        <div class="mb-3">
                            <div class="form-check">
                                <input type="checkbox" name="siwo_notify_customer" id="siwo_notify_customer" class="form-check-input" value="1" <?php checked($notify_customer, 1); ?> />
                                <label for="siwo_notify_customer" class="form-check-label"><?php _e('Notify Customer', 'siteiran-wholesale'); ?></label>
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="form-check">
                                <input type="checkbox" name="siwo_notify_admin" id="siwo_notify_admin" class="form-check-input" value="1" <?php checked($notify_admin, 1); ?> />
                                <label for="siwo_notify_admin" class="form-check-label"><?php _e('Notify Admin', 'siteiran-wholesale'); ?></label>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="siwo_notification_method" class="form-label"><?php _e('Notification Method', 'siteiran-wholesale'); ?></label>
                            <select name="siwo_notification_method" id="siwo_notification_method" class="form-select">
                                <option value="email" <?php selected($notification_method, 'email'); ?>><?php _e('Email', 'siteiran-wholesale'); ?></option>
                                <option value="sms" <?php selected($notification_method, 'sms'); ?>><?php _e('SMS', 'siteiran-wholesale'); ?></option>
                                <option value="both" <?php selected($notification_method, 'both'); ?>><?php _e('Both', 'siteiran-wholesale'); ?></option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="siwo_admin_phone" class="form-label"><?php _e('Admin Phone Number', 'siteiran-wholesale'); ?></label>
                            <input type="text" name="siwo_admin_phone" id="siwo_admin_phone" class="form-control w-25" value="<?php echo esc_attr($admin_phone); ?>" />
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <h5 class="card-title"><?php _e('SMS Settings', 'siteiran-wholesale'); ?></h5>
                        <div class="mb-3">
                            <label for="siwo_sms_provider" class="form-label"><?php _e('SMS Provider', 'siteiran-wholesale'); ?></label>
                            <select name="siwo_sms_provider" id="siwo_sms_provider" class="form-select">
                                <option value="sms_ir" <?php selected($sms_provider, 'sms_ir'); ?>><?php _e('SMS.ir', 'siteiran-wholesale'); ?></option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="siwo_sms_ir_api_key" class="form-label"><?php _e('SMS.ir API Key', 'siteiran-wholesale'); ?></label>
                            <input type="text" name="siwo_sms_ir_api_key" id="siwo_sms_ir_api_key" class="form-control" value="<?php echo esc_attr($sms_ir_api_key); ?>" />
                        </div>
                        <div class="mb-3">
                            <label for="siwo_sms_ir_template_id" class="form-label"><?php _e('SMS.ir Template ID', 'siteiran-wholesale'); ?></label>
                            <input type="text" name="siwo_sms_ir_template_id" id="siwo_sms_ir_template_id" class="form-control" value="<?php echo esc_attr($sms_ir_template_id); ?>" />
                        </div>
                        <div class="mb-3">
                            <label for="siwo_sms_ir_service_number" class="form-label"><?php _e('SMS.ir Service Number', 'siteiran-wholesale'); ?></label>
                            <input type="text" name="siwo_sms_ir_service_number" id="siwo_sms_ir_service_number" class="form-control" value="<?php echo esc_attr($sms_ir_service_number); ?>" />
                        </div>
                        <div class="mb-3">
                            <h6><?php _e('SMS Parameters', 'siteiran-wholesale'); ?></h6>
                            <div id="siwo-sms-params">
                                <?php if (!empty($sms_params)) : ?>
                                    <?php foreach ($sms_params as $index => $param) : ?>
                                        <div class="siwo-sms-param-row mb-2 d-flex align-items-center">
                                            <input type="text" name="siwo_sms_param_name[]" class="form-control w-25 me-2" value="<?php echo esc_attr($param['name']); ?>" placeholder="<?php _e('Parameter Name', 'siteiran-wholesale'); ?>" />
                                            <input type="text" name="siwo_sms_param_value[]" class="form-control w-25 me-2" value="<?php echo esc_attr($param['value']); ?>" placeholder="<?php _e('Parameter Value', 'siteiran-wholesale'); ?>" />
                                            <button type="button" class="btn btn-danger btn-sm siwo-remove-param"><i class="bi bi-trash"></i></button>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                            <button type="button" id="siwo-add-param" class="btn btn-outline-primary mt-2"><i class="bi bi-plus-circle"></i> <?php _e('Add Parameter', 'siteiran-wholesale'); ?></button>
                        </div>
                    </div>
                </div>

                <button type="submit" name="siwo_save_settings" class="btn btn-primary"><?php _e('Save Settings', 'siteiran-wholesale'); ?></button>
            </form>
        </div>
        <?php
        // لود اسکریپت‌های Media Uploader
        wp_enqueue_media();
    }
}