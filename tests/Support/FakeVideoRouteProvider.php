<?php
/**
 * Fake video route provider.
 *
 * @package Alynt_ISHA_Content_Bundles
 */

namespace Alynt\ISHAContentBundles\Tests\Support;

use Alynt\ISHAContentBundles\Contracts\VideoRouteProvider;

/**
 * Supplies deterministic video route facts in unit tests.
 */
final class FakeVideoRouteProvider implements VideoRouteProvider {

	/** @var int[] */
	private $protected_video_ids;

	/** @var array<int,string> */
	private $bundle_urls;

	/** @var array<int,string> */
	private $unavailable_urls;

	/**
	 * Create the fake provider.
	 *
	 * @param int[]             $protected_video_ids Protected video IDs.
	 * @param array<int,string> $bundle_urls         Bundle URLs keyed by video ID.
	 * @param array<int,string> $unavailable_urls    Unavailable URLs keyed by video ID.
	 */
	public function __construct(
		array $protected_video_ids,
		array $bundle_urls = array(),
		array $unavailable_urls = array()
	) {
		$this->protected_video_ids = array_fill_keys( $protected_video_ids, true );
		$this->bundle_urls         = $bundle_urls;
		$this->unavailable_urls    = $unavailable_urls;
	}

	/** {@inheritdoc} */
	public function is_protected_video( int $video_id ): bool {
		return isset( $this->protected_video_ids[ $video_id ] );
	}

	/** {@inheritdoc} */
	public function get_bundle_redirect_url( int $video_id ): ?string {
		return $this->bundle_urls[ $video_id ] ?? null;
	}

	/** {@inheritdoc} */
	public function get_unavailable_redirect_url( int $video_id ): ?string {
		return $this->unavailable_urls[ $video_id ] ?? null;
	}
}
