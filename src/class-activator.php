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
 *
 * @since 0.1.0
 */
final class Activator {

	/**
	 * Activate the plugin.
	 *
	 * Activation is deliberately side-effect free. Production migrations are
	 * explicit, separately approved operations.
	 *
	 * @return void
	 *
	 * @since 0.1.0
	 */
	public static function activate() {
		// Intentionally empty.
	}
}
