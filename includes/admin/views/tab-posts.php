<?php
/**
 * Posts Tab View
 *
 * @package Post_Notifications
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}
?>

<!-- Posts Tab Content -->
<table class="form-table" role="presentation">
    <tr>
        <th scope="row">
            <label><?php _e('Notification Types', 'wp-site-notifications'); ?></label>
        </th>
        <td>
            <fieldset>
                <legend class="screen-reader-text">
                    <span><?php _e('Notification Types', 'wp-site-notifications'); ?></span>
                </legend>

                <label>
                    <input type="checkbox"
                           name="post_notifications_settings[enabled_notifications][pending]"
                           value="1"
                           <?php checked(isset($enabled_notifications['pending']) && $enabled_notifications['pending'] == '1'); ?>>
                    <?php _e('Post submitted for review (Pending)', 'wp-site-notifications'); ?>
                </label><br>

                <label>
                    <input type="checkbox"
                           name="post_notifications_settings[enabled_notifications][published]"
                           value="1"
                           <?php checked(isset($enabled_notifications['published']) && $enabled_notifications['published'] == '1'); ?>>
                    <?php _e('Post published', 'wp-site-notifications'); ?>
                </label><br>

                <label>
                    <input type="checkbox"
                           name="post_notifications_settings[enabled_notifications][draft]"
                           value="1"
                           <?php checked(isset($enabled_notifications['draft']) && $enabled_notifications['draft'] == '1'); ?>>
                    <?php _e('Post saved as draft', 'wp-site-notifications'); ?>
                </label><br>

                <label>
                    <input type="checkbox"
                           name="post_notifications_settings[enabled_notifications][scheduled]"
                           value="1"
                           <?php checked(isset($enabled_notifications['scheduled']) && $enabled_notifications['scheduled'] == '1'); ?>>
                    <?php _e('Post scheduled for future publication', 'wp-site-notifications'); ?>
                </label><br>

                <label>
                    <input type="checkbox"
                           name="post_notifications_settings[enabled_notifications][updated]"
                           value="1"
                           <?php checked(isset($enabled_notifications['updated']) && $enabled_notifications['updated'] == '1'); ?>>
                    <?php _e('Published post updated', 'wp-site-notifications'); ?>
                </label><br>

                <label>
                    <input type="checkbox"
                           name="post_notifications_settings[enabled_notifications][trashed]"
                           value="1"
                           <?php checked(isset($enabled_notifications['trashed']) && $enabled_notifications['trashed'] == '1'); ?>>
                    <?php _e('Post moved to trash', 'wp-site-notifications'); ?>
                </label>
            </fieldset>
        </td>
    </tr>

    <tr>
        <th scope="row">
            <label><?php _e('Recipient Roles', 'wp-site-notifications'); ?></label>
        </th>
        <td>
            <fieldset>
                <legend class="screen-reader-text">
                    <span><?php _e('Recipient Roles', 'wp-site-notifications'); ?></span>
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
                        <strong style="display: block; margin-bottom: 8px;"><?php _e('WordPress Built-in Roles:', 'wp-site-notifications'); ?></strong>
                        <?php foreach ($builtin_list as $role_key => $role_name) :
                            $user_count = count(get_users(array('role' => $role_key, 'fields' => 'ID')));
                        ?>
                            <label style="display: block; margin-bottom: 5px;">
                                <input type="checkbox"
                                       name="post_notifications_settings[recipient_roles][]"
                                       value="<?php echo esc_attr($role_key); ?>"
                                       <?php checked(in_array($role_key, $recipient_roles)); ?>>
                                <?php echo esc_html($role_name); ?>
                                <span style="color: #666; font-size: 0.9em;">
                                    (<?php echo esc_html(sprintf(_n('%d user', '%d users', $user_count, 'wp-site-notifications'), $user_count)); ?>)
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
                            <?php _e('Custom Roles:', 'wp-site-notifications'); ?>
                            <span style="color: #0073aa; font-weight: normal; font-size: 0.9em;">
                                <?php _e('(Added by plugins or theme)', 'wp-site-notifications'); ?>
                            </span>
                        </strong>
                        <?php foreach ($custom_list as $role_key => $role_name) :
                            $user_count = count(get_users(array('role' => $role_key, 'fields' => 'ID')));
                        ?>
                            <label style="display: block; margin-bottom: 5px;">
                                <input type="checkbox"
                                       name="post_notifications_settings[recipient_roles][]"
                                       value="<?php echo esc_attr($role_key); ?>"
                                       <?php checked(in_array($role_key, $recipient_roles)); ?>>
                                <?php echo esc_html($role_name); ?>
                                <span style="color: #666; font-size: 0.9em;">
                                    (<?php echo esc_html(sprintf(_n('%d user', '%d users', $user_count, 'wp-site-notifications'), $user_count)); ?>)
                                </span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <p class="description">
                    <?php _e('Select which user roles should receive email notifications. Custom roles are automatically detected.', 'wp-site-notifications'); ?>
                </p>
            </fieldset>
        </td>
    </tr>

    <tr>
        <th scope="row">
            <label><?php _e('Individual Users', 'wp-site-notifications'); ?></label>
        </th>
        <td>
            <fieldset>
                <legend class="screen-reader-text">
                    <span><?php _e('Individual Users', 'wp-site-notifications'); ?></span>
                </legend>

                <input type="text"
                       id="post-notifications-user-search"
                       placeholder="<?php esc_attr_e('Search users...', 'wp-site-notifications'); ?>"
                       style="width: 100%; max-width: 400px; margin-bottom: 10px; padding: 5px;">

                <div id="post-notifications-user-list" style="max-height: 300px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; background: #fff;">
                    <?php
                    // Get all users ordered by display name
                    $all_users = get_users(array(
                        'orderby' => 'display_name',
                        'order' => 'ASC',
                        'fields' => array('ID', 'display_name', 'user_email', 'user_login')
                    ));

                    if (!empty($all_users)) :
                        foreach ($all_users as $user) :
                            $user_roles = get_userdata($user->ID)->roles;
                            $role_names = array();
                            foreach ($user_roles as $role_key) {
                                $role_obj = get_role($role_key);
                                if ($role_obj) {
                                    $wp_roles = wp_roles();
                                    $role_names[] = isset($wp_roles->role_names[$role_key]) ? translate_user_role($wp_roles->role_names[$role_key]) : $role_key;
                                }
                            }
                            $role_display = !empty($role_names) ? implode(', ', $role_names) : __('No role', 'wp-site-notifications');
                    ?>
                        <label class="post-notifications-user-item"
                               data-name="<?php echo esc_attr(strtolower($user->display_name)); ?>"
                               data-email="<?php echo esc_attr(strtolower($user->user_email)); ?>"
                               style="display: block; margin-bottom: 8px; padding: 5px; background: #f9f9f9; border-radius: 3px;">
                            <input type="checkbox"
                                   name="post_notifications_settings[recipient_users][]"
                                   value="<?php echo esc_attr($user->ID); ?>"
                                   <?php checked(in_array($user->ID, $recipient_users)); ?>>
                            <strong><?php echo esc_html($user->display_name); ?></strong>
                            <span style="color: #666; font-size: 0.9em;">
                                (<?php echo esc_html($user->user_email); ?>)
                            </span>
                            <br>
                            <span style="margin-left: 24px; color: #999; font-size: 0.85em;">
                                <?php echo esc_html($role_display); ?>
                            </span>
                        </label>
                    <?php
                        endforeach;
                    else :
                    ?>
                        <p><?php _e('No users found.', 'wp-site-notifications'); ?></p>
                    <?php endif; ?>
                </div>

                <p class="description" style="margin-top: 10px;">
                    <?php _e('Select individual users who should receive notifications in addition to users selected by role. These users will receive notifications regardless of their role.', 'wp-site-notifications'); ?>
                </p>
            </fieldset>
        </td>
    </tr>

    <tr>
        <th scope="row">
            <label><?php _e('Post Types', 'wp-site-notifications'); ?></label>
        </th>
        <td>
            <fieldset>
                <legend class="screen-reader-text">
                    <span><?php _e('Post Types', 'wp-site-notifications'); ?></span>
                </legend>
                <?php
                // Get all public post types
                $post_types = get_post_types(array('public' => true), 'objects');

                // WordPress built-in post types
                $builtin_types = array('post', 'page');

                // Separate built-in and custom post types
                $builtin_list = array();
                $custom_list = array();

                foreach ($post_types as $post_type_key => $post_type_obj) {
                    if (in_array($post_type_key, $builtin_types, true)) {
                        $builtin_list[$post_type_key] = $post_type_obj;
                    } else {
                        $custom_list[$post_type_key] = $post_type_obj;
                    }
                }

                // Display built-in post types
                if (!empty($builtin_list)) :
                ?>
                    <div style="margin-bottom: 15px;">
                        <strong style="display: block; margin-bottom: 8px;"><?php _e('WordPress Built-in Post Types:', 'wp-site-notifications'); ?></strong>
                        <?php foreach ($builtin_list as $post_type_key => $post_type_obj) :
                            $count_posts = wp_count_posts($post_type_key);
                            $total_posts = 0;
                            foreach ($count_posts as $status => $count) {
                                $total_posts += $count;
                            }
                        ?>
                            <label style="display: block; margin-bottom: 5px;">
                                <input type="checkbox"
                                       name="post_notifications_settings[enabled_post_types][]"
                                       value="<?php echo esc_attr($post_type_key); ?>"
                                       <?php checked(in_array($post_type_key, $enabled_post_types)); ?>>
                                <?php echo esc_html($post_type_obj->labels->name); ?>
                                <span style="color: #666; font-size: 0.9em;">
                                    (<?php echo esc_html(sprintf(_n('%d item', '%d items', $total_posts, 'wp-site-notifications'), $total_posts)); ?>)
                                </span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php
                // Display custom post types
                if (!empty($custom_list)) :
                ?>
                    <div style="margin-bottom: 10px; padding: 10px; background: #f0f8ff; border-left: 4px solid #0073aa;">
                        <strong style="display: block; margin-bottom: 8px;">
                            <?php _e('Custom Post Types:', 'wp-site-notifications'); ?>
                            <span style="color: #0073aa; font-weight: normal; font-size: 0.9em;">
                                <?php _e('(Added by plugins or theme)', 'wp-site-notifications'); ?>
                            </span>
                        </strong>
                        <?php foreach ($custom_list as $post_type_key => $post_type_obj) :
                            $count_posts = wp_count_posts($post_type_key);
                            $total_posts = 0;
                            foreach ($count_posts as $status => $count) {
                                $total_posts += $count;
                            }
                        ?>
                            <label style="display: block; margin-bottom: 5px;">
                                <input type="checkbox"
                                       name="post_notifications_settings[enabled_post_types][]"
                                       value="<?php echo esc_attr($post_type_key); ?>"
                                       <?php checked(in_array($post_type_key, $enabled_post_types)); ?>>
                                <?php echo esc_html($post_type_obj->labels->name); ?>
                                <span style="color: #666; font-size: 0.9em;">
                                    (<?php echo esc_html(sprintf(_n('%d item', '%d items', $total_posts, 'wp-site-notifications'), $total_posts)); ?>)
                                </span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <p class="description">
                    <?php _e('Select which post types should trigger notifications. Both standard and custom post types are shown.', 'wp-site-notifications'); ?>
                </p>
            </fieldset>
        </td>
    </tr>
</table>
