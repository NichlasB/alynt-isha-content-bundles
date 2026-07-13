<?php
/**
 * Fake bundle content provider.
 *
 * @package Alynt_ISHA_Content_Bundles
 */

namespace Alynt\ISHAContentBundles\Tests\Support;

use Alynt\ISHAContentBundles\Contracts\BundleContentProvider;
use Alynt\ISHAContentBundles\Value\BundleVideo;

/**
 * Test double for bundle video facts.
 */
final class FakeBundleContentProvider implements BundleContentProvider {

	/**
	 * Videos keyed by ID.
	 *
	 * @var array<int,BundleVideo>
	 */
	private $videos;

	/**
	 * Create the fake provider.
	 *
	 * @param array<int,BundleVideo> $videos Videos keyed by ID.
	 */
	public function __construct( array $videos ) {
		$this->videos = $videos;
	}

	/**
	 * Get a video record for manifest validation.
	 *
	 * @param int $video_id Video post ID.
	 * @return BundleVideo|null
	 */
	public function get_video( int $video_id ): ?BundleVideo {
		return $this->videos[ $video_id ] ?? null;
	}
}
