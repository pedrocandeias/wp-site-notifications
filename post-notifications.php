<?php
/**
 * Plugin Name: WP Site Notifications
 * Plugin URI: https://github.com/pedrocandeias/wp-site-notifications
 * Description: Send customizable email notifications to selected user roles when post status changes (pending, published, etc.)
 * Version: 1.0.0
 * Author: Pedro Candeias
 * Author URI: https://pedrocandeias.net
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wp-site-notifications
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
if (!defined('POST_NOTIFICATIONS_VERSION')) {
    define('POST_NOTIFICATIONS_VERSION', '1.0.0');
}
if (!defined('POST_NOTIFICATIONS_PLUGIN_DIR')) {
    define('POST_NOTIFICATIONS_PLUGIN_DIR', plugin_dir_path(__FILE__));
}
if (!defined('POST_NOTIFICATIONS_PLUGIN_URL')) {
    define('POST_NOTIFICATIONS_PLUGIN_URL', plugin_dir_url(__FILE__));
}

/**
 * Main Plugin Class
 */
class Post_Notifications {

    private static $instance = null;
    private $settings;
    private $notifications_handler;
    private $admin_notifications_handler;
    private $smtp;

    /**
     * Get singleton instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->load_dependencies();
        $this->init_hooks();
        $this->init_components();
    }

    /**
     * Load required dependencies
     */
    private function load_dependencies() {
        // Core classes
        require_once POST_NOTIFICATIONS_PLUGIN_DIR . 'includes/class-email.php';
        require_once POST_NOTIFICATIONS_PLUGIN_DIR . 'includes/class-notifications-handler.php';
        require_once POST_NOTIFICATIONS_PLUGIN_DIR . 'includes/class-admin-notifications-handler.php';
        require_once POST_NOTIFICATIONS_PLUGIN_DIR . 'includes/class-smtp.php';

        // Admin classes
        if (is_admin()) {
            require_once POST_NOTIFICATIONS_PLUGIN_DIR . 'includes/admin/class-settings.php';
        }
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Load text domain for translations
        add_action('plugins_loaded', array($this, 'load_textdomain'));

        // Activation and uninstall hooks
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_uninstall_hook(__FILE__, array('Post_Notifications', 'uninstall'));
    }

    /**
     * Initialize plugin components
     */
    private function init_components() {
        // Initialize SMTP handler (must be early to hook into phpmailer_init)
        $this->smtp = new WP_Site_Notifications_SMTP();

        // Initialize notifications handler
        $this->notifications_handler = new Post_Notifications_Handler();

        // Initialize admin notifications handler
        $this->admin_notifications_handler = new Admin_Notifications_Handler();

        // Initialize settings page if in admin
        if (is_admin()) {
            $this->settings = new Post_Notifications_Settings();
        }
    }

    /**
     * Load plugin text domain for translations
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'wp-site-notifications',
            false,
            dirname(plugin_basename(__FILE__)) . '/languages/'
        );
    }

    /**
     * Plugin activation
     */
    public function activate() {
        // Set default options
        $default_settings = array(
            'enabled_notifications' => array(
                'pending' => '1',
                'published' => '1',
                'draft' => '0',
                'scheduled' => '1',
                'updated' => '0',
            ),
            'recipient_roles' => array('administrator'),
            'recipient_users' => array(), // Individual users
            'enabled_post_types' => array('post'), // Default to standard posts
            'admin_notifications' => array(
                // Group emails
                'user_management_email' => '',
                'plugin_management_email' => '',
                'theme_management_email' => '',
                'core_updates_email' => '',
                'security_email' => '',
                // Notification toggles
                'user_registered' => array('enabled' => '0'),
                'user_deleted' => array('enabled' => '0'),
                'user_role_changed' => array('enabled' => '0'),
                'plugin_activated' => array('enabled' => '0'),
                'plugin_deactivated' => array('enabled' => '0'),
                'plugin_updated' => array('enabled' => '0'),
                'theme_switched' => array('enabled' => '0'),
                'theme_updated' => array('enabled' => '0'),
                'core_updated' => array('enabled' => '0'),
                'failed_login' => array('enabled' => '0'),
                'password_reset' => array('enabled' => '0'),
            ),
            'smtp' => array(
                'enabled' => '0',
                'host' => '',
                'port' => 587,
                'encryption' => 'tls',
                'auth' => '1',
                'default_account' => '',
                'accounts' => array(),
            ),
        );

        if (!get_option('post_notifications_settings')) {
            add_option('post_notifications_settings', $default_settings);
        }
    }

    /**
     * Plugin uninstall - cleanup
     */
    public static function uninstall() {
        delete_option('post_notifications_settings');
    }
}

/**
 * Initialize plugin
 */
function post_notifications_init() {
    return Post_Notifications::get_instance();
}

// Start the plugin
add_action('plugins_loaded', 'post_notifications_init');
