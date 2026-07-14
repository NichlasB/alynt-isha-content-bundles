<?php
/**
 * WordPress admin-security adapter.
 *
 * @package Alynt_ISHA_Content_Bundles
 */

namespace Alynt\ISHAContentBundles\Integrations;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Alynt\ISHAContentBundles\Contracts\AdminSecurityProvider;

/**
 * Wraps WordPress capability and nonce checks.
 *
 * @since 0.2.0
 */
final class WordPressAdminSecurityProvider implements AdminSecurityProvider {

	/**
	 * Determine whether a user has a capability.
	 *
	 * @param int    $user_id    WordPress user ID.
	 * @param string $capability Required capability.
	 * @return bool
	 *
	 * @since 0.2.0
	 */
	public function user_can( int $user_id, string $capability ): bool {
		return $user_id > 0 && user_can( $user_id, $capability );
	}

	/**
	 * Verify a WordPress nonce.
	 *
	 * @param string $nonce  Submitted nonce.
	 * @param string $action Expected action.
	 * @return bool
	 *
	 * @since 0.2.0
	 */
	public function verify_nonce( string $nonce, string $action ): bool {
		return '' !== $nonce && false !== wp_verify_nonce( $nonce, $action );
	}
}
