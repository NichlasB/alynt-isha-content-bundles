<?php
/**
 * Fake bundle manifest store.
 *
 * @package Alynt_ISHA_Content_Bundles
 */

namespace Alynt\ISHAContentBundles\Tests\Support;

use Alynt\ISHAContentBundles\Contracts\BundleManifestStore;
use Alynt\ISHAContentBundles\Value\BundleManifest;

/**
 * Test double for product manifest persistence.
 */
final class FakeBundleManifestStore implements BundleManifestStore {

	/** @var bool */
	private $fail_writes;

	/**
	 * Create the store.
	 *
	 * @param bool $fail_writes Whether persistence should throw.
	 */
	public function __construct( bool $fail_writes = false ) {
		$this->fail_writes = $fail_writes;
	}

	/**
	 * Saved manifests keyed by product ID.
	 *
	 * @var array<int,BundleManifest>
	 */
	private $saved = array();

	/**
	 * Deleted product IDs.
	 *
	 * @var int[]
	 */
	private $deleted = array();

	/**
	 * Save a product bundle manifest.
	 *
	 * @param int            $product_id Product ID.
	 * @param BundleManifest $manifest   Manifest to persist.
	 * @return void
	 */
	public function save_manifest( int $product_id, BundleManifest $manifest ): void {
		if ( $this->fail_writes ) {
			throw new \RuntimeException( 'Simulated save failure.' );
		}
		$this->saved[ $product_id ] = $manifest;
	}

	/**
	 * Remove bundle metadata from a product.
	 *
	 * @param int $product_id Product ID.
	 * @return void
	 */
	public function delete_manifest( int $product_id ): void {
		if ( $this->fail_writes ) {
			throw new \RuntimeException( 'Simulated delete failure.' );
		}
		$this->deleted[] = $product_id;
		unset( $this->saved[ $product_id ] );
	}

	/**
	 * Get a saved manifest.
	 *
	 * @param int $product_id Product ID.
	 * @return BundleManifest|null
	 */
	public function get_saved_manifest( int $product_id ): ?BundleManifest {
		return $this->saved[ $product_id ] ?? null;
	}

	/**
	 * Count saved manifests.
	 *
	 * @return int
	 */
	public function count_saved(): int {
		return count( $this->saved );
	}

	/**
	 * Get deleted product IDs.
	 *
	 * @return int[]
	 */
	public function get_deleted_product_ids(): array {
		return $this->deleted;
	}
}
