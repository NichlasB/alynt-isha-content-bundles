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
}
