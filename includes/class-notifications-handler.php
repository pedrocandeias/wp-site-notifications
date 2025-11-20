<?php
/**
 * Notifications Handler
 *
 * @package Post_Notifications
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Post_Notifications_Handler
 */
class Post_Notifications_Handler {

    /**
     * Constructor
     */
    public function __construct() {
        add_action('transition_post_status', array($this, 'handle_post_status_change'), 10, 3);
    }

    /**
     * Handle post status changes
     */
    public function handle_post_status_change($new_status, $old_status, $post) {
        // Avoid sending notifications during autosave or revisions
        if (wp_is_post_autosave($post) || wp_is_post_revision($post)) {
            return;
        }

        // Get settings
        $settings = get_option('post_notifications_settings', array());
        $enabled_notifications = isset($settings['enabled_notifications']) ? $settings['enabled_notifications'] : array();
        $recipient_roles = isset($settings['recipient_roles']) ? $settings['recipient_roles'] : array();
        $recipient_users = isset($settings['recipient_users']) ? $settings['recipient_users'] : array();
        $enabled_post_types = isset($settings['enabled_post_types']) ? $settings['enabled_post_types'] : array('post');

        // Only handle enabled post types
        if (!in_array($post->post_type, $enabled_post_types, true)) {
            return;
        }

        // Check if we should send notification for this status change
        $notification_type = null;

        if ($old_status !== 'pending' && $new_status === 'pending' && isset($enabled_notifications['pending']) && $enabled_notifications['pending'] === '1') {
            $notification_type = 'pending';
        } elseif ($old_status !== 'publish' && $new_status === 'publish' && isset($enabled_notifications['published']) && $enabled_notifications['published'] === '1') {
            $notification_type = 'published';
        } elseif ($new_status === 'draft' && $old_status !== 'draft' && $old_status !== 'auto-draft' && isset($enabled_notifications['draft']) && $enabled_notifications['draft'] === '1') {
            $notification_type = 'draft';
        } elseif ($new_status === 'future' && $old_status !== 'future' && isset($enabled_notifications['scheduled']) && $enabled_notifications['scheduled'] === '1') {
            $notification_type = 'scheduled';
        } elseif ($old_status === 'publish' && $new_status === 'publish' && isset($enabled_notifications['updated']) && $enabled_notifications['updated'] === '1') {
            // Rate limit updated notifications - only send once per hour per post
            $last_notification = get_transient('post_notification_sent_' . $post->ID);
            if ($last_notification !== false) {
                return; // Already sent notification recently
            }
            $notification_type = 'updated';
            set_transient('post_notification_sent_' . $post->ID, time(), HOUR_IN_SECONDS);
        } elseif ($new_status === 'trash' && isset($enabled_notifications['trashed']) && $enabled_notifications['trashed'] === '1') {
            $notification_type = 'trashed';
        }

        // Send notification if applicable
        if ($notification_type && (!empty($recipient_roles) || !empty($recipient_users))) {
            $this->send_notification($post, $notification_type, $recipient_roles, $recipient_users);
        }
    }

    /**
     * Send notification email
     */
    private function send_notification($post, $notification_type, $recipient_roles, $recipient_users = array()) {
        // Get recipients from roles
        $recipients = $this->get_users_by_roles($recipient_roles);

        // Add individual users
        if (!empty($recipient_users)) {
            $individual_users = $this->get_users_by_ids($recipient_users);
            $recipients = array_merge($recipients, $individual_users);
        }

        // Remove duplicates based on user ID
        $unique_recipients = array();
        $user_ids = array();
        foreach ($recipients as $recipient) {
            if (!in_array($recipient->ID, $user_ids, true)) {
                $unique_recipients[] = $recipient;
                $user_ids[] = $recipient->ID;
            }
        }

        if (empty($unique_recipients)) {
            return;
        }

        $recipients = $unique_recipients;

        // Get post author
        $author = get_userdata($post->post_author);
        $author_name = $author ? $author->display_name : __('Unknown', 'wp-site-notifications');

        // Build email content
        $email_builder = new Post_Notifications_Email();
        $subject = $email_builder->get_email_subject($notification_type, $post);
        $message = $email_builder->get_email_message($notification_type, $post, $author_name);

        // Allow filtering of subject and message
        $subject = apply_filters('post_notifications_email_subject', $subject, $notification_type, $post);
        $message = apply_filters('post_notifications_email_message', $message, $notification_type, $post, $author_name);
        $recipients = apply_filters('post_notifications_recipients', $recipients, $notification_type, $post);

        // Set email headers
        $headers = array('Content-Type: text/html; charset=UTF-8');

        // Send email to each recipient
        foreach ($recipients as $recipient) {
            $result = wp_mail($recipient->user_email, $subject, $message, $headers);
            if (!$result) {
                error_log('WP Site Notifications: Failed to send email to ' . $recipient->user_email);
            }
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
     * Get users by IDs
     */
    private function get_users_by_ids($user_ids) {
        $users = array();

        if (empty($user_ids)) {
            return $users;
        }

        foreach ($user_ids as $user_id) {
            $user_id = absint($user_id);
            if ($user_id > 0) {
                $user = get_userdata($user_id);
                if ($user) {
                    // Create user object with same structure as get_users output
                    $user_obj = new stdClass();
                    $user_obj->ID = $user->ID;
                    $user_obj->user_email = $user->user_email;
                    $user_obj->display_name = $user->display_name;
                    $users[] = $user_obj;
                }
            }
        }

        return $users;
    }
}
