<?php
/**
 * Plugin Name: Post Notifications
 * Plugin URI: https://github.com/pedrocandeias/post-notifications
 * Description: Send customizable email notifications to selected user roles when post status changes (pending, published, etc.)
 * Version: 1.0.0
 * Author: Pedro Candeias
 * Author URI: https://pedrocandeias.net
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: post-notifications
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
        $this->init_hooks();
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Load text domain for translations
        add_action('plugins_loaded', array($this, 'load_textdomain'));

        // Admin hooks
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));

        // Post status transition hooks
        add_action('transition_post_status', array($this, 'handle_post_status_change'), 10, 3);

        // Activation and uninstall hooks
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_uninstall_hook(__FILE__, array('Post_Notifications', 'uninstall'));
    }

    /**
     * Load plugin text domain for translations
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'post-notifications',
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

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_options_page(
            __('Post Notifications Settings', 'post-notifications'),
            __('Post Notifications', 'post-notifications'),
            'manage_options',
            'post-notifications',
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
                __('Security check failed. Please try again.', 'post-notifications'),
                'error'
            );
            return get_option('post_notifications_settings', array());
        }

        // Verify user has permission
        if (!current_user_can('manage_options')) {
            add_settings_error(
                'post_notifications_settings',
                'permission_denied',
                __('You do not have permission to modify these settings.', 'post-notifications'),
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

        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

            <?php settings_errors('post_notifications_settings'); ?>

            <form method="post" action="options.php">
                <?php
                settings_fields('post_notifications_settings_group');
                ?>

                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row">
                            <label><?php _e('Notification Types', 'post-notifications'); ?></label>
                        </th>
                        <td>
                            <fieldset>
                                <legend class="screen-reader-text">
                                    <span><?php _e('Notification Types', 'post-notifications'); ?></span>
                                </legend>

                                <label>
                                    <input type="checkbox"
                                           name="post_notifications_settings[enabled_notifications][pending]"
                                           value="1"
                                           <?php checked(isset($enabled_notifications['pending']) && $enabled_notifications['pending'] == '1'); ?>>
                                    <?php _e('Post submitted for review (Pending)', 'post-notifications'); ?>
                                </label><br>

                                <label>
                                    <input type="checkbox"
                                           name="post_notifications_settings[enabled_notifications][published]"
                                           value="1"
                                           <?php checked(isset($enabled_notifications['published']) && $enabled_notifications['published'] == '1'); ?>>
                                    <?php _e('Post published', 'post-notifications'); ?>
                                </label><br>

                                <label>
                                    <input type="checkbox"
                                           name="post_notifications_settings[enabled_notifications][draft]"
                                           value="1"
                                           <?php checked(isset($enabled_notifications['draft']) && $enabled_notifications['draft'] == '1'); ?>>
                                    <?php _e('Post saved as draft', 'post-notifications'); ?>
                                </label><br>

                                <label>
                                    <input type="checkbox"
                                           name="post_notifications_settings[enabled_notifications][scheduled]"
                                           value="1"
                                           <?php checked(isset($enabled_notifications['scheduled']) && $enabled_notifications['scheduled'] == '1'); ?>>
                                    <?php _e('Post scheduled for future publication', 'post-notifications'); ?>
                                </label><br>

                                <label>
                                    <input type="checkbox"
                                           name="post_notifications_settings[enabled_notifications][updated]"
                                           value="1"
                                           <?php checked(isset($enabled_notifications['updated']) && $enabled_notifications['updated'] == '1'); ?>>
                                    <?php _e('Published post updated', 'post-notifications'); ?>
                                </label><br>

                                <label>
                                    <input type="checkbox"
                                           name="post_notifications_settings[enabled_notifications][trashed]"
                                           value="1"
                                           <?php checked(isset($enabled_notifications['trashed']) && $enabled_notifications['trashed'] == '1'); ?>>
                                    <?php _e('Post moved to trash', 'post-notifications'); ?>
                                </label>
                            </fieldset>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label><?php _e('Recipient Roles', 'post-notifications'); ?></label>
                        </th>
                        <td>
                            <fieldset>
                                <legend class="screen-reader-text">
                                    <span><?php _e('Recipient Roles', 'post-notifications'); ?></span>
                                </legend>
                                <?php
                                // Get all roles including custom ones
                                $available_roles = wp_roles()->get_names();
                                $wp_roles_obj = wp_roles();

                                // WordPress built-in roles
                                $builtin_roles = array('administrator', 'editor', 'author', 'contributor', 'subscriber');

                                // Separate built-in and custom roles
                                $builtin_list = array();
                                $custom_list = array();

                                foreach ($available_roles as $role_key => $role_name) {
                                    if (in_array($role_key, $builtin_roles, true)) {
                                        $builtin_list[$role_key] = $role_name;
                                    } else {
                                        $custom_list[$role_key] = $role_name;
                                    }
                                }

                                // Display built-in roles
                                if (!empty($builtin_list)) :
                                ?>
                                    <div style="margin-bottom: 15px;">
                                        <strong style="display: block; margin-bottom: 8px;"><?php _e('WordPress Built-in Roles:', 'post-notifications'); ?></strong>
                                        <?php foreach ($builtin_list as $role_key => $role_name) :
                                            $role_obj = $wp_roles_obj->get_role($role_key);
                                            $user_count = count(get_users(array('role' => $role_key, 'fields' => 'ID')));
                                        ?>
                                            <label style="display: block; margin-bottom: 5px;">
                                                <input type="checkbox"
                                                       name="post_notifications_settings[recipient_roles][]"
                                                       value="<?php echo esc_attr($role_key); ?>"
                                                       <?php checked(in_array($role_key, $recipient_roles)); ?>>
                                                <?php echo esc_html($role_name); ?>
                                                <span style="color: #666; font-size: 0.9em;">
                                                    (<?php echo esc_html(sprintf(_n('%d user', '%d users', $user_count, 'post-notifications'), $user_count)); ?>)
                                                </span>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>

                                <?php
                                // Display custom roles
                                if (!empty($custom_list)) :
                                ?>
                                    <div style="margin-bottom: 10px; padding: 10px; background: #f0f8ff; border-left: 4px solid #0073aa;">
                                        <strong style="display: block; margin-bottom: 8px;">
                                            <?php _e('Custom Roles:', 'post-notifications'); ?>
                                            <span style="color: #0073aa; font-weight: normal; font-size: 0.9em;">
                                                <?php _e('(Added by plugins or theme)', 'post-notifications'); ?>
                                            </span>
                                        </strong>
                                        <?php foreach ($custom_list as $role_key => $role_name) :
                                            $role_obj = $wp_roles_obj->get_role($role_key);
                                            $user_count = count(get_users(array('role' => $role_key, 'fields' => 'ID')));
                                        ?>
                                            <label style="display: block; margin-bottom: 5px;">
                                                <input type="checkbox"
                                                       name="post_notifications_settings[recipient_roles][]"
                                                       value="<?php echo esc_attr($role_key); ?>"
                                                       <?php checked(in_array($role_key, $recipient_roles)); ?>>
                                                <?php echo esc_html($role_name); ?>
                                                <span style="color: #666; font-size: 0.9em;">
                                                    (<?php echo esc_html(sprintf(_n('%d user', '%d users', $user_count, 'post-notifications'), $user_count)); ?>)
                                                </span>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>

                                <p class="description">
                                    <?php _e('Select which user roles should receive email notifications. Custom roles are automatically detected.', 'post-notifications'); ?>
                                </p>
                            </fieldset>
                        </td>
                    </tr>
                </table>

                <?php submit_button(__('Save Settings', 'post-notifications')); ?>
            </form>
        </div>
        <?php
    }

    /**
     * Handle post status changes
     */
    public function handle_post_status_change($new_status, $old_status, $post) {
        // Only handle posts (not pages or other post types)
        if ($post->post_type !== 'post') {
            return;
        }

        // Avoid sending notifications during autosave or revisions
        if (wp_is_post_autosave($post) || wp_is_post_revision($post)) {
            return;
        }

        // Get settings
        $settings = get_option('post_notifications_settings', array());
        $enabled_notifications = isset($settings['enabled_notifications']) ? $settings['enabled_notifications'] : array();
        $recipient_roles = isset($settings['recipient_roles']) ? $settings['recipient_roles'] : array();

        // Check if we should send notification for this status change
        $notification_type = null;

        if ($old_status !== 'pending' && $new_status === 'pending' && isset($enabled_notifications['pending']) && $enabled_notifications['pending'] == '1') {
            $notification_type = 'pending';
        } elseif ($old_status !== 'publish' && $new_status === 'publish' && isset($enabled_notifications['published']) && $enabled_notifications['published'] == '1') {
            $notification_type = 'published';
        } elseif ($new_status === 'draft' && $old_status !== 'draft' && $old_status !== 'auto-draft' && isset($enabled_notifications['draft']) && $enabled_notifications['draft'] == '1') {
            $notification_type = 'draft';
        } elseif ($new_status === 'future' && $old_status !== 'future' && isset($enabled_notifications['scheduled']) && $enabled_notifications['scheduled'] == '1') {
            $notification_type = 'scheduled';
        } elseif ($old_status === 'publish' && $new_status === 'publish' && isset($enabled_notifications['updated']) && $enabled_notifications['updated'] == '1') {
            // Rate limit updated notifications - only send once per hour per post
            $last_notification = get_transient('post_notification_sent_' . $post->ID);
            if ($last_notification !== false) {
                return; // Already sent notification recently
            }
            $notification_type = 'updated';
            set_transient('post_notification_sent_' . $post->ID, time(), HOUR_IN_SECONDS);
        } elseif ($new_status === 'trash' && isset($enabled_notifications['trashed']) && $enabled_notifications['trashed'] == '1') {
            $notification_type = 'trashed';
        }

        // Send notification if applicable
        if ($notification_type && !empty($recipient_roles)) {
            $this->send_notification($post, $notification_type, $recipient_roles);
        }
    }

    /**
     * Send notification email
     */
    private function send_notification($post, $notification_type, $recipient_roles) {
        // Get recipients
        $recipients = $this->get_users_by_roles($recipient_roles);

        if (empty($recipients)) {
            return;
        }

        // Get post author
        $author = get_userdata($post->post_author);
        $author_name = $author ? $author->display_name : __('Unknown', 'post-notifications');

        // Build email content
        $subject = $this->get_email_subject($notification_type, $post);
        $message = $this->get_email_message($notification_type, $post, $author_name);

        // Allow filtering of subject and message
        $subject = apply_filters('post_notifications_email_subject', $subject, $notification_type, $post);
        $message = apply_filters('post_notifications_email_message', $message, $notification_type, $post, $author_name);
        $recipients = apply_filters('post_notifications_recipients', $recipients, $notification_type, $post);

        // Set email headers
        $headers = array('Content-Type: text/html; charset=UTF-8');

        // Send email to each recipient
        foreach ($recipients as $recipient) {
            wp_mail($recipient->user_email, $subject, $message, $headers);
        }
    }

    /**
     * Get users by roles
     */
    private function get_users_by_roles($roles) {
        $users = array();

        // Validate roles against actual WordPress roles
        $available_roles = array_keys(wp_roles()->get_names());

        foreach ($roles as $role) {
            // Security: Only query for valid roles
            if (!in_array($role, $available_roles, true)) {
                continue;
            }

            $role_users = get_users(array(
                'role' => $role,
                'fields' => array('ID', 'user_email', 'display_name'),
            ));

            $users = array_merge($users, $role_users);
        }

        // Remove duplicates based on user ID
        $unique_users = array();
        $user_ids = array();

        foreach ($users as $user) {
            if (!in_array($user->ID, $user_ids, true)) {
                $unique_users[] = $user;
                $user_ids[] = $user->ID;
            }
        }

        return $unique_users;
    }

    /**
     * Get email subject
     */
    private function get_email_subject($notification_type, $post) {
        $site_name = get_bloginfo('name');
        $post_title = $post->post_title ? $post->post_title : __('(no title)', 'post-notifications');

        // Security: Remove newlines from title to prevent email header injection
        $post_title = str_replace(array("\r", "\n", "\r\n"), ' ', $post_title);

        $subjects = array(
            'pending' => sprintf(__('[%s] New post pending review: %s', 'post-notifications'), $site_name, $post_title),
            'published' => sprintf(__('[%s] Post published: %s', 'post-notifications'), $site_name, $post_title),
            'draft' => sprintf(__('[%s] Post saved as draft: %s', 'post-notifications'), $site_name, $post_title),
            'scheduled' => sprintf(__('[%s] Post scheduled: %s', 'post-notifications'), $site_name, $post_title),
            'updated' => sprintf(__('[%s] Post updated: %s', 'post-notifications'), $site_name, $post_title),
            'trashed' => sprintf(__('[%s] Post trashed: %s', 'post-notifications'), $site_name, $post_title),
        );

        return isset($subjects[$notification_type]) ? $subjects[$notification_type] : sprintf(__('[%s] Post notification', 'post-notifications'), $site_name);
    }

    /**
     * Get email message
     */
    private function get_email_message($notification_type, $post, $author_name) {
        $post_title = $post->post_title ? $post->post_title : __('(no title)', 'post-notifications');
        $post_link = esc_url(get_permalink($post->ID));
        $edit_link = esc_url(get_edit_post_link($post->ID));
        $site_name = esc_html(get_bloginfo('name'));
        $site_url = esc_url(get_bloginfo('url'));

        $messages = array(
            'pending' => sprintf(
                __('<p>A new post has been submitted for review on <a href="%s">%s</a>.</p>', 'post-notifications'),
                $site_url,
                $site_name
            ) . sprintf(
                __('<p><strong>Title:</strong> %s</p>', 'post-notifications'),
                esc_html($post_title)
            ) . sprintf(
                __('<p><strong>Author:</strong> %s</p>', 'post-notifications'),
                esc_html($author_name)
            ) . sprintf(
                __('<p><strong>Status:</strong> Pending Review</p>', 'post-notifications')
            ) . sprintf(
                __('<p><a href="%s">Review and approve this post</a></p>', 'post-notifications'),
                $edit_link
            ),

            'published' => sprintf(
                __('<p>A post has been published on <a href="%s">%s</a>.</p>', 'post-notifications'),
                $site_url,
                $site_name
            ) . sprintf(
                __('<p><strong>Title:</strong> %s</p>', 'post-notifications'),
                esc_html($post_title)
            ) . sprintf(
                __('<p><strong>Author:</strong> %s</p>', 'post-notifications'),
                esc_html($author_name)
            ) . sprintf(
                __('<p><a href="%s">View post</a> | <a href="%s">Edit post</a></p>', 'post-notifications'),
                $post_link,
                $edit_link
            ),

            'draft' => sprintf(
                __('<p>A post has been saved as draft on <a href="%s">%s</a>.</p>', 'post-notifications'),
                $site_url,
                $site_name
            ) . sprintf(
                __('<p><strong>Title:</strong> %s</p>', 'post-notifications'),
                esc_html($post_title)
            ) . sprintf(
                __('<p><strong>Author:</strong> %s</p>', 'post-notifications'),
                esc_html($author_name)
            ) . sprintf(
                __('<p><a href="%s">Edit draft</a></p>', 'post-notifications'),
                $edit_link
            ),

            'scheduled' => sprintf(
                __('<p>A post has been scheduled for publication on <a href="%s">%s</a>.</p>', 'post-notifications'),
                $site_url,
                $site_name
            ) . sprintf(
                __('<p><strong>Title:</strong> %s</p>', 'post-notifications'),
                esc_html($post_title)
            ) . sprintf(
                __('<p><strong>Author:</strong> %s</p>', 'post-notifications'),
                esc_html($author_name)
            ) . sprintf(
                __('<p><strong>Scheduled for:</strong> %s</p>', 'post-notifications'),
                esc_html(get_the_date('', $post) . ' ' . get_the_time('', $post))
            ) . sprintf(
                __('<p><a href="%s">Edit post</a></p>', 'post-notifications'),
                $edit_link
            ),

            'updated' => sprintf(
                __('<p>A published post has been updated on <a href="%s">%s</a>.</p>', 'post-notifications'),
                $site_url,
                $site_name
            ) . sprintf(
                __('<p><strong>Title:</strong> %s</p>', 'post-notifications'),
                esc_html($post_title)
            ) . sprintf(
                __('<p><strong>Author:</strong> %s</p>', 'post-notifications'),
                esc_html($author_name)
            ) . sprintf(
                __('<p><a href="%s">View post</a> | <a href="%s">Edit post</a></p>', 'post-notifications'),
                $post_link,
                $edit_link
            ),

            'trashed' => sprintf(
                __('<p>A post has been moved to trash on <a href="%s">%s</a>.</p>', 'post-notifications'),
                $site_url,
                $site_name
            ) . sprintf(
                __('<p><strong>Title:</strong> %s</p>', 'post-notifications'),
                esc_html($post_title)
            ) . sprintf(
                __('<p><strong>Author:</strong> %s</p>', 'post-notifications'),
                esc_html($author_name)
            ) . sprintf(
                __('<p><a href="%s">View trashed posts</a></p>', 'post-notifications'),
                esc_url(admin_url('edit.php?post_status=trash&post_type=post'))
            ),
        );

        $message = isset($messages[$notification_type]) ? $messages[$notification_type] : '';

        // Wrap in basic HTML template
        $html_message = '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body>';
        $html_message .= $message;
        $html_message .= '<hr><p style="font-size: 12px; color: #666;">';
        $html_message .= __('This is an automated notification from Post Notifications plugin.', 'post-notifications');
        $html_message .= '</p></body></html>';

        return $html_message;
    }
}

// Initialize plugin
function post_notifications_init() {
    return Post_Notifications::get_instance();
}

// Start the plugin
add_action('plugins_loaded', 'post_notifications_init');
