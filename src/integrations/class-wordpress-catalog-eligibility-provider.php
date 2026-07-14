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
 *
 * @since 0.2.0
 */
final class WordPressCatalogEligibilityProvider implements CatalogEligibilityProvider {

	/**
	 * Request-local bundle marker results.
	 *
	 * @var array<int,bool>
	 */
	private $bundle_product_cache = array();

	/**
	 * Request-local manifests.
	 *
	 * @var array<int,BundleManifest|null>
	 */
	private $manifest_cache = array();

	/**
	 * Request-local teacher bundle IDs.
	 *
	 * @var array<int,int|null>
	 */
	private $teacher_bundle_cache = array();

	/**
	 * Request-local video teacher IDs.
	 *
	 * @var array<int,int|null>
	 */
	private $video_teacher_cache = array();

	/**
	 * Determine whether a product is a retired individual offer.
	 *
	 * @param int $product_id Product ID.
	 * @return bool
	 *
	 * @since 0.2.0
	 */
	public function is_legacy_product( int $product_id ): bool {
		return in_array( $product_id, MigrationDefinition::legacy_product_ids(), true );
	}

	/**
	 * Determine whether a product stores a bundle manifest.
	 *
	 * @param int $product_id Product ID.
	 * @return bool
	 *
	 * @since 0.2.0
	 */
	public function is_bundle_product( int $product_id ): bool {
		if ( ! array_key_exists( $product_id, $this->bundle_product_cache ) ) {
			$this->bundle_product_cache[ $product_id ] = $product_id > 0
				&& metadata_exists( 'post', $product_id, BundleMetadata::META_TEACHER_ID );
		}

		return $this->bundle_product_cache[ $product_id ];
	}

	/**
	 * Rehydrate a stored bundle manifest.
	 *
	 * @param int $product_id Product ID.
	 * @return BundleManifest|null
	 *
	 * @since 0.2.0
	 */
	public function get_bundle_manifest( int $product_id ): ?BundleManifest {
		if ( array_key_exists( $product_id, $this->manifest_cache ) ) {
			return $this->manifest_cache[ $product_id ];
		}

		if ( ! $this->is_bundle_product( $product_id ) ) {
			$this->manifest_cache[ $product_id ] = null;
			return null;
		}

		$teacher_id = absint( get_post_meta( $product_id, BundleMetadata::META_TEACHER_ID, true ) );
		$runtime    = (float) get_post_meta( $product_id, BundleMetadata::META_RUNTIME_SECONDS, true );
		$video_ids  = get_post_meta( $product_id, BundleMetadata::META_VIDEO_IDS, true );
		$video_ids  = array_values( array_filter( array_map( 'absint', (array) $video_ids ) ) );

		if ( $teacher_id <= 0 || $runtime <= 0 || empty( $video_ids ) ) {
			$this->manifest_cache[ $product_id ] = null;
			return null;
		}

		$this->manifest_cache[ $product_id ] = new BundleManifest( $teacher_id, $video_ids, $runtime );

		return $this->manifest_cache[ $product_id ];
	}

	/**
	 * Find the one published bundle assigned to a teacher.
	 *
	 * @param int $teacher_id Teacher author ID.
	 * @return int|null
	 *
	 * @since 0.2.0
	 */
	public function get_bundle_product_id_for_teacher( int $teacher_id ): ?int {
		if ( array_key_exists( $teacher_id, $this->teacher_bundle_cache ) ) {
			return $this->teacher_bundle_cache[ $teacher_id ];
		}

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

		$this->teacher_bundle_cache[ $teacher_id ] = 1 === count( $ids ) ? absint( $ids[0] ) : null;

		return $this->teacher_bundle_cache[ $teacher_id ];
	}

	/**
	 * Get a video's teacher author ID.
	 *
	 * @param int $video_id Video post ID.
	 * @return int|null
	 *
	 * @since 0.2.0
	 */
	public function get_teacher_id_for_video( int $video_id ): ?int {
		if ( array_key_exists( $video_id, $this->video_teacher_cache ) ) {
			return $this->video_teacher_cache[ $video_id ];
		}

		$post = get_post( $video_id );

		if ( ! $post || SiteDefinition::VIDEO_POST_TYPE !== $post->post_type ) {
			$this->video_teacher_cache[ $video_id ] = null;
			return null;
		}

		$author_id                              = absint( $post->post_author );
		$this->video_teacher_cache[ $video_id ] = $author_id > 0 ? $author_id : null;

		return $this->video_teacher_cache[ $video_id ];
	}
}
