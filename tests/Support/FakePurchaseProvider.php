<?php
/**
 * Fake purchase provider.
 *
 * @package Alynt_ISHA_Content_Bundles
 */

namespace Alynt\ISHAContentBundles\Tests\Support;

use Alynt\ISHAContentBundles\Contracts\PurchaseProvider;

/**
 * Supplies deterministic purchases in unit tests.
 */
final class FakePurchaseProvider implements PurchaseProvider {

	/**
	 * Purchases keyed by user ID.
	 *
	 * @var array<int,array>
	 */
	private $purchases_by_user;

	/**
	 * Create the fake provider.
	 *
	 * @param array<int,array> $purchases_by_user Purchases keyed by user ID.
	 */
	public function __construct( array $purchases_by_user = array() ) {
		$this->purchases_by_user = $purchases_by_user;
	}

	/**
	 * Get a user's purchases.
	 *
	 * @param int $user_id User ID.
	 * @return array
	 */
	public function get_purchases( int $user_id ): array {
		return $this->purchases_by_user[ $user_id ] ?? array();
	}
}
