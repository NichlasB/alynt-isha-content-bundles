<?php
/**
 * Purchase value object.
 *
 * @package Alynt_ISHA_Content_Bundles
 */

namespace Alynt\ISHAContentBundles\Value;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use InvalidArgumentException;

/**
 * Represents one purchased product and the state of its order.
 *
 * @since 0.2.0
 */
final class Purchase {

	/**
	 * Product ID.
	 *
	 * @var int
	 */
	private $product_id;

	/**
	 * Normalized order status.
	 *
	 * @var string
	 */
	private $order_status;

	/**
	 * Create a purchase value object.
	 *
	 * @param int    $product_id   WooCommerce product ID.
	 * @param string $order_status WooCommerce order status.
	 * @throws InvalidArgumentException When the product ID is invalid.
	 *
	 * @since 0.2.0
	 */
	public function __construct( int $product_id, string $order_status ) {
		if ( $product_id <= 0 ) {
			throw new InvalidArgumentException( 'A purchase requires a positive product ID.' );
		}

		$this->product_id   = $product_id;
		$this->order_status = strtolower( trim( $order_status ) );
	}

	/**
	 * Get the purchased product ID.
	 *
	 * @return int
	 *
	 * @since 0.2.0
	 */
	public function get_product_id(): int {
		return $this->product_id;
	}

	/**
	 * Get the normalized order status.
	 *
	 * @return string
	 *
	 * @since 0.2.0
	 */
	public function get_order_status(): string {
		return $this->order_status;
	}

	/**
	 * Determine whether the purchase grants access.
	 *
	 * @return bool
	 *
	 * @since 0.2.0
	 */
	public function is_completed(): bool {
		return in_array( $this->order_status, array( 'completed', 'wc-completed' ), true );
	}
}
