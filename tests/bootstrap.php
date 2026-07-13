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
$GLOBALS['alynt_isha_content_bundles_test_shortcodes'] = array();
$GLOBALS['alynt_isha_content_bundles_test_is_admin']    = false;

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

if ( ! function_exists( 'wp_json_encode' ) ) {
	function wp_json_encode( $value, $flags = 0 ) {
		return json_encode( $value, $flags );
	}
}

if ( ! function_exists( 'wp_parse_url' ) ) {
	function wp_parse_url( $url ) {
		return parse_url( $url );
	}
}

if ( ! function_exists( 'is_admin' ) ) {
	function is_admin() {
		return $GLOBALS['alynt_isha_content_bundles_test_is_admin'];
	}
}

if ( ! function_exists( 'absint' ) ) {
	function absint( $value ) {
		return abs( (int) $value );
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
	function add_action( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
		$GLOBALS['alynt_isha_content_bundles_test_hooks']['actions'][ $hook ][] = $callback;
		$GLOBALS['alynt_isha_content_bundles_test_hooks']['action_details'][ $hook ][] = array(
			'callback'      => $callback,
			'priority'      => $priority,
			'accepted_args' => $accepted_args,
		);
	}
}

if ( ! function_exists( 'add_filter' ) ) {
	function add_filter( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
		$GLOBALS['alynt_isha_content_bundles_test_hooks']['filters'][ $hook ][] = array(
			'callback'      => $callback,
			'priority'      => $priority,
			'accepted_args' => $accepted_args,
		);
	}
}

if ( ! function_exists( 'remove_action' ) ) {
	function remove_action( $hook, $callback, $priority = 10 ) {
		$GLOBALS['alynt_isha_content_bundles_test_hooks']['removed_actions'][] = array(
			'hook'     => $hook,
			'callback' => $callback,
			'priority' => $priority,
		);
		return true;
	}
}

if ( ! function_exists( 'add_shortcode' ) ) {
	function add_shortcode( $tag, $callback ) {
		$GLOBALS['alynt_isha_content_bundles_test_shortcodes'][ $tag ] = $callback;
	}
}

if ( ! function_exists( 'remove_shortcode' ) ) {
	function remove_shortcode( $tag ) {
		unset( $GLOBALS['alynt_isha_content_bundles_test_shortcodes'][ $tag ] );
	}
}

if ( ! function_exists( 'do_action' ) ) {
	function do_action( $hook ) {
		$GLOBALS['alynt_isha_content_bundles_test_actions'][] = $hook;
	}
}

require_once dirname( __DIR__ ) . '/alynt-isha-content-bundles.php';
