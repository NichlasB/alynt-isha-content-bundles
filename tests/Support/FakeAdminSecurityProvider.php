<?php
/**
 * Fake admin security provider.
 *
 * @package Alynt_ISHA_Content_Bundles
 */

namespace Alynt\ISHAContentBundles\Tests\Support;

use Alynt\ISHAContentBundles\Contracts\AdminSecurityProvider;

/**
 * Test double for capability and nonce checks.
 */
final class FakeAdminSecurityProvider implements AdminSecurityProvider {

	/**
	 * Whether capability checks should pass.
	 *
	 * @var bool
	 */
	private $allowed;

	/**
	 * Valid nonces keyed by action.
	 *
	 * @var array<string,string>
	 */
	private $nonces_by_action;

	/**
	 * Create the fake provider.
	 *
	 * @param bool                 $allowed           Whether capability checks pass.
	 * @param array<string,string> $nonces_by_action  Valid nonces keyed by action.
	 */
	public function __construct( bool $allowed, array $nonces_by_action ) {
		$this->allowed          = $allowed;
		$this->nonces_by_action = $nonces_by_action;
	}

	/**
	 * Determine whether a user can perform an administrative capability.
	 *
	 * @param int    $user_id    WordPress user ID.
	 * @param string $capability Required capability.
	 * @return bool
	 */
	public function user_can( int $user_id, string $capability ): bool {
		return $user_id > 0 && $this->allowed && 'manage_woocommerce' === $capability;
	}

	/**
	 * Verify a nonce against an expected action.
	 *
	 * @param string $nonce  Submitted nonce.
	 * @param string $action Expected action.
	 * @return bool
	 */
	public function verify_nonce( string $nonce, string $action ): bool {
		return isset( $this->nonces_by_action[ $action ] ) && $this->nonces_by_action[ $action ] === $nonce;
	}
}
