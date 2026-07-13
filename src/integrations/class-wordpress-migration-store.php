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
 * Reads, snapshots, replaces, verifies, and restores WordPress migration state.
 */
final class WordPressMigrationStore implements MigrationStore {

	/**
	 * Get every stored relationship row for a video.
	 *
	 * @param int $video_id Video post ID.
	 * @return string[]
	 */
	public function get_relationship_values( int $video_id ): array {
		return array_map( 'strval', get_post_meta( $video_id, SiteDefinition::RELATIONSHIP_META, false ) );
	}

	/**
	 * Replace every relationship row for a video.
	 *
	 * @param int      $video_id Video post ID.
	 * @param string[] $values   Exact replacement values.
	 * @return void
	 * @throws RuntimeException When a row cannot be written.
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
	 * Capture and durably persist all protected pre-write state.
	 *
	 * @return MigrationSnapshot
	 * @throws RuntimeException When the snapshot cannot be persisted.
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
	 */
	public function restore_snapshot( MigrationSnapshot $snapshot ): void {
		foreach ( $snapshot->get_relationships() as $video_id => $values ) {
			$this->replace_relationship_values( (int) $video_id, $values );
		}

		foreach ( $snapshot->get_products() as $state ) {
			if ( $state !== $this->capture_post_state( $this->get_state_id( $state ) ) ) {
				$this->restore_post_state( $state );
			}
		}

		foreach ( $snapshot->get_teachers() as $state ) {
			if ( $state !== $this->capture_post_state( $this->get_state_id( $state ) ) ) {
				$this->restore_post_state( $state );
			}
		}

		foreach ( $snapshot->get_scripts() as $state ) {
			$term_id = ! empty( $state['term']['term_id'] ) ? absint( $state['term']['term_id'] ) : absint( $state['term_id'] ?? 0 );
			if ( $state !== $this->capture_term_state( $term_id ) ) {
				$this->restore_term_state( $state );
			}
		}
	}

	/**
	 * Determine whether current state matches a snapshot.
	 *
	 * @param MigrationSnapshot $snapshot Rollback snapshot.
	 * @return bool
	 */
	public function snapshot_matches( MigrationSnapshot $snapshot ): bool {
		return $snapshot->to_array() === $this->build_current_snapshot()->to_array();
	}

	/**
	 * Load the most recently persisted pre-write snapshot.
	 *
	 * @return MigrationSnapshot|null
	 * @throws \InvalidArgumentException When persisted data is incompatible.
	 */
	public function load_persisted_snapshot(): ?MigrationSnapshot {
		$stored = get_option( SiteDefinition::SNAPSHOT_OPTION, null );

		return is_array( $stored ) ? MigrationSnapshot::from_array( $stored ) : null;
	}

	/**
	 * Capture current protected state without writing it.
	 *
	 * @return MigrationSnapshot
	 */
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
			$products[ $product_id ] = $this->capture_post_state( $product_id );
		}

		$teachers = array();
		foreach ( MigrationDefinition::teacher_post_ids() as $teacher_id ) {
			$teachers[ $teacher_id ] = $this->capture_post_state( $teacher_id );
		}

		$scripts = array();
		foreach ( MigrationDefinition::script_term_ids() as $term_id ) {
			$scripts[ $term_id ] = $this->capture_term_state( $term_id );
		}

		return new MigrationSnapshot( $relationships, $products, $teachers, $scripts );
	}

	/**
	 * Capture one post, all meta rows, and taxonomy assignments.
	 *
	 * @param int $post_id Post ID.
	 * @return array<string,mixed>
	 */
	private function capture_post_state( int $post_id ): array {
		$post = get_post( $post_id, ARRAY_A );

		if ( ! is_array( $post ) ) {
			return array(
				'missing' => true,
				'ID'      => $post_id,
			);
		}

		$post_fields = array(
			'ID',
			'post_author',
			'post_date',
			'post_date_gmt',
			'post_content',
			'post_title',
			'post_excerpt',
			'post_status',
			'comment_status',
			'ping_status',
			'post_password',
			'post_name',
			'to_ping',
			'pinged',
			'post_content_filtered',
			'post_parent',
			'menu_order',
			'post_type',
			'post_mime_type',
			'comment_count',
		);
		$post        = array_intersect_key( $post, array_flip( $post_fields ) );
		$terms       = array();
		foreach ( get_object_taxonomies( $post['post_type'] ) as $taxonomy ) {
			$term_ids           = wp_get_object_terms( $post_id, $taxonomy, array( 'fields' => 'ids' ) );
			$terms[ $taxonomy ] = is_wp_error( $term_ids ) ? array() : array_map( 'intval', $term_ids );
		}

		return array(
			'missing' => false,
			'post'    => $post,
			'meta'    => get_post_meta( $post_id ),
			'terms'   => $terms,
		);
	}

	/**
	 * Restore one captured post state.
	 *
	 * @param array<string,mixed> $state Captured state.
	 * @return void
	 * @throws RuntimeException When the post or taxonomy state cannot be restored.
	 */
	private function restore_post_state( array $state ): void {
		if ( ! empty( $state['missing'] ) || empty( $state['post']['ID'] ) ) {
			return;
		}

		$post_id = absint( $state['post']['ID'] );
		$result  = wp_update_post( wp_slash( $state['post'] ), true );
		if ( is_wp_error( $result ) ) {
			throw new RuntimeException( 'A protected post could not be restored.' );
		}

		$this->replace_post_meta( $post_id, (array) $state['meta'] );
		foreach ( (array) $state['terms'] as $taxonomy => $term_ids ) {
			$result = wp_set_object_terms( $post_id, array_map( 'intval', $term_ids ), $taxonomy, false );
			if ( is_wp_error( $result ) ) {
				throw new RuntimeException( 'A protected taxonomy assignment could not be restored.' );
			}
		}
	}

	/**
	 * Replace all post metadata with captured rows.
	 *
	 * @param int   $post_id Post ID.
	 * @param array $meta    Metadata rows keyed by key.
	 * @return void
	 */
	private function replace_post_meta( int $post_id, array $meta ): void {
		$current_keys = array_keys( get_post_meta( $post_id ) );
		foreach ( $current_keys as $meta_key ) {
			delete_post_meta( $post_id, $meta_key );
		}

		foreach ( $meta as $meta_key => $values ) {
			foreach ( (array) $values as $value ) {
				add_post_meta( $post_id, $meta_key, maybe_unserialize( $value ), false );
			}
		}
	}

	/**
	 * Capture one Advanced Scripts taxonomy term and all term meta.
	 *
	 * @param int $term_id Term ID.
	 * @return array<string,mixed>
	 */
	private function capture_term_state( int $term_id ): array {
		$term = get_term( $term_id );

		if ( ! $term || is_wp_error( $term ) ) {
			return array(
				'missing' => true,
				'term_id' => $term_id,
			);
		}

		$term_data = $term->to_array();

		return array(
			'missing' => false,
			'term'    => array_intersect_key(
				$term_data,
				array_flip( array( 'term_id', 'name', 'slug', 'taxonomy', 'description', 'parent' ) )
			),
			'meta'    => get_term_meta( $term_id ),
		);
	}

	/**
	 * Restore one Advanced Scripts taxonomy term.
	 *
	 * @param array<string,mixed> $state Captured state.
	 * @return void
	 * @throws RuntimeException When the term state cannot be restored.
	 */
	private function restore_term_state( array $state ): void {
		if ( ! empty( $state['missing'] ) || empty( $state['term']['term_id'] ) ) {
			return;
		}

		$term     = $state['term'];
		$term_id  = absint( $term['term_id'] );
		$taxonomy = (string) $term['taxonomy'];
		$result   = wp_update_term(
			$term_id,
			$taxonomy,
			array(
				'name'        => $term['name'],
				'slug'        => $term['slug'],
				'description' => $term['description'],
				'parent'      => absint( $term['parent'] ),
			)
		);

		if ( is_wp_error( $result ) ) {
			throw new RuntimeException( 'An Advanced Scripts term could not be restored.' );
		}

		foreach ( array_keys( get_term_meta( $term_id ) ) as $meta_key ) {
			delete_term_meta( $term_id, $meta_key );
		}
		foreach ( (array) $state['meta'] as $meta_key => $values ) {
			foreach ( (array) $values as $value ) {
				add_term_meta( $term_id, $meta_key, maybe_unserialize( $value ), false );
			}
		}
	}

	/**
	 * Get a post ID from a captured state.
	 *
	 * @param array<string,mixed> $state Captured post state.
	 * @return int
	 */
	private function get_state_id( array $state ): int {
		return ! empty( $state['post']['ID'] ) ? absint( $state['post']['ID'] ) : absint( $state['ID'] ?? 0 );
	}
}
