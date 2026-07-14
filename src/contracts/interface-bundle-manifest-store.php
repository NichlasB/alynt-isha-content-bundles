<?php
/**
 * Bundle manifest store contract.
 *
 * @package Alynt_ISHA_Content_Bundles
 */

namespace Alynt\ISHAContentBundles\Contracts;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Alynt\ISHAContentBundles\Value\BundleManifest;

/**
 * Persists product-level bundle metadata.
 *
 * @since 0.2.0
 */
interface BundleManifestStore {

	/**
	 * Get the currently stored bundle manifest.
	 *
	 * @param int $product_id Product ID.
	 * @return BundleManifest|null
	 *
	 * @since 0.3.0
	 */
	public function get_manifest( int $product_id ): ?BundleManifest;

	/**
	 * Find videos already assigned to other managed bundles.
	 *
	 * @param int   $product_id Product being edited.
	 * @param int[] $video_ids  Candidate video IDs.
	 * @return array<int,int[]> Bundle product IDs keyed by conflicting video ID.
	 *
	 * @since 0.3.0
	 */
	public function get_video_conflicts( int $product_id, array $video_ids ): array;

	/**
	 * Count completed orders containing a bundle product.
	 *
	 * @param int $product_id Product ID.
	 * @return int
	 *
	 * @since 0.3.0
	 */
	public function get_completed_order_count( int $product_id ): int;

	/**
	 * Save a product bundle manifest.
	 *
	 * @param int            $product_id Product ID.
	 * @param BundleManifest $manifest   Manifest to persist.
	 * @return void
	 *
	 * @since 0.2.0
	 */
	public function save_manifest( int $product_id, BundleManifest $manifest ): void;

	/**
	 * Remove bundle metadata from a product.
	 *
	 * @param int $product_id Product ID.
	 * @return void
	 *
	 * @since 0.2.0
	 */
	public function delete_manifest( int $product_id ): void;

	/**
	 * Append a sold-bundle removal audit entry.
	 *
	 * @param int    $product_id           Bundle product ID.
	 * @param int    $user_id              Administrator user ID.
	 * @param int[]  $removed_video_ids     Removed video IDs.
	 * @param string $reason                Administrator-supplied reason.
	 * @param int    $completed_order_count Affected completed-order count.
	 * @return void
	 *
	 * @since 0.3.0
	 */
	public function record_removal_audit(
		int $product_id,
		int $user_id,
		array $removed_video_ids,
		string $reason,
		int $completed_order_count
	): void;
}
