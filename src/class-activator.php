<?php
/**
 * Plugin activation handler.
 *
 * @package Alynt_ISHA_Content_Bundles
 */

namespace Alynt\ISHAContentBundles;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles activation without migrating or changing site data.
 */
final class Activator {

	/**
	 * Activate the plugin.
	 *
	 * Activation is deliberately side-effect free. Production migrations are
	 * explicit, separately approved operations.
	 *
	 * @return void
	 */
	public static function activate() {
		// Intentionally empty.
	}
}
