<?php
/**
 * Video library provider contract.
 *
 * @package Alynt_ISHA_Content_Bundles
 */

namespace Alynt\ISHAContentBundles\Contracts;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Alynt\ISHAContentBundles\Value\LibraryVideo;

/**
 * Supplies presentation data for entitled videos.
 */
interface VideoLibraryProvider {

	/**
	 * Get one video for the purchased-video library.
	 *
	 * @param int $video_id Video post ID.
	 * @return LibraryVideo|null
	 */
	public function get_video( int $video_id ): ?LibraryVideo;
}
