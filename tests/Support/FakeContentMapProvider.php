<?php
/**
 * Fake content map provider.
 *
 * @package Alynt_ISHA_Content_Bundles
 */

namespace Alynt\ISHAContentBundles\Tests\Support;

use Alynt\ISHAContentBundles\Contracts\ContentMapProvider;

/**
 * Supplies deterministic product-to-video mappings in unit tests.
 */
final class FakeContentMapProvider implements ContentMapProvider {

	/** @var array<int,int> */
	private $legacy_map;

	/** @var array<int,array> */
	private $bundle_map;

	/** @var array */
	private $all_video_ids;

	/**
	 * Create the fake provider.
	 *
	 * @param array<int,int>   $legacy_map   Legacy product mappings.
	 * @param array<int,array> $bundle_map   Bundle product mappings.
	 * @param array            $all_video_ids All known video IDs.
	 */
	public function __construct(
		array $legacy_map = array(),
		array $bundle_map = array(),
		array $all_video_ids = array()
	) {
		$this->legacy_map   = $legacy_map;
		$this->bundle_map   = $bundle_map;
		$this->all_video_ids = $all_video_ids;
	}

	/** {@inheritdoc} */
	public function get_legacy_video_id( int $product_id ): ?int {
		return $this->legacy_map[ $product_id ] ?? null;
	}

	/** {@inheritdoc} */
	public function get_bundle_video_ids( int $product_id ): array {
		return $this->bundle_map[ $product_id ] ?? array();
	}

	/** {@inheritdoc} */
	public function get_all_video_ids(): array {
		return $this->all_video_ids;
	}
}
