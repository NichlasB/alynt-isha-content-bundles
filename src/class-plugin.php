<?php
/**
 * Plugin runtime.
 *
 * @package Alynt_ISHA_Content_Bundles
 */

namespace Alynt\ISHAContentBundles;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Coordinates plugin services and integrations.
 */
final class Plugin {

	/**
	 * Start the plugin runtime.
	 *
	 * The scaffold exposes only a lifecycle action. It does not register
	 * customer-facing behavior or write WordPress data.
	 *
	 * @return void
	 */
	public function run() {
		/**
		 * Fires after the plugin runtime has loaded.
		 *
		 * @since 0.1.0
		 */
		do_action( 'alynt_isha_content_bundles_loaded' );
	}
}
