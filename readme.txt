=== Guard Site 24 ===
Contributors: infoahmadi-projects
Tags: security, login, firewall, activity-log, monitoring
Requires at least: 6.0
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A modular WordPress plugin for basic security monitoring and hardening.

== Description ==

Guard Site 24 provides a dashboard, basic WordPress configuration checks, failed-login tracking, temporary IP-based lockout, simple request pattern inspection, activity logging, and CSV export.

This plugin is an initial monitoring tool. It is not a replacement for a professional Web Application Firewall (WAF), a comprehensive malware scanner, or an independent security audit.

== Installation ==

1. Upload the plugin folder to the /wp-content/plugins/ directory, or upload the ZIP file through the WordPress administration dashboard.
2. Activate Guard Site 24 through the Plugins menu.
3. Open the Guard Site 24 menu in the WordPress administration area.
4. Review the settings and use Log Only mode while validating compatibility.

== Frequently Asked Questions ==

= Does this plugin provide complete malware protection? =

No. Version 1.0.0 performs selected configuration checks and basic request pattern inspection. It does not provide comprehensive malware scanning or file integrity verification.

= Should I enable request blocking immediately? =

No. Start with Log Only mode and test the plugin with your site's normal workflows before considering Block mode.

= Does the plugin support two-factor authentication? =

No. Two-factor authentication is listed as a possible future enhancement and is not included in this release.

== Changelog ==

= 1.0.0 =
* Initial modular release.
* Added basic configuration checks.
* Added failed-login tracking and temporary IP-based lockout.
* Added basic request pattern inspection.
* Added activity logging and CSV export.
