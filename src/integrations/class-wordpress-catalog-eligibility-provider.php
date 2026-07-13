<?php
/**
 * WordPress catalog eligibility adapter.
 *
 * @package Alynt_ISHA_Content_Bundles
 */

namespace Alynt\ISHAContentBundles\Integrations;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Alynt\ISHAContentBundles\BundleMetadata;
use Alynt\ISHAContentBundles\Contracts\CatalogEligibilityProvider;
use Alynt\ISHAContentBundles\MigrationDefinition;
use Alynt\ISHAContentBundles\SiteDefinition;
use Alynt\ISHAContentBundles\Value\BundleManifest;

/**
 * Reads product, bundle, and teacher relationships from WordPress.
 */
final class WordPressCatalogEligibilityProvider implements CatalogEligibilityProvider {

	/**
	 * Determine whether a product is a retired individual offer.
	 *
	 * @param int $product_id Product ID.
	 * @return bool
	 */
	public function is_legacy_product( int $product_id ): bool {
		return in_array( $product_id, MigrationDefinition::legacy_product_ids(), true );
	}

	/**
	 * Determine whether a product stores a bundle manifest.
	 *
	 * @param int $product_id Product ID.
	 * @return bool
	 */
	public function is_bundle_product( int $product_id ): bool {
		return $product_id > 0 && metadata_exists( 'post', $product_id, BundleMetadata::META_TEACHER_ID );
	}

	/**
	 * Rehydrate a stored bundle manifest.
	 *
	 * @param int $product_id Product ID.
	 * @return BundleManifest|null
	 */
	public function get_bundle_manifest( int $product_id ): ?BundleManifest {
		if ( ! $this->is_bundle_product( $product_id ) ) {
			return null;
		}

		$teacher_id = absint( get_post_meta( $product_id, BundleMetadata::META_TEACHER_ID, true ) );
		$runtime    = (float) get_post_meta( $product_id, BundleMetadata::META_RUNTIME_SECONDS, true );
		$video_ids  = get_post_meta( $product_id, BundleMetadata::META_VIDEO_IDS, true );
		$video_ids  = array_values( array_filter( array_map( 'absint', (array) $video_ids ) ) );

		if ( $teacher_id <= 0 || $runtime <= 0 || empty( $video_ids ) ) {
			return null;
		}

		return new BundleManifest( $teacher_id, $video_ids, $runtime );
	}

	/**
	 * Find the one published bundle assigned to a teacher.
	 *
	 * @param int $teacher_id Teacher author ID.
	 * @return int|null
	 */
	public function get_bundle_product_id_for_teacher( int $teacher_id ): ?int {
		$ids = get_posts(
			array(
				'post_type'      => SiteDefinition::PRODUCT_POST_TYPE,
				'post_status'    => 'publish',
				'posts_per_page' => 2,
				'fields'         => 'ids',
				'orderby'        => 'ID',
				'order'          => 'ASC',
				'no_found_rows'  => true,
				'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- One indexed site-specific lookup per policy decision.
					array(
						'key'     => BundleMetadata::META_TEACHER_ID,
						'value'   => (string) $teacher_id,
						'compare' => '=',
					),
				),
			)
		);

		return 1 === count( $ids ) ? absint( $ids[0] ) : null;
	}

	/**
	 * Get a video's teacher author ID.
	 *
	 * @param int $video_id Video post ID.
	 * @return int|null
	 */
	public function get_teacher_id_for_video( int $video_id ): ?int {
		$post = get_post( $video_id );

		if ( ! $post || SiteDefinition::VIDEO_POST_TYPE !== $post->post_type ) {
			return null;
		}

		$author_id = absint( $post->post_author );

		return $author_id > 0 ? $author_id : null;
	}
}
