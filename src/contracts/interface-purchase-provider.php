<?php
/**
 * Purchase provider contract.
 *
 * @package Alynt_ISHA_Content_Bundles
 */

namespace Alynt\ISHAContentBundles\Contracts;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Alynt\ISHAContentBundles\Value\Purchase;

/**
 * Supplies product purchases to the entitlement resolver.
 *
 * @since 0.2.0
 */
interface PurchaseProvider {

	/**
	 * Get purchases associated with a user.
	 *
	 * The resolver remains responsible for accepting only completed orders.
	 *
	 * @param int $user_id WordPress user ID.
	 * @return Purchase[]
	 *
	 * @since 0.2.0
	 */
	public function get_purchases( int $user_id ): array;
}
