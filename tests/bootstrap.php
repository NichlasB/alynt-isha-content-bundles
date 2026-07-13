<?php
/**
 * PHPUnit bootstrap.
 *
 * @package Alynt_ISHA_Content_Bundles
 */

require_once dirname( __DIR__ ) . '/vendor/autoload.php';

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname( __DIR__ ) . '/' );
}

$GLOBALS['alynt_isha_content_bundles_test_actions'] = array();
$GLOBALS['alynt_isha_content_bundles_test_hooks']   = array();

if ( ! function_exists( 'plugin_dir_path' ) ) {
	function plugin_dir_path( $file ) {
		return trailingslashit( dirname( $file ) );
	}
}

if ( ! function_exists( 'trailingslashit' ) ) {
	function trailingslashit( $value ) {
		return rtrim( $value, '/\\' ) . '/';
	}
}

if ( ! function_exists( 'plugin_dir_url' ) ) {
	function plugin_dir_url( $file ) {
		return 'https://example.test/wp-content/plugins/' . basename( dirname( $file ) ) . '/';
	}
}

if ( ! function_exists( 'plugin_basename' ) ) {
	function plugin_basename( $file ) {
		return basename( dirname( $file ) ) . '/' . basename( $file );
	}
}

if ( ! function_exists( 'register_activation_hook' ) ) {
	function register_activation_hook( $file, $callback ) {
		$GLOBALS['alynt_isha_content_bundles_test_hooks']['activation'] = array( $file, $callback );
	}
}

if ( ! function_exists( 'register_deactivation_hook' ) ) {
	function register_deactivation_hook( $file, $callback ) {
		$GLOBALS['alynt_isha_content_bundles_test_hooks']['deactivation'] = array( $file, $callback );
	}
}

if ( ! function_exists( 'add_action' ) ) {
	function add_action( $hook, $callback ) {
		$GLOBALS['alynt_isha_content_bundles_test_hooks']['actions'][ $hook ][] = $callback;
	}
}

if ( ! function_exists( 'do_action' ) ) {
	function do_action( $hook ) {
		$GLOBALS['alynt_isha_content_bundles_test_actions'][] = $hook;
	}
}

require_once dirname( __DIR__ ) . '/alynt-isha-content-bundles.php';
