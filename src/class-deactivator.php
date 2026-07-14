<?php
/**
 * Plugin deactivation handler.
 *
 * @package Alynt_ISHA_Content_Bundles
 */

namespace Alynt\ISHAContentBundles;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles deactivation without changing site data.
 *
 * @since 0.1.0
 */
final class Deactivator {

	/**
	 * Deactivate the plugin.
	 *
	 * @return void
	 *
	 * @since 0.1.0
	 */
	public static function deactivate() {
		// Intentionally empty.
	}
}
