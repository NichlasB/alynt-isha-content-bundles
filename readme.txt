=== Alynt ISHA Content Bundles ===
Contributors: alynt
Tags: woocommerce, video, bundles, access
Requires at least: 6.0
Requires PHP: 7.4
Stable tag: 0.3.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Manages explicit teacher content bundles and compatible legacy video entitlements for the ISHA Classes website.

== Description ==

Version 0.3.0 supports multiple independently qualifying $50 bundles per teacher, dynamic completed-order entitlements, guarded sold-bundle removals, direct-video access enforcement, bundle-aware shortcodes, catalog and purchase controls, and signed WP-CLI migration tooling. Activation and deactivation remain write-free.

== Installation ==

1. Upload the `alynt-isha-content-bundles` directory to `/wp-content/plugins/`.
2. Activate the plugin through the WordPress Plugins screen.

== Changelog ==

= 0.3.0 =
* Support multiple independently qualifying bundles per teacher.
* Grant existing completed-order purchasers access to videos appended to their bundle.
* Prevent cross-bundle video overlap and guard sold-bundle removals with impact, confirmation, reason, audit, and rollback controls.

= 0.2.0 =
* Add explicit $50 teacher bundles with a 3,540-second approved cutoff.
* Preserve completed legacy individual-video entitlements.
* Add bundle-aware access, library, discovery, purchase, admin, and migration integration.
