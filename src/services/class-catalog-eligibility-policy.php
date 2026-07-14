<?php
/**
 * Catalog eligibility and discovery policy.
 *
 * @package Alynt_ISHA_Content_Bundles
 */

namespace Alynt\ISHAContentBundles\Services;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Alynt\ISHAContentBundles\Contracts\CatalogEligibilityProvider;
use Alynt\ISHAContentBundles\Value\BundleManifest;

/**
 * Decides which managed offers can be purchased or publicly discovered.
 *
 * @since 0.2.0
 */
final class CatalogEligibilityPolicy {

	/**
	 * Catalog relationship provider.
	 *
	 * @var CatalogEligibilityProvider
	 */
	private $provider;

	/**
	 * Create the catalog policy.
	 *
	 * @param CatalogEligibilityProvider $provider Catalog relationship provider.
	 *
	 * @since 0.2.0
	 */
	public function __construct( CatalogEligibilityProvider $provider ) {
		$this->provider = $provider;
	}

	/**
	 * Determine whether a product may appear in public discovery.
	 *
	 * Unrelated products remain discoverable so this site-specific policy only
	 * controls known legacy and bundle offers.
	 *
	 * @param int $product_id Product ID.
	 * @return bool
	 *
	 * @since 0.2.0
	 */
	public function is_product_discoverable( int $product_id ): bool {
		if ( $product_id <= 0 || $this->provider->is_legacy_product( $product_id ) ) {
			return false;
		}

		if ( ! $this->provider->is_bundle_product( $product_id ) ) {
			return true;
		}

		return $this->is_qualifying_bundle_product( $product_id );
	}

	/**
	 * Determine whether this policy must block a product purchase.
	 *
	 * A false result means this policy does not block the product; normal
	 * WooCommerce status, stock, and purchasability rules still apply.
	 *
	 * @param int $product_id Product ID.
	 * @return bool
	 *
	 * @since 0.2.0
	 */
	public function should_block_purchase( int $product_id ): bool {
		if ( $product_id <= 0 || $this->provider->is_legacy_product( $product_id ) ) {
			return true;
		}

		if ( ! $this->provider->is_bundle_product( $product_id ) ) {
			return false;
		}

		return ! $this->is_qualifying_bundle_product( $product_id );
	}

	/**
	 * Determine whether a teacher may appear in public discovery.
	 *
	 * @param int $teacher_id Teacher owner ID.
	 * @return bool
	 *
	 * @since 0.2.0
	 */
	public function is_teacher_discoverable( int $teacher_id ): bool {
		return null !== $this->get_available_bundle_product_id_for_teacher( $teacher_id );
	}

	/**
	 * Determine whether a video may appear in public discovery.
	 *
	 * @param int $video_id Video post ID.
	 * @return bool
	 *
	 * @since 0.2.0
	 */
	public function is_video_discoverable( int $video_id ): bool {
		return null !== $this->get_available_bundle_product_id_for_video( $video_id );
	}

	/**
	 * Get an available qualifying bundle for a teacher.
	 *
	 * @param int $teacher_id Teacher owner ID.
	 * @return int|null
	 *
	 * @since 0.2.0
	 */
	public function get_available_bundle_product_id_for_teacher( int $teacher_id ): ?int {
		if ( $teacher_id <= 0 ) {
			return null;
		}

		$product_id = $this->provider->get_bundle_product_id_for_teacher( $teacher_id );

		if ( null === $product_id || $product_id <= 0 || ! $this->is_qualifying_bundle_product( $product_id ) ) {
			return null;
		}

		$manifest = $this->provider->get_bundle_manifest( $product_id );

		if ( null === $manifest || $teacher_id !== $manifest->get_teacher_id() ) {
			return null;
		}

		return $product_id;
	}

	/**
	 * Get an available qualifying bundle for a video.
	 *
	 * @param int $video_id Video post ID.
	 * @return int|null
	 *
	 * @since 0.2.0
	 */
	public function get_available_bundle_product_id_for_video( int $video_id ): ?int {
		if ( $video_id <= 0 ) {
			return null;
		}

		$teacher_id = $this->provider->get_teacher_id_for_video( $video_id );

		if ( null === $teacher_id || $teacher_id <= 0 ) {
			return null;
		}

		return $this->get_available_bundle_product_id_for_teacher( $teacher_id );
	}

	/**
	 * Determine whether a product is a valid qualifying bundle.
	 *
	 * @param int $product_id Product ID.
	 * @return bool
	 */
	private function is_qualifying_bundle_product( int $product_id ): bool {
		if ( $this->provider->is_legacy_product( $product_id ) || ! $this->provider->is_bundle_product( $product_id ) ) {
			return false;
		}

		$manifest = $this->provider->get_bundle_manifest( $product_id );

		if ( ! $manifest instanceof BundleManifest || ! $manifest->qualifies() ) {
			return false;
		}

		return $product_id === $this->provider->get_bundle_product_id_for_teacher( $manifest->get_teacher_id() );
	}
}
