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
 */
final class Deactivator {

	/**
	 * Deactivate the plugin.
	 *
	 * @return void
	 */
	public static function deactivate() {
		// Intentionally empty.
	}
}
