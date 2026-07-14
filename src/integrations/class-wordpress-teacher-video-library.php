<?php
/**
 * WordPress teacher-video library adapter.
 *
 * @package Alynt_ISHA_Content_Bundles
 */

namespace Alynt\ISHAContentBundles\Integrations;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Alynt\ISHAContentBundles\Contracts\CatalogEligibilityProvider;
use Alynt\ISHAContentBundles\Contracts\VideoLibraryProvider;
use Alynt\ISHAContentBundles\SiteDefinition;

/**
 * Resolves the published videos in a teacher's explicit qualifying manifest.
 *
 * @since 0.2.0
 */
final class WordPressTeacherVideoLibrary {

	/**
	 * Catalog provider.
	 *
	 * @var CatalogEligibilityProvider
	 */
	private $catalog_provider;

	/**
	 * Video presentation provider.
	 *
	 * @var VideoLibraryProvider
	 */
	private $video_provider;

	/**
	 * Create the teacher library.
	 *
	 * @param CatalogEligibilityProvider $catalog_provider Catalog provider.
	 * @param VideoLibraryProvider       $video_provider   Video provider.
	 *
	 * @since 0.2.0
	 */
	public function __construct( CatalogEligibilityProvider $catalog_provider, VideoLibraryProvider $video_provider ) {
		$this->catalog_provider = $catalog_provider;
		$this->video_provider   = $video_provider;
	}

	/**
	 * Get published manifest videos in reverse chronological order.
	 *
	 * @param int $teacher_id Teacher author ID.
	 * @return \Alynt\ISHAContentBundles\Value\LibraryVideo[]
	 *
	 * @since 0.2.0
	 */
	public function get_videos( int $teacher_id ): array {
		$product_id = $this->catalog_provider->get_bundle_product_id_for_teacher( $teacher_id );
		$manifest   = null === $product_id ? null : $this->catalog_provider->get_bundle_manifest( $product_id );

		if ( null === $manifest || $manifest->get_teacher_id() !== $teacher_id || ! $manifest->qualifies() ) {
			return array();
		}

		$ids    = get_posts(
			array(
				'post_type'      => SiteDefinition::VIDEO_POST_TYPE,
				'post_status'    => 'publish',
				'posts_per_page' => count( $manifest->get_video_ids() ),
				'post__in'       => $manifest->get_video_ids(),
				'fields'         => 'ids',
				'author'         => $teacher_id,
				'orderby'        => 'date',
				'order'          => 'DESC',
				'no_found_rows'  => true,
			)
		);
		$videos = array();

		foreach ( $ids as $video_id ) {
			$video = $this->video_provider->get_video( absint( $video_id ) );
			if ( null !== $video ) {
				$videos[] = $video;
			}
		}

		return $videos;
	}
}
