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

/**
 * Persists approved bundle product metadata.
 */
final class WordPressBundleManifestStore implements BundleManifestStore {

	/**
	 * Save a product bundle manifest and approved commercial fields.
	 *
	 * @param int            $product_id Product ID.
	 * @param BundleManifest $manifest   Validated manifest.
	 * @return void
	 */
	public function save_manifest( int $product_id, BundleManifest $manifest ): void {
		update_post_meta( $product_id, BundleMetadata::META_VIDEO_IDS, $manifest->get_video_ids() );
		update_post_meta( $product_id, BundleMetadata::META_TEACHER_ID, $manifest->get_teacher_id() );
		update_post_meta( $product_id, BundleMetadata::META_RUNTIME_SECONDS, $manifest->get_runtime_seconds() );
		update_post_meta( $product_id, BundleMetadata::META_QUALIFIES, $manifest->qualifies() ? 'yes' : 'no' );
		update_post_meta( $product_id, '_regular_price', BundleMetadata::BUNDLE_PRICE );
		update_post_meta( $product_id, '_price', BundleMetadata::BUNDLE_PRICE );
		update_post_meta( $product_id, '_virtual', 'yes' );
	}

	/**
	 * Delete plugin-owned bundle metadata.
	 *
	 * @param int $product_id Product ID.
	 * @return void
	 */
	public function delete_manifest( int $product_id ): void {
		foreach ( $this->get_manifest_meta_keys() as $meta_key ) {
			delete_post_meta( $product_id, $meta_key );
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
