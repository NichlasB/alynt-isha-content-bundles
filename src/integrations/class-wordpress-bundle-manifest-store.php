<?php
/**
 * WordPress bundle-manifest store.
 *
 * @package Alynt_ISHA_Content_Bundles
 */

namespace Alynt\ISHAContentBundles\Integrations;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Alynt\ISHAContentBundles\BundleMetadata;
use Alynt\ISHAContentBundles\Contracts\BundleManifestStore;
use Alynt\ISHAContentBundles\Value\BundleManifest;
use RuntimeException;

/**
 * Persists approved bundle product metadata.
 *
 * @since 0.2.0
 */
final class WordPressBundleManifestStore implements BundleManifestStore {

	/**
	 * Get the currently stored bundle manifest.
	 *
	 * @param int $product_id Product ID.
	 * @return BundleManifest|null
	 *
	 * @since 0.3.0
	 */
	public function get_manifest( int $product_id ): ?BundleManifest {
		$teacher_id = absint( get_post_meta( $product_id, BundleMetadata::META_TEACHER_ID, true ) );
		$runtime    = (float) get_post_meta( $product_id, BundleMetadata::META_RUNTIME_SECONDS, true );
		$video_ids  = $this->normalize_ids( (array) get_post_meta( $product_id, BundleMetadata::META_VIDEO_IDS, true ) );

		if ( $teacher_id <= 0 || $runtime <= 0 || empty( $video_ids ) ) {
			return null;
		}

		return new BundleManifest( $teacher_id, $video_ids, $runtime );
	}

	/**
	 * Find videos already assigned to other non-trashed bundles.
	 *
	 * @param int   $product_id Product being edited.
	 * @param int[] $video_ids  Candidate video IDs.
	 * @return array<int,int[]> Bundle product IDs keyed by conflicting video ID.
	 *
	 * @since 0.3.0
	 */
	public function get_video_conflicts( int $product_id, array $video_ids ): array {
		$candidates = array_fill_keys( $this->normalize_ids( $video_ids ), true );
		$conflicts  = array();

		if ( empty( $candidates ) ) {
			return $conflicts;
		}

		$bundle_ids = get_posts(
			array(
				'post_type'      => 'product',
				'post_status'    => array( 'publish', 'future', 'draft', 'pending', 'private' ),
				'posts_per_page' => -1,
				'post__not_in'   => array( $product_id ),
				'fields'         => 'ids',
				'orderby'        => 'ID',
				'order'          => 'ASC',
				'no_found_rows'  => true,
				'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Administrator-only integrity check over the small managed product set.
					array(
						'key'     => BundleMetadata::META_TEACHER_ID,
						'compare' => 'EXISTS',
					),
				),
			)
		);

		foreach ( $bundle_ids as $bundle_id ) {
			$assigned_ids = $this->normalize_ids( (array) get_post_meta( $bundle_id, BundleMetadata::META_VIDEO_IDS, true ) );
			foreach ( $assigned_ids as $video_id ) {
				if ( isset( $candidates[ $video_id ] ) ) {
					$conflicts[ $video_id ][] = absint( $bundle_id );
				}
			}
		}

		ksort( $conflicts, SORT_NUMERIC );

		return $conflicts;
	}

	/**
	 * Count completed orders containing a bundle product.
	 *
	 * @param int $product_id Product ID.
	 * @return int
	 *
	 * @since 0.3.0
	 */
	public function get_completed_order_count( int $product_id ): int {
		if ( $product_id <= 0 || ! function_exists( 'wc_get_orders' ) ) {
			return 0;
		}

		$count = 0;
		$page  = 1;

		do {
			$result = wc_get_orders(
				array(
					'status'   => array( 'wc-completed' ),
					'limit'    => 100,
					'page'     => $page,
					'paginate' => true,
				)
			);
			$orders = is_object( $result ) && isset( $result->orders ) ? (array) $result->orders : array();

			foreach ( $orders as $order ) {
				if ( ! is_object( $order ) || ! method_exists( $order, 'get_items' ) ) {
					continue;
				}

				foreach ( $order->get_items( 'line_item' ) as $item ) {
					if ( is_object( $item ) && method_exists( $item, 'get_product_id' ) && absint( $item->get_product_id() ) === $product_id ) {
						++$count;
						break;
					}
				}
			}

			$max_pages = is_object( $result ) && isset( $result->max_num_pages ) ? absint( $result->max_num_pages ) : 0;
			++$page;
		} while ( ! empty( $orders ) && $page <= $max_pages );

		return $count;
	}

	/**
	 * Save a product bundle manifest and approved commercial fields.
	 *
	 * @param int            $product_id Product ID.
	 * @param BundleManifest $manifest   Validated manifest.
	 * @return void
	 *
	 * @since 0.2.0
	 */
	public function save_manifest( int $product_id, BundleManifest $manifest ): void {
		$this->write_meta( $product_id, BundleMetadata::META_VIDEO_IDS, $manifest->get_video_ids() );
		$this->write_meta( $product_id, BundleMetadata::META_TEACHER_ID, $manifest->get_teacher_id() );
		$this->write_meta( $product_id, BundleMetadata::META_RUNTIME_SECONDS, $manifest->get_runtime_seconds() );
		$this->write_meta( $product_id, BundleMetadata::META_QUALIFIES, $manifest->qualifies() ? 'yes' : 'no' );
		$this->write_meta( $product_id, '_regular_price', BundleMetadata::BUNDLE_PRICE );
		$this->write_meta( $product_id, '_price', BundleMetadata::BUNDLE_PRICE );
		$this->write_meta( $product_id, '_virtual', 'yes' );
	}

	/**
	 * Delete plugin-owned bundle metadata.
	 *
	 * @param int $product_id Product ID.
	 * @return void
	 * @throws RuntimeException When metadata cannot be removed.
	 *
	 * @since 0.2.0
	 */
	public function delete_manifest( int $product_id ): void {
		foreach ( $this->get_manifest_meta_keys() as $meta_key ) {
			if ( ! delete_post_meta( $product_id, $meta_key ) && metadata_exists( 'post', $product_id, $meta_key ) ) {
				throw new RuntimeException( 'Bundle metadata could not be removed.' );
			}
		}
	}

	/**
	 * Append a sold-bundle removal audit entry.
	 *
	 * @param int    $product_id           Bundle product ID.
	 * @param int    $user_id              Administrator user ID.
	 * @param int[]  $removed_video_ids     Removed video IDs.
	 * @param string $reason                Administrator-supplied reason.
	 * @param int    $completed_order_count Affected completed-order count.
	 * @return void
	 * @throws RuntimeException When the audit entry cannot be confirmed.
	 *
	 * @since 0.3.0
	 */
	public function record_removal_audit(
		int $product_id,
		int $user_id,
		array $removed_video_ids,
		string $reason,
		int $completed_order_count
	): void {
		$reason = sanitize_textarea_field( $reason );
		if ( '' === $reason ) {
			throw new RuntimeException( 'Bundle removal audit reason is empty.' );
		}

		$entry = array(
			'version'               => 1,
			'user_id'               => absint( $user_id ),
			'occurred_at_utc'       => gmdate( 'c' ),
			'removed_video_ids'     => $this->normalize_ids( $removed_video_ids ),
			'reason'                => $reason,
			'completed_order_count' => max( 0, $completed_order_count ),
		);

		if ( false === add_post_meta( $product_id, BundleMetadata::META_MANIFEST_AUDIT, $entry, false ) ) {
			throw new RuntimeException( 'Bundle removal audit could not be saved.' );
		}
	}

	/**
	 * Write and verify one metadata value.
	 *
	 * WordPress returns false when the value is unchanged, so the stored value is
	 * checked before treating that result as a failure.
	 *
	 * @param int    $product_id Product ID.
	 * @param string $meta_key   Metadata key.
	 * @param mixed  $value      Metadata value.
	 * @return void
	 * @throws RuntimeException When the value cannot be confirmed.
	 */
	private function write_meta( int $product_id, string $meta_key, $value ): void {
		$result = update_post_meta( $product_id, $meta_key, $value );
		if ( false !== $result ) {
			return;
		}

		$stored  = get_post_meta( $product_id, $meta_key, true );
		$matches = is_array( $value ) ? $stored === $value : (string) $stored === (string) $value;
		if ( ! $matches ) {
			throw new RuntimeException( 'Bundle metadata could not be saved.' );
		}
	}

	/**
	 * Get plugin-owned manifest keys.
	 *
	 * @return string[]
	 */
	private function get_manifest_meta_keys(): array {
		return array(
			BundleMetadata::META_VIDEO_IDS,
			BundleMetadata::META_TEACHER_ID,
			BundleMetadata::META_RUNTIME_SECONDS,
			BundleMetadata::META_QUALIFIES,
		);
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
