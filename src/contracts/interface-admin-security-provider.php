<?php
/**
 * Admin security provider contract.
 *
 * @package Alynt_ISHA_Content_Bundles
 */

namespace Alynt\ISHAContentBundles\Contracts;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Wraps WordPress capability and nonce checks for testable admin writes.
 */
interface AdminSecurityProvider {

	/**
	 * Determine whether a user can perform an administrative capability.
	 *
	 * @param int    $user_id    WordPress user ID.
	 * @param string $capability Required capability.
	 * @return bool
	 */
	public function user_can( int $user_id, string $capability ): bool;

	/**
	 * Verify a nonce against an expected action.
	 *
	 * @param string $nonce  Submitted nonce.
	 * @param string $action Expected action.
	 * @return bool
	 */
	public function verify_nonce( string $nonce, string $action ): bool;
}
