<?php
/**
 * Video route provider contract.
 *
 * @package Alynt_ISHA_Content_Bundles
 */

namespace Alynt\ISHAContentBundles\Contracts;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Supplies routing facts for protected video access decisions.
 */
interface VideoRouteProvider {

	/**
	 * Determine whether a video should be protected by the entitlement layer.
	 *
	 * @param int $video_id Video post ID.
	 * @return bool
	 */
	public function is_protected_video( int $video_id ): bool;

	/**
	 * Get the purchasable qualifying bundle URL for a video, if available.
	 *
	 * @param int $video_id Video post ID.
	 * @return string|null
	 */
	public function get_bundle_redirect_url( int $video_id ): ?string;

	/**
	 * Get the unavailable-content URL for a video.
	 *
	 * @param int $video_id Video post ID.
	 * @return string|null
	 */
	public function get_unavailable_redirect_url( int $video_id ): ?string;
}
