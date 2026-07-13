<?php
/**
 * Fake user access provider.
 *
 * @package Alynt_ISHA_Content_Bundles
 */

namespace Alynt\ISHAContentBundles\Tests\Support;

use Alynt\ISHAContentBundles\Contracts\UserAccessProvider;

/**
 * Supplies deterministic administrator access in unit tests.
 */
final class FakeUserAccessProvider implements UserAccessProvider {

	/**
	 * Administrator IDs.
	 *
	 * @var array<int,bool>
	 */
	private $administrator_ids;

	/**
	 * Create the fake provider.
	 *
	 * @param int[] $administrator_ids Administrator IDs.
	 */
	public function __construct( array $administrator_ids = array() ) {
		$this->administrator_ids = array_fill_keys( $administrator_ids, true );
	}

	/**
	 * Determine whether a user is an administrator.
	 *
	 * @param int $user_id User ID.
	 * @return bool
	 */
	public function is_administrator( int $user_id ): bool {
		return isset( $this->administrator_ids[ $user_id ] );
	}
}
