<?php
/**
 * Settings Page Handler
 *
 * @package Post_Notifications
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Post_Notifications_Settings
 */
class Post_Notifications_Settings {

    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_options_page(
            __('WP Notifications Settings', 'wp-site-notifications'),
            __('WP Notifications', 'wp-site-notifications'),
            'manage_options',
            'wp-site-notifications',
            array($this, 'render_settings_page')
        );
    }

    /**
     * Register settings
     */
    public function register_settings() {
        register_setting(
            'post_notifications_settings_group',
            'post_notifications_settings',
            array($this, 'sanitize_settings')
        );
    }

    /**
     * Sanitize settings
     */
    public function sanitize_settings($input) {
        $sanitized = array();

        // Verify nonce for security
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'post_notifications_settings_group-options')) {
            add_settings_error(
                'post_notifications_settings',
                'nonce_failed',
                __('Security check failed. Please try again.', 'wp-site-notifications'),
                'error'
            );
            return get_option('post_notifications_settings', array());
        }

        // Verify user has permission
        if (!current_user_can('manage_options')) {
            add_settings_error(
                'post_notifications_settings',
                'permission_denied',
                __('You do not have permission to modify these settings.', 'wp-site-notifications'),
                'error'
            );
            return get_option('post_notifications_settings', array());
        }

        // Sanitize enabled notifications - validate against allowed types
        $allowed_notification_types = array('pending', 'published', 'draft', 'scheduled', 'updated', 'trashed');
        if (isset($input['enabled_notifications']) && is_array($input['enabled_notifications'])) {
            $sanitized['enabled_notifications'] = array();
            foreach ($input['enabled_notifications'] as $key => $value) {
                if (in_array($key, $allowed_notification_types, true)) {
                    $sanitized['enabled_notifications'][$key] = '1';
                }
            }
        } else {
            $sanitized['enabled_notifications'] = array();
        }

        // Sanitize recipient roles - validate against actual WordPress roles
        if (isset($input['recipient_roles']) && is_array($input['recipient_roles'])) {
            $available_roles = array_keys(wp_roles()->get_names());
            $sanitized['recipient_roles'] = array();
            foreach ($input['recipient_roles'] as $role) {
                $role = sanitize_text_field($role);
                if (in_array($role, $available_roles, true)) {
                    $sanitized['recipient_roles'][] = $role;
                }
            }
        } else {
            $sanitized['recipient_roles'] = array();
        }

        // Sanitize enabled post types - validate against actual post types
        if (isset($input['enabled_post_types']) && is_array($input['enabled_post_types'])) {
            $available_post_types = get_post_types(array('public' => true), 'names');
            $sanitized['enabled_post_types'] = array();
            foreach ($input['enabled_post_types'] as $post_type) {
                $post_type = sanitize_text_field($post_type);
                if (in_array($post_type, $available_post_types, true)) {
                    $sanitized['enabled_post_types'][] = $post_type;
                }
            }
        } else {
            $sanitized['enabled_post_types'] = array('post'); // Default to standard posts
        }

        // Sanitize recipient users - validate against actual user IDs
        if (isset($input['recipient_users']) && is_array($input['recipient_users'])) {
            $sanitized['recipient_users'] = array();
            foreach ($input['recipient_users'] as $user_id) {
                $user_id = absint($user_id);
                // Verify user exists
                if ($user_id > 0 && get_userdata($user_id)) {
                    $sanitized['recipient_users'][] = $user_id;
                }
            }
        } else {
            $sanitized['recipient_users'] = array();
        }

        // Sanitize admin notifications
        $allowed_admin_notifications = array(
            'user_registered',
            'user_deleted',
            'user_role_changed',
            'plugin_activated',
            'plugin_deactivated',
            'plugin_updated',
            'theme_switched',
            'theme_updated',
            'core_updated',
            'failed_login',
            'password_reset'
        );

        // Group email fields
        $group_emails = array(
            'user_management_email',
            'plugin_management_email',
            'theme_management_email',
            'core_updates_email',
            'security_email'
        );

        $sanitized['admin_notifications'] = array();
        if (isset($input['admin_notifications']) && is_array($input['admin_notifications'])) {
            // Sanitize group emails
            foreach ($group_emails as $email_key) {
                if (isset($input['admin_notifications'][$email_key])) {
                    $sanitized['admin_notifications'][$email_key] = sanitize_email($input['admin_notifications'][$email_key]);
                } else {
                    $sanitized['admin_notifications'][$email_key] = '';
                }
            }

            // Sanitize notification toggles
            foreach ($input['admin_notifications'] as $key => $notification) {
                if (in_array($key, $allowed_admin_notifications, true)) {
                    $sanitized['admin_notifications'][$key] = array(
                        'enabled' => !empty($notification['enabled']) ? '1' : '0'
                    );
                }
            }
        }

        // Sanitize SMTP settings
        $sanitized['smtp'] = array();
        if (isset($input['smtp']) && is_array($input['smtp'])) {
            $sanitized['smtp']['enabled'] = !empty($input['smtp']['enabled']) ? '1' : '0';
            $sanitized['smtp']['host'] = isset($input['smtp']['host']) ? sanitize_text_field($input['smtp']['host']) : '';
            $sanitized['smtp']['port'] = isset($input['smtp']['port']) ? absint($input['smtp']['port']) : 587;
            $sanitized['smtp']['encryption'] = isset($input['smtp']['encryption']) && in_array($input['smtp']['encryption'], array('', 'ssl', 'tls'), true) ? $input['smtp']['encryption'] : 'tls';
            $sanitized['smtp']['auth'] = !empty($input['smtp']['auth']) ? '1' : '0';
            $sanitized['smtp']['default_account'] = isset($input['smtp']['default_account']) ? sanitize_email($input['smtp']['default_account']) : '';

            // Sanitize accounts
            $sanitized['smtp']['accounts'] = array();
            if (isset($input['smtp']['accounts']) && is_array($input['smtp']['accounts'])) {
                $account_index = 0;
                foreach ($input['smtp']['accounts'] as $account) {
                    $email = isset($account['email']) ? sanitize_email($account['email']) : '';
                    // Only save accounts with valid email
                    if (!empty($email)) {
                        $sanitized['smtp']['accounts'][$account_index] = array(
                            'email' => $email,
                            'name' => isset($account['name']) ? sanitize_text_field($account['name']) : '',
                            'username' => isset($account['username']) ? sanitize_text_field($account['username']) : '',
                            'password' => isset($account['password']) ? $account['password'] : '' // Don't sanitize password to preserve special chars
                        );
                        $account_index++;
                    }
                }
            }
        }

        return $sanitized;
    }

    /**
     * Render settings page
     */
    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        // Get current settings
        $settings = get_option('post_notifications_settings', array());
        $enabled_notifications = isset($settings['enabled_notifications']) ? $settings['enabled_notifications'] : array();
        $recipient_roles = isset($settings['recipient_roles']) ? $settings['recipient_roles'] : array('administrator');
        $recipient_users = isset($settings['recipient_users']) ? $settings['recipient_users'] : array();
        $enabled_post_types = isset($settings['enabled_post_types']) ? $settings['enabled_post_types'] : array('post');

        // Get active tab
        $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'posts';

        // Include the view
        include POST_NOTIFICATIONS_PLUGIN_DIR . 'includes/admin/views/settings-page.php';
    }
}
