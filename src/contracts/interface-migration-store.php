<?php
/**
 * Migration store contract.
 *
 * @package Alynt_ISHA_Content_Bundles
 */

namespace Alynt\ISHAContentBundles\Contracts;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Alynt\ISHAContentBundles\Value\MigrationSnapshot;

/**
 * Reads migration state and performs exact approval-gated replacements.
 *
 * @since 0.2.0
 */
interface MigrationStore {

	/**
	 * Get every stored relationship row value for a video.
	 *
	 * @param int $video_id Video post ID.
	 * @return string[]
	 *
	 * @since 0.2.0
	 */
	public function get_relationship_values( int $video_id ): array;

	/**
	 * Replace every relationship row for a video.
	 *
	 * @param int      $video_id Video post ID.
	 * @param string[] $values   Exact replacement values.
	 * @return void
	 *
	 * @since 0.2.0
	 */
	public function replace_relationship_values( int $video_id, array $values ): void;

	/**
	 * Capture and durably persist the complete pre-write rollback state.
	 *
	 * The runtime adapter must include relationships, protected products,
	 * teachers, and Advanced Scripts state before returning the snapshot.
	 *
	 * @return MigrationSnapshot
	 *
	 * @since 0.2.0
	 */
	public function capture_snapshot(): MigrationSnapshot;

	/**
	 * Restore every logical state represented by a snapshot.
	 *
	 * @param MigrationSnapshot $snapshot Rollback snapshot.
	 * @return void
	 *
	 * @since 0.2.0
	 */
	public function restore_snapshot( MigrationSnapshot $snapshot ): void;

	/**
	 * Determine whether current logical state matches a snapshot.
	 *
	 * @param MigrationSnapshot $snapshot Rollback snapshot.
	 * @return bool
	 *
	 * @since 0.2.0
	 */
	public function snapshot_matches( MigrationSnapshot $snapshot ): bool;
}
