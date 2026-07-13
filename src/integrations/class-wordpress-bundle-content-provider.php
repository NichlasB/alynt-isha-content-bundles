<?php
/**
 * WordPress bundle-content adapter.
 *
 * @package Alynt_ISHA_Content_Bundles
 */

namespace Alynt\ISHAContentBundles\Integrations;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Alynt\ISHAContentBundles\Contracts\BundleContentProvider;
use Alynt\ISHAContentBundles\SiteDefinition;
use Alynt\ISHAContentBundles\Value\BundleVideo;

/**
 * Supplies verified WordPress video facts for manifest validation.
 */
final class WordPressBundleContentProvider implements BundleContentProvider {

	/**
	 * Get a verified video record.
	 *
	 * @param int $video_id Video post ID.
	 * @return BundleVideo|null
	 */
	public function get_video( int $video_id ): ?BundleVideo {
		$post = get_post( $video_id );

		if ( ! $post || SiteDefinition::VIDEO_POST_TYPE !== $post->post_type ) {
			return null;
		}

		$runtime = (float) get_post_meta( $video_id, SiteDefinition::RUNTIME_META, true );
		if ( 0 >= $runtime ) {
			$runtimes = SiteDefinition::video_runtimes();
			$runtime  = isset( $runtimes[ $video_id ] ) ? (float) $runtimes[ $video_id ] : 0.0;
		}

		if ( 0 >= $runtime ) {
			return null;
		}

		$retained = get_post_meta( $video_id, SiteDefinition::RETAINED_META, true );

		return new BundleVideo(
			$video_id,
			absint( $post->post_author ),
			(string) $post->post_status,
			$runtime,
			'yes' === $retained
		);
	}
}
