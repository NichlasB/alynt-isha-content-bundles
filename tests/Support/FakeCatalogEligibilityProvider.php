<?php
/**
 * Fake catalog eligibility provider.
 *
 * @package Alynt_ISHA_Content_Bundles
 */

namespace Alynt\ISHAContentBundles\Tests\Support;

use Alynt\ISHAContentBundles\Contracts\CatalogEligibilityProvider;
use Alynt\ISHAContentBundles\Value\BundleManifest;

/**
 * Supplies deterministic catalog relationships in unit tests.
 */
final class FakeCatalogEligibilityProvider implements CatalogEligibilityProvider {

	/** @var int[] */
	private $legacy_product_ids;

	/** @var int[] */
	private $bundle_product_ids;

	/** @var array<int,BundleManifest> */
	private $manifests;

	/** @var array<int,int|int[]> */
	private $teacher_bundle_map;

	/** @var array<int,int> */
	private $video_teacher_map;

	/**
	 * Create the fake provider.
	 *
	 * @param int[]                     $legacy_product_ids Legacy product IDs.
	 * @param int[]                     $bundle_product_ids Bundle product IDs.
	 * @param array<int,BundleManifest> $manifests          Manifests keyed by product ID.
	 * @param array<int,int|int[]>      $teacher_bundle_map Bundle IDs keyed by teacher ID.
	 * @param array<int,int>            $video_teacher_map  Teacher IDs keyed by video ID.
	 */
	public function __construct(
		array $legacy_product_ids = array(),
		array $bundle_product_ids = array(),
		array $manifests = array(),
		array $teacher_bundle_map = array(),
		array $video_teacher_map = array()
	) {
		$this->legacy_product_ids = $legacy_product_ids;
		$this->bundle_product_ids = $bundle_product_ids;
		$this->manifests          = $manifests;
		$this->teacher_bundle_map = $teacher_bundle_map;
		$this->video_teacher_map  = $video_teacher_map;
	}

	/** {@inheritdoc} */
	public function is_legacy_product( int $product_id ): bool {
		return in_array( $product_id, $this->legacy_product_ids, true );
	}

	/** {@inheritdoc} */
	public function is_bundle_product( int $product_id ): bool {
		return in_array( $product_id, $this->bundle_product_ids, true );
	}

	/** {@inheritdoc} */
	public function get_bundle_manifest( int $product_id ): ?BundleManifest {
		return $this->manifests[ $product_id ] ?? null;
	}

	/** {@inheritdoc} */
	public function get_bundle_product_ids_for_teacher( int $teacher_id ): array {
		$value = $this->teacher_bundle_map[ $teacher_id ] ?? array();
		$value = is_array( $value ) ? $value : array( $value );

		return array_values( array_filter( array_map( 'intval', $value ) ) );
	}

	/** {@inheritdoc} */
	public function get_teacher_id_for_video( int $video_id ): ?int {
		return $this->video_teacher_map[ $video_id ] ?? null;
	}
}
