<?php
/**
 * WordPress term-state snapshot adapter.
 *
 * @package Alynt_ISHA_Content_Bundles
 */

namespace Alynt\ISHAContentBundles\Integrations;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use RuntimeException;

/**
 * Captures and restores an Advanced Scripts taxonomy term and its metadata.
 *
 * @since 0.2.0
 */
final class WordPressTermStateStore {

	/**
	 * Capture one term and all metadata rows.
	 *
	 * @param int $term_id Term ID.
	 * @return array<string,mixed>
	 *
	 * @since 0.2.0
	 */
	public function capture( int $term_id ): array {
		$term = get_term( $term_id );
		if ( ! $term || is_wp_error( $term ) ) {
			return array(
				'missing' => true,
				'term_id' => $term_id,
			);
		}

		return array(
			'missing' => false,
			'term'    => array_intersect_key(
				$term->to_array(),
				array_flip( array( 'term_id', 'name', 'slug', 'taxonomy', 'description', 'parent' ) )
			),
			'meta'    => get_term_meta( $term_id ),
		);
	}

	/**
	 * Restore one captured term state.
	 *
	 * @param array<string,mixed> $state Captured state.
	 * @return void
	 * @throws RuntimeException When the term state cannot be restored.
	 *
	 * @since 0.2.0
	 */
	public function restore( array $state ): void {
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
				if ( false === add_term_meta( $term_id, $meta_key, $value, false ) ) {
					throw new RuntimeException( 'A protected term metadata row could not be restored.' );
				}
			}
		}
	}
}
