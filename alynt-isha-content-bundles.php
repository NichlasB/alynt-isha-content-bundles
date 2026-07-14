<?php
/**
 * Plugin Name:       Alynt ISHA Content Bundles
 * Description:       Manages explicit teacher content bundles and compatible legacy video entitlements for the ISHA Classes website.
 * Version:           0.3.0
 * Author:            Alynt
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       alynt-isha-content-bundles
 * Domain Path:       /languages
 * GitHub Plugin URI: NichlasB/alynt-isha-content-bundles
 * Requires at least: 6.0
 * Requires PHP:      7.4
 *
 * @package Alynt_ISHA_Content_Bundles
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'ALYNT_ISHA_CONTENT_BUNDLES_VERSION', '0.3.0' );
define( 'ALYNT_ISHA_CONTENT_BUNDLES_PLUGIN_FILE', __FILE__ );
define( 'ALYNT_ISHA_CONTENT_BUNDLES_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'ALYNT_ISHA_CONTENT_BUNDLES_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'ALYNT_ISHA_CONTENT_BUNDLES_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

require_once ALYNT_ISHA_CONTENT_BUNDLES_PLUGIN_DIR . 'src/class-loader.php';

\Alynt\ISHAContentBundles\Loader::load();

register_activation_hook(
	__FILE__,
	array( \Alynt\ISHAContentBundles\Activator::class, 'activate' )
);
register_deactivation_hook(
	__FILE__,
	array( \Alynt\ISHAContentBundles\Deactivator::class, 'deactivate' )
);

/**
 * Register the plugin runtime.
 *
 * Activation remains write-free; runtime behavior is registered only after
 * all plugins have loaded.
 *
 * @return void
 */
function alynt_isha_content_bundles_run() {
	$plugin = new \Alynt\ISHAContentBundles\Plugin();
	add_action( 'plugins_loaded', array( $plugin, 'run' ) );
}

alynt_isha_content_bundles_run();
