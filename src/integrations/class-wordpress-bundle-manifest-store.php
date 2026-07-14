<?php
/**
 * WordPress bundle-manifest store.
 *
 * @package Alynt_ISHA_Content_Bundles
 */

namespace Alynt\ISHAContentBundles\Integrations;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Alynt\ISHAContentBundles\BundleMetadata;
use Alynt\ISHAContentBundles\Contracts\BundleManifestStore;
use Alynt\ISHAContentBundles\Value\BundleManifest;
use RuntimeException;

/**
 * Persists approved bundle product metadata.
 *
 * @since 0.2.0
 */
final class WordPressBundleManifestStore implements BundleManifestStore {

	/**
	 * Save a product bundle manifest and approved commercial fields.
	 *
	 * @param int            $product_id Product ID.
	 * @param BundleManifest $manifest   Validated manifest.
	 * @return void
	 *
	 * @since 0.2.0
	 */
	public function save_manifest( int $product_id, BundleManifest $manifest ): void {
		$this->write_meta( $product_id, BundleMetadata::META_VIDEO_IDS, $manifest->get_video_ids() );
		$this->write_meta( $product_id, BundleMetadata::META_TEACHER_ID, $manifest->get_teacher_id() );
		$this->write_meta( $product_id, BundleMetadata::META_RUNTIME_SECONDS, $manifest->get_runtime_seconds() );
		$this->write_meta( $product_id, BundleMetadata::META_QUALIFIES, $manifest->qualifies() ? 'yes' : 'no' );
		$this->write_meta( $product_id, '_regular_price', BundleMetadata::BUNDLE_PRICE );
		$this->write_meta( $product_id, '_price', BundleMetadata::BUNDLE_PRICE );
		$this->write_meta( $product_id, '_virtual', 'yes' );
	}

	/**
	 * Delete plugin-owned bundle metadata.
	 *
	 * @param int $product_id Product ID.
	 * @return void
	 * @throws RuntimeException When metadata cannot be removed.
	 *
	 * @since 0.2.0
	 */
	public function delete_manifest( int $product_id ): void {
		foreach ( $this->get_manifest_meta_keys() as $meta_key ) {
			if ( ! delete_post_meta( $product_id, $meta_key ) && metadata_exists( 'post', $product_id, $meta_key ) ) {
				throw new RuntimeException( 'Bundle metadata could not be removed.' );
			}
		}
	}

	/**
	 * Write and verify one metadata value.
	 *
	 * WordPress returns false when the value is unchanged, so the stored value is
	 * checked before treating that result as a failure.
	 *
	 * @param int    $product_id Product ID.
	 * @param string $meta_key   Metadata key.
	 * @param mixed  $value      Metadata value.
	 * @return void
	 * @throws RuntimeException When the value cannot be confirmed.
	 */
	private function write_meta( int $product_id, string $meta_key, $value ): void {
		$result = update_post_meta( $product_id, $meta_key, $value );
		if ( false !== $result ) {
			return;
		}

		$stored  = get_post_meta( $product_id, $meta_key, true );
		$matches = is_array( $value ) ? $stored === $value : (string) $stored === (string) $value;
		if ( ! $matches ) {
			throw new RuntimeException( 'Bundle metadata could not be saved.' );
		}
	}

	/**
	 * Get plugin-owned manifest keys.
	 *
	 * @return string[]
	 */
	private function get_manifest_meta_keys(): array {
		return array(
			BundleMetadata::META_VIDEO_IDS,
			BundleMetadata::META_TEACHER_ID,
			BundleMetadata::META_RUNTIME_SECONDS,
			BundleMetadata::META_QUALIFIES,
		);
	}
}
