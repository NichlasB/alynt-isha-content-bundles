<?php
/**
 * Fake video library provider.
 *
 * @package Alynt_ISHA_Content_Bundles
 */

namespace Alynt\ISHAContentBundles\Tests\Support;

use Alynt\ISHAContentBundles\Contracts\VideoLibraryProvider;
use Alynt\ISHAContentBundles\Value\LibraryVideo;

/**
 * Supplies deterministic library video records in unit tests.
 */
final class FakeVideoLibraryProvider implements VideoLibraryProvider {

	/** @var array<int,LibraryVideo> */
	private $videos;

	/**
	 * Create the fake provider.
	 *
	 * @param array<int,LibraryVideo> $videos Videos keyed by requested video ID.
	 */
	public function __construct( array $videos = array() ) {
		$this->videos = $videos;
	}

	/** {@inheritdoc} */
	public function get_video( int $video_id ): ?LibraryVideo {
		return $this->videos[ $video_id ] ?? null;
	}
}
