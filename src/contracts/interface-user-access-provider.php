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
 *
 * @since 0.2.0
 */
interface UserAccessProvider {

	/**
	 * Determine whether a user has administrator content access.
	 *
	 * @param int $user_id WordPress user ID.
	 * @return bool
	 *
	 * @since 0.2.0
	 */
	public function is_administrator( int $user_id ): bool;
}
