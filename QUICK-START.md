# Quick Start Guide - Post Notifications

## Installation (30 seconds)

1. Upload `post-notifications` folder to `/wp-content/plugins/`
2. Activate via **Plugins** menu in WordPress
3. Go to **Settings > Post Notifications**

## Basic Setup (2 minutes)

### Step 1: Select Notification Types

Check which events should send emails:

- ☑ Post submitted for review (Pending) - *Recommended for editors*
- ☑ Post published - *Recommended*
- ☐ Post saved as draft
- ☑ Post scheduled - *Recommended*
- ☐ Published post updated
- ☐ Post moved to trash

### Step 2: Select Recipients

Choose who receives notifications:

- ☑ Administrator - *Recommended*
- ☑ Editor - *Recommended*
- ☐ Author
- ☐ Contributor
- ☐ Subscriber

### Step 3: Save Settings

Click **Save Settings** button.

## That's It!

Notifications will now be sent automatically when posts change status.

## Testing (1 minute)

1. Create a new post
2. Set status to "Pending Review"
3. Click "Submit for Review"
4. Check email inbox for notification

## Common Scenarios

### Scenario 1: Editorial Workflow
**Goal**: Notify editors when authors submit posts for review

**Settings**:
- ☑ Post submitted for review (Pending)
- ☑ Post published
- Recipients: ☑ Administrator, ☑ Editor

### Scenario 2: Publication Alerts
**Goal**: Notify team when posts go live

**Settings**:
- ☑ Post published
- ☑ Post scheduled
- Recipients: ☑ Administrator, ☑ Editor, ☑ Author

### Scenario 3: Full Monitoring
**Goal**: Track all post changes

**Settings**:
- ☑ All notification types
- Recipients: ☑ Administrator

## Email Examples

### When Author Submits Post for Review
```
Subject: [Your Site] New post pending review: Amazing Article Title

A new post has been submitted for review on Your Site.

Title: Amazing Article Title
Author: John Doe
Status: Pending Review

[Review and approve this post]
```

### When Post is Published
```
Subject: [Your Site] Post published: Amazing Article Title

A post has been published on Your Site.

Title: Amazing Article Title
Author: John Doe

[View post] | [Edit post]
```

## Troubleshooting

### Not Receiving Emails?

1. **Check spam folder** - Emails might be filtered
2. **Verify email settings** - Ensure WordPress can send emails
3. **Test with plugin** - Install WP Mail SMTP for testing
4. **Check recipient roles** - Ensure your user has selected role
5. **Verify notification enabled** - Check settings for that notification type

### Getting Too Many Emails?

1. **Disable "Updated" notifications** - These can be frequent
2. **Select fewer roles** - Reduce number of recipients
3. **Rate limiting active** - Updates limited to 1 per hour per post

### Emails in Wrong Language?

1. Go to **Settings > General**
2. Change **Site Language**
3. Save and test again

Available languages: English, Portuguese (pt_PT), Spanish (es_ES)

## Advanced Usage

### Custom Email Content

Use filter hooks to customize emails:

```php
// Modify email subject
add_filter('post_notifications_email_subject', function($subject, $type, $post) {
    return '[CUSTOM] ' . $subject;
}, 10, 3);

// Modify email message
add_filter('post_notifications_email_message', function($message, $type, $post, $author) {
    return $message . '<p>Custom footer text</p>';
}, 10, 4);

// Modify recipients
add_filter('post_notifications_recipients', function($recipients, $type, $post) {
    // Add custom logic
    return $recipients;
}, 10, 3);
```

### Check Settings Programmatically

```php
$settings = get_option('post_notifications_settings');
$enabled = $settings['enabled_notifications'];
$roles = $settings['recipient_roles'];
```

## Security Features

✅ CSRF protection (nonce verification)
✅ XSS prevention (output escaping)
✅ Permission checks (capability verification)
✅ Email header injection prevention
✅ Rate limiting (1 update email per hour)
✅ Role validation

## Performance

- **Minimal overhead**: Only runs on post status changes
- **No database queries**: Uses WordPress core functions
- **Cached translations**: Language files cached by WordPress
- **Efficient email sending**: Uses WordPress mail queue

## Support

- **Documentation**: See README.md
- **Security**: See SECURITY-AUDIT.md
- **Translations**: See TRANSLATION.md
- **Issues**: Contact plugin author

## Tips

💡 **Start with minimal notifications** - Add more as needed
💡 **Test before going live** - Create test posts first
💡 **Use descriptive post titles** - They appear in email subjects
💡 **Keep recipient list focused** - Too many recipients = email fatigue
💡 **Check email settings** - Ensure proper "From" name and address

## Uninstall

1. Deactivate plugin
2. Delete plugin
3. Settings automatically removed from database

## Next Steps

- Read full [README.md](README.md) for detailed features
- Review [SECURITY-AUDIT.md](SECURITY-AUDIT.md) for security info
- Check [TRANSLATION.md](TRANSLATION.md) to add your language
- Customize with filter hooks (see Advanced Usage above)

---

**Plugin**: Post Notifications v1.0.0
**Time to Setup**: 2 minutes
**Difficulty**: Easy ⭐
