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
		require_once ALYNT_ISHA_CONTENT_BUNDLES_PLUGIN_DIR . 'src/class-plugin.php';
	}
}
