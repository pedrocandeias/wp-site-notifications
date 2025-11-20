<?php
/**
 * Email Template Builder
 *
 * @package Post_Notifications
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Post_Notifications_Email
 */
class Post_Notifications_Email {

    /**
     * Get email subject
     */
    public function get_email_subject($notification_type, $post) {
        $site_name = get_bloginfo('name');
        $post_title = $post->post_title ? $post->post_title : __('(no title)', 'wp-site-notifications');

        // Security: Remove newlines from title to prevent email header injection
        $post_title = str_replace(array("\r", "\n", "\r\n"), ' ', $post_title);

        $subjects = array(
            'pending' => sprintf(__('[%s] New post pending review: %s', 'wp-site-notifications'), $site_name, $post_title),
            'published' => sprintf(__('[%s] Post published: %s', 'wp-site-notifications'), $site_name, $post_title),
            'draft' => sprintf(__('[%s] Post saved as draft: %s', 'wp-site-notifications'), $site_name, $post_title),
            'scheduled' => sprintf(__('[%s] Post scheduled: %s', 'wp-site-notifications'), $site_name, $post_title),
            'updated' => sprintf(__('[%s] Post updated: %s', 'wp-site-notifications'), $site_name, $post_title),
            'trashed' => sprintf(__('[%s] Post trashed: %s', 'wp-site-notifications'), $site_name, $post_title),
        );

        return isset($subjects[$notification_type]) ? $subjects[$notification_type] : sprintf(__('[%s] Post notification', 'wp-site-notifications'), $site_name);
    }

    /**
     * Get email message
     */
    public function get_email_message($notification_type, $post, $author_name) {
        $post_title = $post->post_title ? $post->post_title : __('(no title)', 'wp-site-notifications');
        $post_link = esc_url(get_permalink($post->ID));
        $edit_link = esc_url(get_edit_post_link($post->ID));
        $site_name = esc_html(get_bloginfo('name'));
        $site_url = esc_url(get_bloginfo('url'));

        // Get post type label
        $post_type_obj = get_post_type_object($post->post_type);
        $post_type_label = $post_type_obj ? $post_type_obj->labels->singular_name : $post->post_type;

        $messages = array(
            'pending' => $this->build_pending_message($site_url, $site_name, $post_title, $post_type_label, $author_name, $edit_link),
            'published' => $this->build_published_message($site_url, $site_name, $post_title, $post_type_label, $author_name, $post_link, $edit_link),
            'draft' => $this->build_draft_message($site_url, $site_name, $post_title, $post_type_label, $author_name, $edit_link),
            'scheduled' => $this->build_scheduled_message($site_url, $site_name, $post_title, $post_type_label, $author_name, $post, $edit_link),
            'updated' => $this->build_updated_message($site_url, $site_name, $post_title, $post_type_label, $author_name, $post_link, $edit_link),
            'trashed' => $this->build_trashed_message($site_url, $site_name, $post_title, $post_type_label, $author_name, $post),
        );

        $message = isset($messages[$notification_type]) ? $messages[$notification_type] : '';

        // Wrap in basic HTML template
        return $this->wrap_html_template($message);
    }

    /**
     * Build pending notification message
     */
    private function build_pending_message($site_url, $site_name, $post_title, $post_type_label, $author_name, $edit_link) {
        return sprintf(
            __('<p>A new post has been submitted for review on <a href="%s">%s</a>.</p>', 'wp-site-notifications'),
            $site_url,
            $site_name
        ) . sprintf(
            __('<p><strong>Title:</strong> %s</p>', 'wp-site-notifications'),
            esc_html($post_title)
        ) . sprintf(
            __('<p><strong>Type:</strong> %s</p>', 'wp-site-notifications'),
            esc_html($post_type_label)
        ) . sprintf(
            __('<p><strong>Author:</strong> %s</p>', 'wp-site-notifications'),
            esc_html($author_name)
        ) . sprintf(
            __('<p><strong>Status:</strong> Pending Review</p>', 'wp-site-notifications')
        ) . sprintf(
            __('<p><a href="%s">Review and approve this post</a></p>', 'wp-site-notifications'),
            $edit_link
        );
    }

    /**
     * Build published notification message
     */
    private function build_published_message($site_url, $site_name, $post_title, $post_type_label, $author_name, $post_link, $edit_link) {
        return sprintf(
            __('<p>A post has been published on <a href="%s">%s</a>.</p>', 'wp-site-notifications'),
            $site_url,
            $site_name
        ) . sprintf(
            __('<p><strong>Title:</strong> %s</p>', 'wp-site-notifications'),
            esc_html($post_title)
        ) . sprintf(
            __('<p><strong>Type:</strong> %s</p>', 'wp-site-notifications'),
            esc_html($post_type_label)
        ) . sprintf(
            __('<p><strong>Author:</strong> %s</p>', 'wp-site-notifications'),
            esc_html($author_name)
        ) . sprintf(
            __('<p><a href="%s">View post</a> | <a href="%s">Edit post</a></p>', 'wp-site-notifications'),
            $post_link,
            $edit_link
        );
    }

    /**
     * Build draft notification message
     */
    private function build_draft_message($site_url, $site_name, $post_title, $post_type_label, $author_name, $edit_link) {
        return sprintf(
            __('<p>A post has been saved as draft on <a href="%s">%s</a>.</p>', 'wp-site-notifications'),
            $site_url,
            $site_name
        ) . sprintf(
            __('<p><strong>Title:</strong> %s</p>', 'wp-site-notifications'),
            esc_html($post_title)
        ) . sprintf(
            __('<p><strong>Type:</strong> %s</p>', 'wp-site-notifications'),
            esc_html($post_type_label)
        ) . sprintf(
            __('<p><strong>Author:</strong> %s</p>', 'wp-site-notifications'),
            esc_html($author_name)
        ) . sprintf(
            __('<p><a href="%s">Edit draft</a></p>', 'wp-site-notifications'),
            $edit_link
        );
    }

    /**
     * Build scheduled notification message
     */
    private function build_scheduled_message($site_url, $site_name, $post_title, $post_type_label, $author_name, $post, $edit_link) {
        return sprintf(
            __('<p>A post has been scheduled for publication on <a href="%s">%s</a>.</p>', 'wp-site-notifications'),
            $site_url,
            $site_name
        ) . sprintf(
            __('<p><strong>Title:</strong> %s</p>', 'wp-site-notifications'),
            esc_html($post_title)
        ) . sprintf(
            __('<p><strong>Type:</strong> %s</p>', 'wp-site-notifications'),
            esc_html($post_type_label)
        ) . sprintf(
            __('<p><strong>Author:</strong> %s</p>', 'wp-site-notifications'),
            esc_html($author_name)
        ) . sprintf(
            __('<p><strong>Scheduled for:</strong> %s</p>', 'wp-site-notifications'),
            esc_html(get_the_date('', $post) . ' ' . get_the_time('', $post))
        ) . sprintf(
            __('<p><a href="%s">Edit post</a></p>', 'wp-site-notifications'),
            $edit_link
        );
    }

    /**
     * Build updated notification message
     */
    private function build_updated_message($site_url, $site_name, $post_title, $post_type_label, $author_name, $post_link, $edit_link) {
        return sprintf(
            __('<p>A published post has been updated on <a href="%s">%s</a>.</p>', 'wp-site-notifications'),
            $site_url,
            $site_name
        ) . sprintf(
            __('<p><strong>Title:</strong> %s</p>', 'wp-site-notifications'),
            esc_html($post_title)
        ) . sprintf(
            __('<p><strong>Type:</strong> %s</p>', 'wp-site-notifications'),
            esc_html($post_type_label)
        ) . sprintf(
            __('<p><strong>Author:</strong> %s</p>', 'wp-site-notifications'),
            esc_html($author_name)
        ) . sprintf(
            __('<p><a href="%s">View post</a> | <a href="%s">Edit post</a></p>', 'wp-site-notifications'),
            $post_link,
            $edit_link
        );
    }

    /**
     * Build trashed notification message
     */
    private function build_trashed_message($site_url, $site_name, $post_title, $post_type_label, $author_name, $post) {
        return sprintf(
            __('<p>A post has been moved to trash on <a href="%s">%s</a>.</p>', 'wp-site-notifications'),
            $site_url,
            $site_name
        ) . sprintf(
            __('<p><strong>Title:</strong> %s</p>', 'wp-site-notifications'),
            esc_html($post_title)
        ) . sprintf(
            __('<p><strong>Type:</strong> %s</p>', 'wp-site-notifications'),
            esc_html($post_type_label)
        ) . sprintf(
            __('<p><strong>Author:</strong> %s</p>', 'wp-site-notifications'),
            esc_html($author_name)
        ) . sprintf(
            __('<p><a href="%s">View trashed posts</a></p>', 'wp-site-notifications'),
            esc_url(admin_url('edit.php?post_status=trash&post_type=' . $post->post_type))
        );
    }

    /**
     * Wrap message in HTML template
     */
    private function wrap_html_template($message) {
        $html_message = '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body>';
        $html_message .= $message;
        $html_message .= '<hr><p style="font-size: 12px; color: #666;">';
        $html_message .= __('This is an automated notification from WP Site Notifications plugin.', 'wp-site-notifications');
        $html_message .= '</p></body></html>';

        return $html_message;
    }
}
