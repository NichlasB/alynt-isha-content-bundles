<?php
/**
 * Catalog eligibility provider contract.
 *
 * @package Alynt_ISHA_Content_Bundles
 */

namespace Alynt\ISHAContentBundles\Contracts;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Alynt\ISHAContentBundles\Value\BundleManifest;

/**
 * Supplies product and teacher relationships used by catalog policy.
 */
interface CatalogEligibilityProvider {

	/**
	 * Determine whether a product is a retired individual-video product.
	 *
	 * @param int $product_id Product ID.
	 * @return bool
	 */
	public function is_legacy_product( int $product_id ): bool;

	/**
	 * Determine whether a product is managed as a teacher bundle.
	 *
	 * @param int $product_id Product ID.
	 * @return bool
	 */
	public function is_bundle_product( int $product_id ): bool;

	/**
	 * Get the normalized manifest for a bundle product.
	 *
	 * @param int $product_id Product ID.
	 * @return BundleManifest|null
	 */
	public function get_bundle_manifest( int $product_id ): ?BundleManifest;

	/**
	 * Get the bundle product assigned to a teacher owner.
	 *
	 * @param int $teacher_id Teacher owner ID.
	 * @return int|null
	 */
	public function get_bundle_product_id_for_teacher( int $teacher_id ): ?int;

	/**
	 * Get the teacher owner assigned to a video.
	 *
	 * @param int $video_id Video post ID.
	 * @return int|null
	 */
	public function get_teacher_id_for_video( int $video_id ): ?int;
}
