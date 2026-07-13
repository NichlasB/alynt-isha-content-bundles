<?php
/**
 * WooCommerce purchase adapter.
 *
 * @package Alynt_ISHA_Content_Bundles
 */

namespace Alynt\ISHAContentBundles\Integrations;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Alynt\ISHAContentBundles\Contracts\PurchaseProvider;
use Alynt\ISHAContentBundles\Value\Purchase;

/**
 * Reads customer purchases through HPOS-compatible WooCommerce APIs.
 */
final class WooCommercePurchaseProvider implements PurchaseProvider {

	/**
	 * Get purchases associated with a user.
	 *
	 * @param int $user_id WordPress user ID.
	 * @return Purchase[]
	 */
	public function get_purchases( int $user_id ): array {
		if ( $user_id <= 0 || ! function_exists( 'wc_get_orders' ) ) {
			return array();
		}

		$orders    = wc_get_orders(
			array(
				'customer_id' => $user_id,
				'status'      => array( 'wc-completed' ),
				'limit'       => -1,
				'return'      => 'objects',
			)
		);
		$purchases = array();

		foreach ( $orders as $order ) {
			if ( ! is_object( $order ) || ! method_exists( $order, 'get_items' ) || ! method_exists( $order, 'get_status' ) ) {
				continue;
			}

			foreach ( $order->get_items( 'line_item' ) as $item ) {
				if ( ! is_object( $item ) || ! method_exists( $item, 'get_product_id' ) ) {
					continue;
				}

				$product_id = absint( $item->get_product_id() );
				if ( $product_id > 0 ) {
					$purchases[] = new Purchase( $product_id, (string) $order->get_status() );
				}
			}
		}

		return $purchases;
	}
}
