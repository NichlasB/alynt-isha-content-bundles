<?php
/**
 * Bundle content provider contract.
 *
 * @package Alynt_ISHA_Content_Bundles
 */

namespace Alynt\ISHAContentBundles\Contracts;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Alynt\ISHAContentBundles\Value\BundleVideo;

/**
 * Supplies video facts needed to validate bundle manifests.
 */
interface BundleContentProvider {

	/**
	 * Get a video record for manifest validation.
	 *
	 * @param int $video_id Video post ID.
	 * @return BundleVideo|null
	 */
	public function get_video( int $video_id ): ?BundleVideo;
}
