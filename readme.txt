=== More Mails for CF7 ===
Contributors: lev0
Tags: contact, form, contact form, feedback, email, multiple receipients
Requires at least: 4.9.0
Tested up to: 6.5.2
Stable tag: 1.2.1
Requires PHP: 5.6.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Extends the ubiquitous Contact Form 7 plugin to allow three or more messages.

== Description ==

By default, Contact Form 7 has a limit of two distinct mail messages per form, though each can have multiple recipients. This plugin allows you to add as many as you need. It's relatively simple, so does not include the automatic configuration error detection that the default mails have.

If you only wish to send the same message to multiple recipients, you won't need this plugin; instead use the **To** field, or add *Cc*/*Bcc* headers in the **Additional Headers** field as per [Contact Form 7's documentation](https://contactform7.com/adding-cc-bcc-and-other-mail-headers/).

Tested up to v5.9.3 of Contact Form 7.

== Installation ==

1. Upload the entire, extracted plugin directory to the `wp-content/plugins/` directory, or install the plugin through the WordPress Plugins screen directly.
2. Activate the plugin through the Plugins screen.
3. Go to *Contact > More mails*, to set how many Mail sections you need per form, and save.
4. Edit each form that requires an additional mail message.
