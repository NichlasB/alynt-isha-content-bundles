<?php
/**
 * Uninstall policy for Alynt ISHA Content Bundles.
 *
 * @package Alynt_ISHA_Content_Bundles
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Preserve bundle manifests, verified runtimes, legacy relationships, and the
// latest migration snapshot so uninstall cannot silently destroy entitlements
// or rollback evidence. Administrators may remove those records only through a
// separately reviewed, site-specific data-retirement procedure.
