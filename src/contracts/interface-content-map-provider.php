<?php
/**
 * Content map provider contract.
 *
 * @package Alynt_ISHA_Content_Bundles
 */

namespace Alynt\ISHAContentBundles\Contracts;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Maps purchased products to their entitled video content.
 */
interface ContentMapProvider {

	/**
	 * Get the one video linked to a legacy individual product.
	 *
	 * @param int $product_id WooCommerce product ID.
	 * @return int|null
	 */
	public function get_legacy_video_id( int $product_id ): ?int;

	/**
	 * Get the explicit video manifest for a bundle product.
	 *
	 * @param int $product_id WooCommerce product ID.
	 * @return array<int|string|mixed>
	 */
	public function get_bundle_video_ids( int $product_id ): array;

	/**
	 * Get all videos available to administrators.
	 *
	 * @return array<int|string|mixed>
	 */
	public function get_all_video_ids(): array;
}
