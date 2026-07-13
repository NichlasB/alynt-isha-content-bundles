<?php
/**
 * Runtime class loader.
 *
 * @package Alynt_ISHA_Content_Bundles
 */

namespace Alynt\ISHAContentBundles;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Loads runtime classes without requiring Composer on production.
 */
final class Loader {

	/**
	 * Load the plugin classes.
	 *
	 * @return void
	 */
	public static function load() {
		require_once ALYNT_ISHA_CONTENT_BUNDLES_PLUGIN_DIR . 'src/class-activator.php';
		require_once ALYNT_ISHA_CONTENT_BUNDLES_PLUGIN_DIR . 'src/class-deactivator.php';
		require_once ALYNT_ISHA_CONTENT_BUNDLES_PLUGIN_DIR . 'src/class-bundle-metadata.php';
		require_once ALYNT_ISHA_CONTENT_BUNDLES_PLUGIN_DIR . 'src/class-migration-definition.php';
		require_once ALYNT_ISHA_CONTENT_BUNDLES_PLUGIN_DIR . 'src/class-site-definition.php';
		require_once ALYNT_ISHA_CONTENT_BUNDLES_PLUGIN_DIR . 'src/value/class-purchase.php';
		require_once ALYNT_ISHA_CONTENT_BUNDLES_PLUGIN_DIR . 'src/value/class-bundle-video.php';
		require_once ALYNT_ISHA_CONTENT_BUNDLES_PLUGIN_DIR . 'src/value/class-bundle-manifest.php';
		require_once ALYNT_ISHA_CONTENT_BUNDLES_PLUGIN_DIR . 'src/value/class-bundle-manifest-save-result.php';
		require_once ALYNT_ISHA_CONTENT_BUNDLES_PLUGIN_DIR . 'src/value/class-video-access-decision.php';
		require_once ALYNT_ISHA_CONTENT_BUNDLES_PLUGIN_DIR . 'src/value/class-library-video.php';
		require_once ALYNT_ISHA_CONTENT_BUNDLES_PLUGIN_DIR . 'src/value/class-relationship-migration-change.php';
		require_once ALYNT_ISHA_CONTENT_BUNDLES_PLUGIN_DIR . 'src/value/class-migration-plan.php';
		require_once ALYNT_ISHA_CONTENT_BUNDLES_PLUGIN_DIR . 'src/value/class-migration-snapshot.php';
		require_once ALYNT_ISHA_CONTENT_BUNDLES_PLUGIN_DIR . 'src/value/class-migration-execution-result.php';
		require_once ALYNT_ISHA_CONTENT_BUNDLES_PLUGIN_DIR . 'src/contracts/interface-user-access-provider.php';
		require_once ALYNT_ISHA_CONTENT_BUNDLES_PLUGIN_DIR . 'src/contracts/interface-purchase-provider.php';
		require_once ALYNT_ISHA_CONTENT_BUNDLES_PLUGIN_DIR . 'src/contracts/interface-content-map-provider.php';
		require_once ALYNT_ISHA_CONTENT_BUNDLES_PLUGIN_DIR . 'src/contracts/interface-admin-security-provider.php';
		require_once ALYNT_ISHA_CONTENT_BUNDLES_PLUGIN_DIR . 'src/contracts/interface-bundle-content-provider.php';
		require_once ALYNT_ISHA_CONTENT_BUNDLES_PLUGIN_DIR . 'src/contracts/interface-bundle-manifest-store.php';
		require_once ALYNT_ISHA_CONTENT_BUNDLES_PLUGIN_DIR . 'src/contracts/interface-video-route-provider.php';
		require_once ALYNT_ISHA_CONTENT_BUNDLES_PLUGIN_DIR . 'src/contracts/interface-video-library-provider.php';
		require_once ALYNT_ISHA_CONTENT_BUNDLES_PLUGIN_DIR . 'src/contracts/interface-catalog-eligibility-provider.php';
		require_once ALYNT_ISHA_CONTENT_BUNDLES_PLUGIN_DIR . 'src/contracts/interface-migration-store.php';
		require_once ALYNT_ISHA_CONTENT_BUNDLES_PLUGIN_DIR . 'src/services/class-entitlement-resolver.php';
		require_once ALYNT_ISHA_CONTENT_BUNDLES_PLUGIN_DIR . 'src/services/class-bundle-manifest-normalizer.php';
		require_once ALYNT_ISHA_CONTENT_BUNDLES_PLUGIN_DIR . 'src/services/class-bundle-manifest-admin-service.php';
		require_once ALYNT_ISHA_CONTENT_BUNDLES_PLUGIN_DIR . 'src/services/class-video-access-controller.php';
		require_once ALYNT_ISHA_CONTENT_BUNDLES_PLUGIN_DIR . 'src/services/class-purchased-video-library-resolver.php';
		require_once ALYNT_ISHA_CONTENT_BUNDLES_PLUGIN_DIR . 'src/services/class-purchased-video-library-renderer.php';
		require_once ALYNT_ISHA_CONTENT_BUNDLES_PLUGIN_DIR . 'src/services/class-catalog-eligibility-policy.php';
		require_once ALYNT_ISHA_CONTENT_BUNDLES_PLUGIN_DIR . 'src/services/class-relationship-migration-service.php';
		require_once ALYNT_ISHA_CONTENT_BUNDLES_PLUGIN_DIR . 'src/services/class-teacher-directory-content-filter.php';
		require_once ALYNT_ISHA_CONTENT_BUNDLES_PLUGIN_DIR . 'src/services/class-teacher-video-renderer.php';
		require_once ALYNT_ISHA_CONTENT_BUNDLES_PLUGIN_DIR . 'src/integrations/class-wordpress-access-content-provider.php';
		require_once ALYNT_ISHA_CONTENT_BUNDLES_PLUGIN_DIR . 'src/integrations/class-woocommerce-purchase-provider.php';
		require_once ALYNT_ISHA_CONTENT_BUNDLES_PLUGIN_DIR . 'src/integrations/class-wordpress-admin-security-provider.php';
		require_once ALYNT_ISHA_CONTENT_BUNDLES_PLUGIN_DIR . 'src/integrations/class-wordpress-bundle-content-provider.php';
		require_once ALYNT_ISHA_CONTENT_BUNDLES_PLUGIN_DIR . 'src/integrations/class-wordpress-bundle-manifest-store.php';
		require_once ALYNT_ISHA_CONTENT_BUNDLES_PLUGIN_DIR . 'src/integrations/class-wordpress-video-library-provider.php';
		require_once ALYNT_ISHA_CONTENT_BUNDLES_PLUGIN_DIR . 'src/integrations/class-wordpress-catalog-eligibility-provider.php';
		require_once ALYNT_ISHA_CONTENT_BUNDLES_PLUGIN_DIR . 'src/integrations/class-wordpress-video-route-provider.php';
		require_once ALYNT_ISHA_CONTENT_BUNDLES_PLUGIN_DIR . 'src/integrations/class-wordpress-migration-store.php';
		require_once ALYNT_ISHA_CONTENT_BUNDLES_PLUGIN_DIR . 'src/integrations/class-migration-command.php';
		require_once ALYNT_ISHA_CONTENT_BUNDLES_PLUGIN_DIR . 'src/integrations/class-runtime-hooks.php';
		require_once ALYNT_ISHA_CONTENT_BUNDLES_PLUGIN_DIR . 'src/integrations/class-video-runtime-admin.php';
		require_once ALYNT_ISHA_CONTENT_BUNDLES_PLUGIN_DIR . 'src/class-plugin.php';
	}
}
