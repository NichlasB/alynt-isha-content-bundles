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

	/** @var bool */
	private $fail_audits;

	/** @var bool */
	private $fail_conflict_checks = false;

	/** @var bool */
	private $fail_order_checks = false;

	/**
	 * Create the store.
	 *
	 * @param bool $fail_writes Whether persistence should throw.
	 * @param bool $fail_audits Whether audit persistence should throw.
	 */
	public function __construct( bool $fail_writes = false, bool $fail_audits = false ) {
		$this->fail_writes = $fail_writes;
		$this->fail_audits = $fail_audits;
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

	/** @var array<int,int[]> */
	private $conflicts = array();

	/** @var array<int,int> */
	private $completed_order_counts = array();

	/** @var array<int,array<string,mixed>[]> */
	private $audits = array();

	/** {@inheritdoc} */
	public function get_manifest( int $product_id ): ?BundleManifest {
		return $this->saved[ $product_id ] ?? null;
	}

	/** {@inheritdoc} */
	public function get_video_conflicts( int $product_id, array $video_ids ): array {
		if ( $this->fail_conflict_checks ) {
			throw new \RuntimeException( 'Simulated conflict-check failure.' );
		}
		unset( $product_id );
		$candidates = array_fill_keys( array_map( 'intval', $video_ids ), true );

		return array_intersect_key( $this->conflicts, $candidates );
	}

	/** {@inheritdoc} */
	public function get_completed_order_count( int $product_id ): int {
		if ( $this->fail_order_checks ) {
			throw new \RuntimeException( 'Simulated order-check failure.' );
		}
		return $this->completed_order_counts[ $product_id ] ?? 0;
	}

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

	/** {@inheritdoc} */
	public function record_removal_audit(
		int $product_id,
		int $user_id,
		array $removed_video_ids,
		string $reason,
		int $completed_order_count
	): void {
		if ( $this->fail_audits ) {
			throw new \RuntimeException( 'Simulated audit failure.' );
		}
		$this->audits[ $product_id ][] = array(
			'user_id'              => $user_id,
			'removed_video_ids'     => $removed_video_ids,
			'reason'                => $reason,
			'completed_order_count' => $completed_order_count,
		);
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

	/**
	 * Seed an existing manifest.
	 *
	 * @param int            $product_id Product ID.
	 * @param BundleManifest $manifest   Manifest.
	 * @return void
	 */
	public function seed_manifest( int $product_id, BundleManifest $manifest ): void {
		$this->saved[ $product_id ] = $manifest;
	}

	/**
	 * Configure video conflicts.
	 *
	 * @param array<int,int[]> $conflicts Conflicts.
	 * @return void
	 */
	public function set_conflicts( array $conflicts ): void {
		$this->conflicts = $conflicts;
	}

	/**
	 * Make cross-bundle assignment checks fail.
	 *
	 * @return void
	 */
	public function fail_conflict_checks(): void {
		$this->fail_conflict_checks = true;
	}

	/**
	 * Configure completed-order impact.
	 *
	 * @param int $product_id Product ID.
	 * @param int $count      Count.
	 * @return void
	 */
	public function set_completed_order_count( int $product_id, int $count ): void {
		$this->completed_order_counts[ $product_id ] = $count;
	}

	/**
	 * Make completed-order impact checks fail.
	 *
	 * @return void
	 */
	public function fail_order_checks(): void {
		$this->fail_order_checks = true;
	}

	/**
	 * Get audit entries.
	 *
	 * @param int $product_id Product ID.
	 * @return array<int,array<string,mixed>>
	 */
	public function get_audits( int $product_id ): array {
		return $this->audits[ $product_id ] ?? array();
	}
}
