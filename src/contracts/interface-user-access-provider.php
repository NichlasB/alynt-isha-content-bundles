<?php
/**
 * User access provider contract.
 *
 * @package Alynt_ISHA_Content_Bundles
 */

namespace Alynt\ISHAContentBundles\Contracts;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Supplies user-level access facts to the entitlement resolver.
 */
interface UserAccessProvider {

	/**
	 * Determine whether a user has administrator content access.
	 *
	 * @param int $user_id WordPress user ID.
	 * @return bool
	 */
	public function is_administrator( int $user_id ): bool;
}
