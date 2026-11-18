/**
 * Small progressive enhancements for the Guard Site 24 dashboard.
 *
 * The plugin does not depend on JavaScript for security enforcement;
 * all security-sensitive checks are performed server-side.
 */

document.addEventListener('DOMContentLoaded', function () {
    'use strict';

    // Make dismissible dashboard notices easier to locate for assistive tools.
    document.querySelectorAll('.gs24-wrap .notice').forEach(function (notice) {
        notice.setAttribute('role', 'status');
    });
});
