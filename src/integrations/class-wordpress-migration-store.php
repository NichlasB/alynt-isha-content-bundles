<?php
/**
 * WordPress migration store.
 *
 * @package Alynt_ISHA_Content_Bundles
 */

namespace Alynt\ISHAContentBundles\Integrations;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Alynt\ISHAContentBundles\BundleMetadata;
use Alynt\ISHAContentBundles\Contracts\MigrationStore;
use Alynt\ISHAContentBundles\MigrationDefinition;
use Alynt\ISHAContentBundles\SiteDefinition;
use Alynt\ISHAContentBundles\Value\MigrationSnapshot;
use RuntimeException;

/**
 * Reads, snapshots, replaces, verifies, and restores migration state.
 *
 * @since 0.2.0
 */
final class WordPressMigrationStore implements MigrationStore {

	/**
	 * Post-state store.
	 *
	 * @var WordPressPostStateStore
	 */
	private $post_store;

	/**
	 * Term-state store.
	 *
	 * @var WordPressTermStateStore
	 */
	private $term_store;

	/**
	 * Create the migration store.
	 *
	 * @param WordPressPostStateStore|null $post_store Post-state store.
	 * @param WordPressTermStateStore|null $term_store Term-state store.
	 *
	 * @since 0.2.0
	 */
	public function __construct( ?WordPressPostStateStore $post_store = null, ?WordPressTermStateStore $term_store = null ) {
		$this->post_store = $post_store ?? new WordPressPostStateStore();
		$this->term_store = $term_store ?? new WordPressTermStateStore();
	}

	/**
	 * Get all relationship rows for a video.
	 *
	 * @param int $video_id Video post ID.
	 * @return string[]
	 *
	 * @since 0.2.0
	 */
	public function get_relationship_values( int $video_id ): array {
		return array_map( 'strval', get_post_meta( $video_id, SiteDefinition::RELATIONSHIP_META, false ) );
	}

	/**
	 * Replace all relationship rows for a video.
	 *
	 * @param int      $video_id Video post ID.
	 * @param string[] $values   Replacement values.
	 * @return void
	 * @throws RuntimeException When a row cannot be written.
	 *
	 * @since 0.2.0
	 */
	public function replace_relationship_values( int $video_id, array $values ): void {
		delete_post_meta( $video_id, SiteDefinition::RELATIONSHIP_META );
		foreach ( $values as $value ) {
			if ( false === add_post_meta( $video_id, SiteDefinition::RELATIONSHIP_META, (string) $value, false ) ) {
				throw new RuntimeException( 'A video relationship row could not be written.' );
			}
		}
	}

	/**
	 * Capture and persist all protected pre-write state.
	 *
	 * @return MigrationSnapshot
	 * @throws RuntimeException When the snapshot cannot be persisted.
	 *
	 * @since 0.2.0
	 */
	public function capture_snapshot(): MigrationSnapshot {
		$snapshot = $this->build_current_snapshot();
		if ( ! update_option( SiteDefinition::SNAPSHOT_OPTION, $snapshot->to_array(), false ) ) {
			$stored = get_option( SiteDefinition::SNAPSHOT_OPTION, null );
			if ( $stored !== $snapshot->to_array() ) {
				throw new RuntimeException( 'The migration snapshot could not be persisted.' );
			}
		}

		return $snapshot;
	}

	/**
	 * Restore all logical state in a snapshot.
	 *
	 * @param MigrationSnapshot $snapshot Rollback snapshot.
	 * @return void
	 * @throws RuntimeException When protected state cannot be restored.
	 *
	 * @since 0.2.0
	 */
	public function restore_snapshot( MigrationSnapshot $snapshot ): void {
		foreach ( $snapshot->get_relationships() as $video_id => $values ) {
			$this->replace_relationship_values( (int) $video_id, $values );
		}
		foreach ( array_merge( $snapshot->get_products(), $snapshot->get_teachers() ) as $state ) {
			if ( $state !== $this->post_store->capture( $this->post_store->get_state_id( $state ) ) ) {
				$this->post_store->restore( $state );
			}
		}
		foreach ( $snapshot->get_scripts() as $state ) {
			$term_id = ! empty( $state['term']['term_id'] ) ? absint( $state['term']['term_id'] ) : absint( $state['term_id'] ?? 0 );
			if ( $state !== $this->term_store->capture( $term_id ) ) {
				$this->term_store->restore( $state );
			}
		}
	}

	/**
	 * Determine whether current state matches a snapshot.
	 *
	 * @param MigrationSnapshot $snapshot Rollback snapshot.
	 * @return bool
	 *
	 * @since 0.2.0
	 */
	public function snapshot_matches( MigrationSnapshot $snapshot ): bool {
		return $snapshot->to_array() === $this->build_current_snapshot()->to_array();
	}

	/**
	 * Load the most recently persisted pre-write snapshot.
	 *
	 * @return MigrationSnapshot|null
	 * @throws \InvalidArgumentException When persisted data is incompatible.
	 *
	 * @since 0.2.0
	 */
	public function load_persisted_snapshot(): ?MigrationSnapshot {
		$stored = get_option( SiteDefinition::SNAPSHOT_OPTION, null );

		return is_array( $stored ) ? MigrationSnapshot::from_array( $stored ) : null;
	}

	/** Capture current protected state without writing it. @return MigrationSnapshot */
	private function build_current_snapshot(): MigrationSnapshot {
		$relationships = array();
		foreach ( array_keys( MigrationDefinition::target_relationships() ) as $video_id ) {
			$relationships[ $video_id ] = $this->get_relationship_values( $video_id );
		}

		$product_ids = MigrationDefinition::legacy_product_ids();
		$bundle_ids  = get_posts(
			array(
				'post_type'      => SiteDefinition::PRODUCT_POST_TYPE,
				'post_status'    => 'any',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'no_found_rows'  => true,
				'meta_key'       => BundleMetadata::META_TEACHER_ID, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Limited administrative snapshot query.
			)
		);
		$product_ids = array_values( array_unique( array_merge( $product_ids, array_map( 'absint', $bundle_ids ) ) ) );

		$products = array();
		foreach ( $product_ids as $product_id ) {
			$products[ $product_id ] = $this->post_store->capture( $product_id );
		}
		$teachers = array();
		foreach ( MigrationDefinition::teacher_post_ids() as $teacher_id ) {
			$teachers[ $teacher_id ] = $this->post_store->capture( $teacher_id );
		}
		$scripts = array();
		foreach ( MigrationDefinition::script_term_ids() as $term_id ) {
			$scripts[ $term_id ] = $this->term_store->capture( $term_id );
		}

		return new MigrationSnapshot( $relationships, $products, $teachers, $scripts );
	}
}
