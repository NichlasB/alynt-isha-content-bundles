<?php
/**
 * WordPress access and content-map adapter.
 *
 * @package Alynt_ISHA_Content_Bundles
 */

namespace Alynt\ISHAContentBundles\Integrations;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Alynt\ISHAContentBundles\BundleMetadata;
use Alynt\ISHAContentBundles\Contracts\ContentMapProvider;
use Alynt\ISHAContentBundles\Contracts\UserAccessProvider;
use Alynt\ISHAContentBundles\MigrationDefinition;
use Alynt\ISHAContentBundles\SiteDefinition;

/**
 * Supplies entitlement facts from WordPress.
 */
final class WordPressAccessContentProvider implements UserAccessProvider, ContentMapProvider {

	/**
	 * Determine whether a user has administrator content access.
	 *
	 * @param int $user_id WordPress user ID.
	 * @return bool
	 */
	public function is_administrator( int $user_id ): bool {
		return $user_id > 0 && user_can( $user_id, 'manage_options' );
	}

	/**
	 * Get the video linked to a retired individual product.
	 *
	 * @param int $product_id Product ID.
	 * @return int|null
	 */
	public function get_legacy_video_id( int $product_id ): ?int {
		$video_id = array_search( $product_id, MigrationDefinition::legacy_product_map(), true );

		return false === $video_id ? null : (int) $video_id;
	}

	/**
	 * Get the explicit video manifest for a bundle product.
	 *
	 * @param int $product_id Product ID.
	 * @return int[]
	 */
	public function get_bundle_video_ids( int $product_id ): array {
		$value = get_post_meta( $product_id, BundleMetadata::META_VIDEO_IDS, true );

		return $this->normalize_ids( is_array( $value ) ? $value : preg_split( '/[\s,]+/', (string) $value ) );
	}

	/**
	 * Get every video available to administrators.
	 *
	 * @return int[]
	 */
	public function get_all_video_ids(): array {
		$ids = get_posts(
			array(
				'post_type'      => SiteDefinition::VIDEO_POST_TYPE,
				'post_status'    => 'any',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'orderby'        => 'ID',
				'order'          => 'ASC',
				'no_found_rows'  => true,
			)
		);

		return $this->normalize_ids( (array) $ids );
	}

	/**
	 * Normalize positive IDs.
	 *
	 * @param array $ids Candidate IDs.
	 * @return int[]
	 */
	private function normalize_ids( array $ids ): array {
		$normalized = array();

		foreach ( $ids as $id ) {
			$id = absint( $id );
			if ( $id > 0 ) {
				$normalized[ $id ] = $id;
			}
		}

		return array_values( $normalized );
	}
}
