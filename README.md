# Guard Site 24

Guard Site 24 is a modular WordPress plugin that provides a focused set of basic security monitoring and hardening tools.

## Project Information

- **Plugin name:** Guard Site 24
- **Slug:** `guard-site-24`
- **Version:** 1.0.0
- **Author:** infoahmadi-projects
- **License:** GPL-2.0-or-later
- **Minimum WordPress version:** 6.0
- **Minimum PHP version:** 7.4

## Current Features

- Administrative dashboard with an overview of selected security settings.
- Basic WordPress configuration checks for HTTPS, debug mode, file editor configuration, and core updates.
- Failed-login tracking with temporary IP-based lockout.
- Trusted IP address list.
- Basic request pattern inspection for selected GET and POST data.
- Log-only and block modes for matching request patterns.
- Custom database activity log.
- CSV export of recent activity.
- Modular PHP structure with separate administration assets.

## Important Scope and Limitations

Guard Site 24 is an initial security-monitoring project. It is **not** a professional Web Application Firewall, malware scanner, endpoint detection platform, or substitute for a security audit.

The current configuration checks do not perform a comprehensive filesystem scan, malware signature scan, checksum verification, or vulnerability assessment. Request pattern inspection uses a small set of simple patterns and can produce false positives or miss sophisticated attacks. Login protection uses the direct remote IP address and may require additional configuration when a site is behind a reverse proxy, CDN, or shared network.

The following items are not implemented in version 1.0.0:

- Two-factor authentication (2FA)
- CAPTCHA integration
- Custom login URL
- File quarantine and restoration
- Comprehensive malware scanning
- WordPress core and plugin checksum verification
- Scheduled scanning and automated alert delivery
- Advanced firewall rules and trusted-proxy configuration
- Automated integration and compatibility test suites

The email alert setting is not presented as an active notification workflow in this release.

## Installation

1. Download or clone this repository.
2. Upload the `guard-site-24` directory to `/wp-content/plugins/`, or create a ZIP archive and upload it through **Plugins > Add New > Upload Plugin**.
3. Activate **Guard Site 24** from the WordPress Plugins screen.
4. Open the **Guard Site 24** menu in the WordPress administration area.
5. Review the settings and keep the request filter in **Log Only** mode while validating compatibility.

## Repository Structure

```text
guard-site-24/
├── admin/
│   ├── class-gs24-admin.php
│   ├── css/
│   │   └── admin.css
│   └── js/
│       └── admin.js
├── includes/
│   ├── class-gs24-activity-log.php
│   ├── class-gs24-database.php
│   ├── class-gs24-firewall.php
│   ├── class-gs24-helper.php
│   ├── class-gs24-login-protection.php
│   ├── class-gs24-security-scan.php
│   └── class-gs24-settings.php
├── guard-site-24.php
├── uninstall.php
├── readme.txt
├── LICENSE
└── README.md
```

## Development Notes

The codebase is organized by responsibility:

- `GS24_Helper` contains shared utility methods.
- `GS24_Database` creates the activity table.
- `GS24_Activity_Log` stores and retrieves security events.
- `GS24_Settings` registers and sanitizes plugin settings.
- `GS24_Login_Protection` handles failed-login tracking and temporary lockouts.
- `GS24_Firewall` performs basic request pattern inspection.
- `GS24_Security_Scan` runs selected configuration checks.
- `GS24_Admin` renders the dashboard and handles protected administrative actions.

## Security and Privacy Notes

The plugin records security-related events and visitor IP addresses in the site's WordPress database. Site owners should disclose this behavior in their privacy documentation and define an appropriate data-retention policy.

Before enabling block mode on a production site, test the plugin with the site's themes, plugins, APIs, forms, and normal visitor workflows. Keep current backups and use a staging environment when possible.

This repository has not been represented as having passed a comprehensive automated test suite, third-party penetration test, or independent security audit. Review and test the code in a controlled WordPress environment before production use.

## License

This project is licensed under the GNU General Public License v2.0 or later. See [LICENSE](LICENSE).
