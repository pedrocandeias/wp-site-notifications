# Post Notifications

[![WordPress](https://img.shields.io/badge/WordPress-5.0%2B-blue.svg)](https://wordpress.org/)
[![PHP](https://img.shields.io/badge/PHP-7.0%2B-purple.svg)](https://php.net/)
[![License](https://img.shields.io/badge/License-GPLv2%2B-green.svg)](LICENSE)

A powerful WordPress plugin that sends customizable email notifications to selected user roles when post status changes occur. Stay informed about content workflow events automatically.

---

## Table of Contents

- [Features](#features)
- [Installation](#installation)
- [Configuration](#configuration)
- [Usage](#usage)
- [Translation](#translation)
- [Customization](#customization)
- [Requirements](#requirements)
- [Changelog](#changelog)
- [Contributing](#contributing)
- [Support](#support)
- [License](#license)

---

## Features

### 📧 Notification Types

Track all important post status changes:

- ✅ **Post submitted for review** (Pending) - When authors submit content for approval
- ✅ **Post published** - When content goes live
- ✅ **Post saved as draft** - When work is saved
- ✅ **Post scheduled** - When future publications are set
- ✅ **Published post updated** - When live content is modified
- ✅ **Post moved to trash** - When content is deleted

### 👥 Role Management

- Select any WordPress role to receive notifications
- **Automatic custom role detection** - Works with roles from plugins/themes
- User count display for each role
- Visual distinction between built-in and custom roles
- Duplicate prevention (users with multiple roles get one email)

### 🎨 User Experience

- Clean, HTML-formatted emails with post details and action links
- Prevents spam from autosave and revisions
- Rate limiting on update notifications (max 1 per hour)
- Smart filtering (only tracks regular posts, not pages or custom post types)

### 🌍 Translation Ready

- **Fully internationalized** and translation-ready
- Includes **Portuguese (pt_PT)** and **Spanish (es_ES)** translations
- See [TRANSLATION.md](TRANSLATION.md) for adding your language

## Installation

### Method 1: Manual Installation

1. Download the latest release from the [Releases page](../../releases)
2. Upload the `post-notifications` folder to `/wp-content/plugins/`
3. Activate the plugin through the **Plugins** menu in WordPress
4. Go to **Settings > Post Notifications** to configure

### Method 2: WordPress Admin

1. Go to **Plugins > Add New** in your WordPress admin
2. Search for "Post Notifications"
3. Click **Install Now** and then **Activate**
4. Go to **Settings > Post Notifications** to configure

### Method 3: WP-CLI

```bash
wp plugin install post-notifications --activate
```

### First-Time Setup

Upon activation, default settings are automatically configured:
- ✅ Enabled notifications: Pending, Published, Scheduled
- 👤 Default recipients: Administrators only

You can customize these in **Settings > Post Notifications**.

## Configuration

Navigate to **Settings > Post Notifications** in your WordPress admin panel.

### 🔔 Choosing Notification Types

Select which post status changes trigger notifications:

| Notification Type | When It's Sent |
|------------------|----------------|
| Post submitted for review | Author submits a post (Pending status) |
| Post published | Post goes live for the first time |
| Post saved as draft | Post is saved as draft |
| Post scheduled | Post is scheduled for future publication |
| Published post updated | Changes are made to an already published post |
| Post moved to trash | Post is deleted |

### 👥 Selecting Recipient Roles

Choose which user roles receive notifications:

#### Built-in WordPress Roles
- Administrator
- Editor
- Author
- Contributor
- Subscriber

#### Custom Roles (Automatically Detected)
The plugin automatically detects roles added by:
- Plugins (e.g., WooCommerce Shop Manager, SEO Manager)
- Themes
- Custom code

**Features:**
- 📊 User count displayed for each role
- 🎨 Visual separation between built-in and custom roles
- 🚫 Duplicate prevention (users with multiple roles receive only one email)

> 📖 See [CUSTOM-ROLES.md](CUSTOM-ROLES.md) for detailed information about custom role support.

## Usage

### 📬 What's Included in Notification Emails

Each email contains:

- **Site information**: Name and link to your WordPress site
- **Post details**: Title, author name, and current status
- **Action links**: Quick access to view, edit, or review the post
- **Clean HTML formatting**: Professional, readable layout

### Example Email

```
[Your Site Name]

Post Status Change

Title: "10 Tips for Better Content"
Author: John Doe
Status: Published

[View Post] [Edit Post]
```

## Customization

### 🔌 Developer Hooks

The plugin provides filter hooks for advanced customization:

#### Modify Email Subject

```php
add_filter('post_notifications_email_subject', function($subject, $post, $new_status, $old_status) {
    return "Custom: " . $subject;
}, 10, 4);
```

#### Modify Email Body

```php
add_filter('post_notifications_email_message', function($message, $post, $new_status, $old_status) {
    return $message . "\n\nCustom footer text";
}, 10, 4);
```

#### Modify Recipients List

```php
add_filter('post_notifications_recipients', function($recipients, $post, $new_status) {
    // Add a specific email
    $recipients[] = 'custom@example.com';
    return $recipients;
}, 10, 3);
```

### 🛠️ Technical Details

| Aspect | Implementation |
|--------|----------------|
| **Hook Used** | `transition_post_status` |
| **Post Type** | Standard posts only (excludes pages & custom post types) |
| **Autosave/Revisions** | Automatically ignored to prevent spam |
| **Email Function** | WordPress native `wp_mail()` |
| **Rate Limiting** | 1 update notification per hour per post |
| **Security** | CSRF protection, XSS prevention, SQL injection hardening |

### 🚀 Roadmap

Future versions may include:

- [ ] Custom post type support
- [ ] Visual email template editor
- [ ] Additional notification triggers
- [ ] Per-user notification preferences
- [ ] Notification history log

## Translation

The plugin is **fully internationalized** and ready for translation.

### Available Languages

| Language | Code | Status |
|----------|------|--------|
| English | en_US | ✅ Default |
| Portuguese (Portugal) | pt_PT | ✅ Complete |
| Spanish (Spain) | es_ES | ✅ Complete |

### Add Your Language

Want to translate to your language? See [TRANSLATION.md](TRANSLATION.md) for step-by-step instructions.

Contributions are welcome! Submit your translations via pull request.

## Requirements

| Requirement | Minimum Version |
|-------------|-----------------|
| WordPress | 5.0+ |
| PHP | 7.0+ |

---

## Changelog

### Version 1.0.0 (Initial Release)

#### ✨ Features
- 📧 6 notification types for post status changes
- 👥 Role-based recipient selection (including custom roles)
- 📨 HTML-formatted email templates
- 🌍 Full translation support (pt_PT, es_ES included)
- 🔌 Developer hooks for extensibility

#### 🔒 Security
- CSRF token protection
- XSS prevention
- SQL injection hardening
- Input sanitization and validation

#### ⚡ Performance
- Rate limiting (1 update notification per hour)
- Autosave/revision filtering
- Duplicate notification prevention

See [CHANGELOG.md](CHANGELOG.md) for detailed version history.

---

## Contributing

Contributions are welcome! Here's how you can help:

### 🐛 Report Bugs

Found a bug? [Open an issue](../../issues/new) with:
- WordPress version
- PHP version
- Plugin version
- Steps to reproduce
- Expected vs actual behavior

### 💡 Suggest Features

Have an idea? [Open a feature request](../../issues/new) describing:
- The problem you're trying to solve
- Your proposed solution
- Any alternative solutions considered

### 🔧 Submit Pull Requests

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

### 📝 Code Standards

- Follow [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/)
- Include inline documentation
- Test thoroughly before submitting

---

## Support

### 📚 Documentation

- [Custom Roles Guide](CUSTOM-ROLES.md)
- [Translation Guide](TRANSLATION.md)
- [Changelog](CHANGELOG.md)

### 💬 Get Help

- 🐛 **Bug reports**: [GitHub Issues](../../issues)
- 💡 **Feature requests**: [GitHub Issues](../../issues)
- ❓ **Questions**: [GitHub Discussions](../../discussions)

### ⭐ Show Your Support

If this plugin helped you, please:
- ⭐ Star this repository
- 🐦 Share it on social media
- 📝 Write a review

---

## License

This plugin is licensed under the **GNU General Public License v2.0 or later**.

```
Post Notifications - WordPress Plugin
Copyright (C) 2025

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
```

See [LICENSE](LICENSE) file for full license text.

---

<div align="center">

**Made with ❤️ for the WordPress community**

[Report Bug](../../issues) · [Request Feature](../../issues) · [Documentation](../../wiki)

</div>
