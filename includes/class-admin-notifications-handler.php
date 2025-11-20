<?php
/**
 * Admin Notifications Handler
 *
 * @package WP_Site_Notifications
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Admin_Notifications_Handler
 */
class Admin_Notifications_Handler {

    /**
     * Constructor
     */
    public function __construct() {
        // User Management
        add_action('user_register', array($this, 'handle_user_registered'));
        add_action('delete_user', array($this, 'handle_user_deleted'));
        add_action('set_user_role', array($this, 'handle_user_role_changed'), 10, 3);

        // Plugin Management
        add_action('activated_plugin', array($this, 'handle_plugin_activated'), 10, 2);
        add_action('deactivated_plugin', array($this, 'handle_plugin_deactivated'), 10, 2);
        add_action('upgrader_process_complete', array($this, 'handle_upgrader_complete'), 10, 2);

        // Theme Management
        add_action('switch_theme', array($this, 'handle_theme_switched'), 10, 3);

        // Security
        add_action('wp_login_failed', array($this, 'handle_failed_login'));
        add_action('retrieve_password', array($this, 'handle_password_reset'));
    }

    /**
     * Map notification types to their group email keys
     */
    private $notification_groups = array(
        'user_registered' => 'user_management_email',
        'user_deleted' => 'user_management_email',
        'user_role_changed' => 'user_management_email',
        'plugin_activated' => 'plugin_management_email',
        'plugin_deactivated' => 'plugin_management_email',
        'plugin_updated' => 'plugin_management_email',
        'theme_switched' => 'theme_management_email',
        'theme_updated' => 'theme_management_email',
        'core_updated' => 'core_updates_email',
        'failed_login' => 'security_email',
        'password_reset' => 'security_email'
    );

    /**
     * Get admin notification settings
     */
    private function get_notification_settings($type) {
        $settings = get_option('post_notifications_settings', array());
        $admin_notifications = isset($settings['admin_notifications']) ? $settings['admin_notifications'] : array();

        // Check if notification type is enabled
        if (!isset($admin_notifications[$type]) || empty($admin_notifications[$type]['enabled'])) {
            return false;
        }

        // Get the group email for this notification type
        $email_key = isset($this->notification_groups[$type]) ? $this->notification_groups[$type] : '';
        $email = isset($admin_notifications[$email_key]) ? $admin_notifications[$email_key] : '';

        if (empty($email)) {
            return false;
        }

        return array(
            'enabled' => true,
            'email' => $email
        );
    }

    /**
     * Send admin notification email
     */
    private function send_notification($type, $subject, $message) {
        $notification = $this->get_notification_settings($type);

        if (!$notification) {
            return false;
        }

        $email = $notification['email'];

        // Build HTML email
        $html_message = $this->build_email_html($subject, $message);

        // Set email headers
        $headers = array('Content-Type: text/html; charset=UTF-8');

        return wp_mail($email, $subject, $html_message, $headers);
    }

    /**
     * Build HTML email template
     */
    private function build_email_html($subject, $message) {
        $site_name = get_bloginfo('name');
        $site_url = home_url();

        $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>' . esc_html($subject) . '</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background: #f7f7f7; padding: 20px; border-radius: 5px;">
        <h2 style="color: #0073aa; margin-top: 0;">' . esc_html($subject) . '</h2>
        <div style="background: #fff; padding: 20px; border-radius: 5px; margin: 15px 0;">
            ' . wp_kses_post($message) . '
        </div>
        <p style="font-size: 12px; color: #666; margin-bottom: 0;">
            ' . sprintf(
                __('This is an automated notification from %s.', 'wp-site-notifications'),
                '<a href="' . esc_url($site_url) . '">' . esc_html($site_name) . '</a>'
            ) . '
        </p>
    </div>
</body>
</html>';

        return $html;
    }

    /**
     * Handle new user registration
     */
    public function handle_user_registered($user_id) {
        $user = get_userdata($user_id);
        if (!$user) {
            return;
        }

        $subject = sprintf(__('[%s] New User Registration', 'wp-site-notifications'), get_bloginfo('name'));

        $message = '<p><strong>' . __('A new user has registered:', 'wp-site-notifications') . '</strong></p>';
        $message .= '<ul>';
        $message .= '<li><strong>' . __('Username:', 'wp-site-notifications') . '</strong> ' . esc_html($user->user_login) . '</li>';
        $message .= '<li><strong>' . __('Email:', 'wp-site-notifications') . '</strong> ' . esc_html($user->user_email) . '</li>';
        $message .= '<li><strong>' . __('Display Name:', 'wp-site-notifications') . '</strong> ' . esc_html($user->display_name) . '</li>';
        $message .= '<li><strong>' . __('Date:', 'wp-site-notifications') . '</strong> ' . current_time('mysql') . '</li>';
        $message .= '</ul>';
        $message .= '<p><a href="' . esc_url(admin_url('user-edit.php?user_id=' . $user_id)) . '">' . __('View User Profile', 'wp-site-notifications') . '</a></p>';

        $this->send_notification('user_registered', $subject, $message);
    }

    /**
     * Handle user deletion
     */
    public function handle_user_deleted($user_id) {
        $user = get_userdata($user_id);
        if (!$user) {
            return;
        }

        $subject = sprintf(__('[%s] User Deleted', 'wp-site-notifications'), get_bloginfo('name'));

        $message = '<p><strong>' . __('A user has been deleted:', 'wp-site-notifications') . '</strong></p>';
        $message .= '<ul>';
        $message .= '<li><strong>' . __('Username:', 'wp-site-notifications') . '</strong> ' . esc_html($user->user_login) . '</li>';
        $message .= '<li><strong>' . __('Email:', 'wp-site-notifications') . '</strong> ' . esc_html($user->user_email) . '</li>';
        $message .= '<li><strong>' . __('Display Name:', 'wp-site-notifications') . '</strong> ' . esc_html($user->display_name) . '</li>';
        $message .= '<li><strong>' . __('Date:', 'wp-site-notifications') . '</strong> ' . current_time('mysql') . '</li>';
        $message .= '</ul>';

        $this->send_notification('user_deleted', $subject, $message);
    }

    /**
     * Handle user role change
     */
    public function handle_user_role_changed($user_id, $role, $old_roles) {
        $user = get_userdata($user_id);
        if (!$user) {
            return;
        }

        $wp_roles = wp_roles();
        $new_role_name = isset($wp_roles->role_names[$role]) ? translate_user_role($wp_roles->role_names[$role]) : $role;

        $old_role_names = array();
        foreach ($old_roles as $old_role) {
            $old_role_names[] = isset($wp_roles->role_names[$old_role]) ? translate_user_role($wp_roles->role_names[$old_role]) : $old_role;
        }

        $subject = sprintf(__('[%s] User Role Changed', 'wp-site-notifications'), get_bloginfo('name'));

        $message = '<p><strong>' . __('A user\'s role has been changed:', 'wp-site-notifications') . '</strong></p>';
        $message .= '<ul>';
        $message .= '<li><strong>' . __('Username:', 'wp-site-notifications') . '</strong> ' . esc_html($user->user_login) . '</li>';
        $message .= '<li><strong>' . __('Email:', 'wp-site-notifications') . '</strong> ' . esc_html($user->user_email) . '</li>';
        $message .= '<li><strong>' . __('Previous Role(s):', 'wp-site-notifications') . '</strong> ' . esc_html(implode(', ', $old_role_names)) . '</li>';
        $message .= '<li><strong>' . __('New Role:', 'wp-site-notifications') . '</strong> ' . esc_html($new_role_name) . '</li>';
        $message .= '<li><strong>' . __('Date:', 'wp-site-notifications') . '</strong> ' . current_time('mysql') . '</li>';
        $message .= '</ul>';
        $message .= '<p><a href="' . esc_url(admin_url('user-edit.php?user_id=' . $user_id)) . '">' . __('View User Profile', 'wp-site-notifications') . '</a></p>';

        $this->send_notification('user_role_changed', $subject, $message);
    }

    /**
     * Handle plugin activation
     */
    public function handle_plugin_activated($plugin, $network_wide) {
        $plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin);
        $plugin_name = !empty($plugin_data['Name']) ? $plugin_data['Name'] : $plugin;

        $subject = sprintf(__('[%s] Plugin Activated', 'wp-site-notifications'), get_bloginfo('name'));

        $message = '<p><strong>' . __('A plugin has been activated:', 'wp-site-notifications') . '</strong></p>';
        $message .= '<ul>';
        $message .= '<li><strong>' . __('Plugin:', 'wp-site-notifications') . '</strong> ' . esc_html($plugin_name) . '</li>';
        if (!empty($plugin_data['Version'])) {
            $message .= '<li><strong>' . __('Version:', 'wp-site-notifications') . '</strong> ' . esc_html($plugin_data['Version']) . '</li>';
        }
        $message .= '<li><strong>' . __('Network Wide:', 'wp-site-notifications') . '</strong> ' . ($network_wide ? __('Yes', 'wp-site-notifications') : __('No', 'wp-site-notifications')) . '</li>';
        $message .= '<li><strong>' . __('Date:', 'wp-site-notifications') . '</strong> ' . current_time('mysql') . '</li>';
        $message .= '</ul>';
        $message .= '<p><a href="' . esc_url(admin_url('plugins.php')) . '">' . __('View Plugins', 'wp-site-notifications') . '</a></p>';

        $this->send_notification('plugin_activated', $subject, $message);
    }

    /**
     * Handle plugin deactivation
     */
    public function handle_plugin_deactivated($plugin, $network_wide) {
        $plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin);
        $plugin_name = !empty($plugin_data['Name']) ? $plugin_data['Name'] : $plugin;

        $subject = sprintf(__('[%s] Plugin Deactivated', 'wp-site-notifications'), get_bloginfo('name'));

        $message = '<p><strong>' . __('A plugin has been deactivated:', 'wp-site-notifications') . '</strong></p>';
        $message .= '<ul>';
        $message .= '<li><strong>' . __('Plugin:', 'wp-site-notifications') . '</strong> ' . esc_html($plugin_name) . '</li>';
        if (!empty($plugin_data['Version'])) {
            $message .= '<li><strong>' . __('Version:', 'wp-site-notifications') . '</strong> ' . esc_html($plugin_data['Version']) . '</li>';
        }
        $message .= '<li><strong>' . __('Network Wide:', 'wp-site-notifications') . '</strong> ' . ($network_wide ? __('Yes', 'wp-site-notifications') : __('No', 'wp-site-notifications')) . '</li>';
        $message .= '<li><strong>' . __('Date:', 'wp-site-notifications') . '</strong> ' . current_time('mysql') . '</li>';
        $message .= '</ul>';
        $message .= '<p><a href="' . esc_url(admin_url('plugins.php')) . '">' . __('View Plugins', 'wp-site-notifications') . '</a></p>';

        $this->send_notification('plugin_deactivated', $subject, $message);
    }

    /**
     * Handle upgrader process complete (plugins, themes, core)
     */
    public function handle_upgrader_complete($upgrader, $options) {
        if (!isset($options['type'])) {
            return;
        }

        switch ($options['type']) {
            case 'plugin':
                $this->handle_plugin_updated($upgrader, $options);
                break;
            case 'theme':
                $this->handle_theme_updated($upgrader, $options);
                break;
            case 'core':
                $this->handle_core_updated($upgrader, $options);
                break;
        }
    }

    /**
     * Handle plugin update
     */
    private function handle_plugin_updated($upgrader, $options) {
        if (!isset($options['plugins']) || !is_array($options['plugins'])) {
            return;
        }

        $updated_plugins = array();
        foreach ($options['plugins'] as $plugin) {
            $plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin);
            $plugin_name = !empty($plugin_data['Name']) ? $plugin_data['Name'] : $plugin;
            $version = !empty($plugin_data['Version']) ? $plugin_data['Version'] : __('Unknown', 'wp-site-notifications');
            $updated_plugins[] = $plugin_name . ' (v' . $version . ')';
        }

        $subject = sprintf(__('[%s] Plugin(s) Updated', 'wp-site-notifications'), get_bloginfo('name'));

        $message = '<p><strong>' . __('The following plugin(s) have been updated:', 'wp-site-notifications') . '</strong></p>';
        $message .= '<ul>';
        foreach ($updated_plugins as $plugin_info) {
            $message .= '<li>' . esc_html($plugin_info) . '</li>';
        }
        $message .= '</ul>';
        $message .= '<p><strong>' . __('Date:', 'wp-site-notifications') . '</strong> ' . current_time('mysql') . '</p>';
        $message .= '<p><a href="' . esc_url(admin_url('plugins.php')) . '">' . __('View Plugins', 'wp-site-notifications') . '</a></p>';

        $this->send_notification('plugin_updated', $subject, $message);
    }

    /**
     * Handle theme update
     */
    private function handle_theme_updated($upgrader, $options) {
        if (!isset($options['themes']) || !is_array($options['themes'])) {
            return;
        }

        $updated_themes = array();
        foreach ($options['themes'] as $theme_slug) {
            $theme = wp_get_theme($theme_slug);
            if ($theme->exists()) {
                $updated_themes[] = $theme->get('Name') . ' (v' . $theme->get('Version') . ')';
            } else {
                $updated_themes[] = $theme_slug;
            }
        }

        $subject = sprintf(__('[%s] Theme(s) Updated', 'wp-site-notifications'), get_bloginfo('name'));

        $message = '<p><strong>' . __('The following theme(s) have been updated:', 'wp-site-notifications') . '</strong></p>';
        $message .= '<ul>';
        foreach ($updated_themes as $theme_info) {
            $message .= '<li>' . esc_html($theme_info) . '</li>';
        }
        $message .= '</ul>';
        $message .= '<p><strong>' . __('Date:', 'wp-site-notifications') . '</strong> ' . current_time('mysql') . '</p>';
        $message .= '<p><a href="' . esc_url(admin_url('themes.php')) . '">' . __('View Themes', 'wp-site-notifications') . '</a></p>';

        $this->send_notification('theme_updated', $subject, $message);
    }

    /**
     * Handle theme switch
     */
    public function handle_theme_switched($new_name, $new_theme, $old_theme) {
        $subject = sprintf(__('[%s] Theme Switched', 'wp-site-notifications'), get_bloginfo('name'));

        $old_theme_name = $old_theme ? $old_theme->get('Name') : __('Unknown', 'wp-site-notifications');

        $message = '<p><strong>' . __('The active theme has been changed:', 'wp-site-notifications') . '</strong></p>';
        $message .= '<ul>';
        $message .= '<li><strong>' . __('Previous Theme:', 'wp-site-notifications') . '</strong> ' . esc_html($old_theme_name) . '</li>';
        $message .= '<li><strong>' . __('New Theme:', 'wp-site-notifications') . '</strong> ' . esc_html($new_name) . '</li>';
        $message .= '<li><strong>' . __('Date:', 'wp-site-notifications') . '</strong> ' . current_time('mysql') . '</li>';
        $message .= '</ul>';
        $message .= '<p><a href="' . esc_url(admin_url('themes.php')) . '">' . __('View Themes', 'wp-site-notifications') . '</a></p>';

        $this->send_notification('theme_switched', $subject, $message);
    }

    /**
     * Handle core update
     */
    private function handle_core_updated($upgrader, $options) {
        global $wp_version;

        $subject = sprintf(__('[%s] WordPress Core Updated', 'wp-site-notifications'), get_bloginfo('name'));

        $message = '<p><strong>' . __('WordPress core has been updated:', 'wp-site-notifications') . '</strong></p>';
        $message .= '<ul>';
        $message .= '<li><strong>' . __('New Version:', 'wp-site-notifications') . '</strong> ' . esc_html($wp_version) . '</li>';
        $message .= '<li><strong>' . __('Date:', 'wp-site-notifications') . '</strong> ' . current_time('mysql') . '</li>';
        $message .= '</ul>';
        $message .= '<p><a href="' . esc_url(admin_url('update-core.php')) . '">' . __('View Updates', 'wp-site-notifications') . '</a></p>';

        $this->send_notification('core_updated', $subject, $message);
    }

    /**
     * Handle failed login attempt
     */
    public function handle_failed_login($username) {
        // Rate limit failed login notifications (max 1 per 5 minutes per username)
        $transient_key = 'wp_notif_failed_login_' . md5($username);
        if (get_transient($transient_key)) {
            return;
        }
        set_transient($transient_key, true, 5 * MINUTE_IN_SECONDS);

        $subject = sprintf(__('[%s] Failed Login Attempt', 'wp-site-notifications'), get_bloginfo('name'));

        $ip_address = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field($_SERVER['REMOTE_ADDR']) : __('Unknown', 'wp-site-notifications');

        $message = '<p><strong>' . __('A failed login attempt was detected:', 'wp-site-notifications') . '</strong></p>';
        $message .= '<ul>';
        $message .= '<li><strong>' . __('Username:', 'wp-site-notifications') . '</strong> ' . esc_html($username) . '</li>';
        $message .= '<li><strong>' . __('IP Address:', 'wp-site-notifications') . '</strong> ' . esc_html($ip_address) . '</li>';
        $message .= '<li><strong>' . __('Date:', 'wp-site-notifications') . '</strong> ' . current_time('mysql') . '</li>';
        $message .= '</ul>';
        $message .= '<p style="color: #d63638;"><strong>' . __('If you did not attempt to log in, please review your site security.', 'wp-site-notifications') . '</strong></p>';

        $this->send_notification('failed_login', $subject, $message);
    }

    /**
     * Handle password reset request
     */
    public function handle_password_reset($user_login) {
        $user = get_user_by('login', $user_login);
        if (!$user) {
            $user = get_user_by('email', $user_login);
        }

        $subject = sprintf(__('[%s] Password Reset Requested', 'wp-site-notifications'), get_bloginfo('name'));

        $ip_address = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field($_SERVER['REMOTE_ADDR']) : __('Unknown', 'wp-site-notifications');

        $message = '<p><strong>' . __('A password reset was requested:', 'wp-site-notifications') . '</strong></p>';
        $message .= '<ul>';
        $message .= '<li><strong>' . __('Username:', 'wp-site-notifications') . '</strong> ' . esc_html($user ? $user->user_login : $user_login) . '</li>';
        if ($user) {
            $message .= '<li><strong>' . __('Email:', 'wp-site-notifications') . '</strong> ' . esc_html($user->user_email) . '</li>';
        }
        $message .= '<li><strong>' . __('IP Address:', 'wp-site-notifications') . '</strong> ' . esc_html($ip_address) . '</li>';
        $message .= '<li><strong>' . __('Date:', 'wp-site-notifications') . '</strong> ' . current_time('mysql') . '</li>';
        $message .= '</ul>';

        $this->send_notification('password_reset', $subject, $message);
    }
}
