<?php
/**
 * WordPress post-state snapshot adapter.
 *
 * @package Alynt_ISHA_Content_Bundles
 */

namespace Alynt\ISHAContentBundles\Integrations;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use RuntimeException;

/**
 * Captures and restores a post, its metadata, and taxonomy assignments.
 *
 * @since 0.2.0
 */
final class WordPressPostStateStore {

	/**
	 * Capture one post and its related state.
	 *
	 * @param int $post_id Post ID.
	 * @return array<string,mixed>
	 *
	 * @since 0.2.0
	 */
	public function capture( int $post_id ): array {
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
	 * @throws RuntimeException When protected state cannot be restored.
	 *
	 * @since 0.2.0
	 */
	public function restore( array $state ): void {
		if ( ! empty( $state['missing'] ) || empty( $state['post']['ID'] ) ) {
			return;
		}

		$post_id = absint( $state['post']['ID'] );
		$result  = wp_update_post( wp_slash( $state['post'] ), true );
		if ( is_wp_error( $result ) ) {
			throw new RuntimeException( 'A protected post could not be restored.' );
		}

		$this->replace_meta( $post_id, (array) $state['meta'] );
		foreach ( (array) $state['terms'] as $taxonomy => $term_ids ) {
			$result = wp_set_object_terms( $post_id, array_map( 'intval', $term_ids ), $taxonomy, false );
			if ( is_wp_error( $result ) ) {
				throw new RuntimeException( 'A protected taxonomy assignment could not be restored.' );
			}
		}
	}

	/**
	 * Get a post ID from captured state.
	 *
	 * @param array<string,mixed> $state Captured state.
	 * @return int
	 *
	 * @since 0.2.0
	 */
	public function get_state_id( array $state ): int {
		return ! empty( $state['post']['ID'] ) ? absint( $state['post']['ID'] ) : absint( $state['ID'] ?? 0 );
	}

	/**
	 * Replace all post metadata with captured rows.
	 *
	 * @param int   $post_id Post ID.
	 * @param array $meta    Captured metadata.
	 * @return void
	 * @throws RuntimeException When a metadata row cannot be restored.
	 */
	private function replace_meta( int $post_id, array $meta ): void {
		foreach ( array_keys( get_post_meta( $post_id ) ) as $meta_key ) {
			delete_post_meta( $post_id, $meta_key );
		}

		foreach ( $meta as $meta_key => $values ) {
			foreach ( (array) $values as $value ) {
				if ( false === add_post_meta( $post_id, $meta_key, $value, false ) ) {
					throw new RuntimeException( 'A protected post metadata row could not be restored.' );
				}
			}
		}
	}
}
