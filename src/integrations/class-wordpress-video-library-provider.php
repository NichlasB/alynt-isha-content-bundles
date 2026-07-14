<?php
/**
 * WordPress video-library adapter.
 *
 * @package Alynt_ISHA_Content_Bundles
 */

namespace Alynt\ISHAContentBundles\Integrations;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Alynt\ISHAContentBundles\Contracts\VideoLibraryProvider;
use Alynt\ISHAContentBundles\SiteDefinition;
use Alynt\ISHAContentBundles\Value\LibraryVideo;

/**
 * Builds account-library presentation records from WordPress posts.
 *
 * @since 0.2.0
 */
final class WordPressVideoLibraryProvider implements VideoLibraryProvider {

	/**
	 * Get presentation data for a video post.
	 *
	 * @param int $video_id Video post ID.
	 * @return LibraryVideo|null
	 *
	 * @since 0.2.0
	 */
	public function get_video( int $video_id ): ?LibraryVideo {
		$post = get_post( $video_id );

		if ( ! $post || SiteDefinition::VIDEO_POST_TYPE !== $post->post_type ) {
			return null;
		}

		$author_id  = absint( $post->post_author );
		$terms      = get_the_terms( $video_id, 'category' );
		$categories = array();

		if ( is_array( $terms ) ) {
			foreach ( $terms as $term ) {
				if ( is_object( $term ) && isset( $term->name ) ) {
					$categories[] = (string) $term->name;
				}
			}
		}

		$email = (string) get_the_author_meta( 'user_email', $author_id );

		$avatar = wp_kses(
			(string) get_avatar( $email, 32 ),
			array(
				'img' => array(
					'alt'      => true,
					'class'    => true,
					'decoding' => true,
					'height'   => true,
					'loading'  => true,
					'sizes'    => true,
					'src'      => true,
					'srcset'   => true,
					'width'    => true,
				),
			)
		);

		return new LibraryVideo(
			$video_id,
			(string) get_the_title( $video_id ),
			(string) get_permalink( $video_id ),
			(string) get_the_post_thumbnail_url( $video_id, 'full' ),
			(string) get_the_author_meta( 'display_name', $author_id ),
			$email,
			$categories,
			$avatar
		);
	}
}
