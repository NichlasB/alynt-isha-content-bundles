<?php
/**
 * Relationship migration service.
 *
 * @package Alynt_ISHA_Content_Bundles
 */

namespace Alynt\ISHAContentBundles\Services;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Alynt\ISHAContentBundles\Contracts\MigrationStore;
use Alynt\ISHAContentBundles\MigrationDefinition;
use Alynt\ISHAContentBundles\Value\MigrationExecutionResult;
use Alynt\ISHAContentBundles\Value\MigrationPlan;
use Alynt\ISHAContentBundles\Value\MigrationSnapshot;
use Alynt\ISHAContentBundles\Value\RelationshipMigrationChange;
use Throwable;

/**
 * Previews, applies, verifies, and rolls back exact relationship cleanup.
 */
final class RelationshipMigrationService {

	/**
	 * Migration state store.
	 *
	 * @var MigrationStore
	 */
	private $store;

	/**
	 * Create the migration service.
	 *
	 * @param MigrationStore $store Migration state store.
	 */
	public function __construct( MigrationStore $store ) {
		$this->store = $store;
	}

	/**
	 * Build an exact dry-run preview from current state.
	 *
	 * Baseline or already-normalized values are accepted. Any other state is a
	 * drift conflict and prevents apply mode.
	 *
	 * @return MigrationPlan
	 */
	public function preview(): MigrationPlan {
		$baseline  = MigrationDefinition::baseline_relationships();
		$targets   = MigrationDefinition::target_relationships();
		$changes   = array();
		$conflicts = array();

		foreach ( $targets as $video_id => $target_values ) {
			$current_values = array_values( $this->store->get_relationship_values( $video_id ) );

			if ( $current_values === $target_values ) {
				continue;
			}

			if ( $current_values !== $baseline[ $video_id ] ) {
				$conflicts[] = array(
					'video_id'          => $video_id,
					'expected_baseline' => $baseline[ $video_id ],
					'current'           => $current_values,
					'target'            => $target_values,
				);
				continue;
			}

			$changes[] = new RelationshipMigrationChange( $video_id, $current_values, $target_values );
		}

		return new MigrationPlan( $changes, $conflicts );
	}

	/**
	 * Apply an explicitly reviewed dry-run plan.
	 *
	 * @param MigrationPlan $approved_plan Previously reviewed preview.
	 * @return MigrationExecutionResult
	 */
	public function apply( MigrationPlan $approved_plan ): MigrationExecutionResult {
		$fresh_plan = $this->preview();

		if ( ! $fresh_plan->is_applicable() ) {
			return MigrationExecutionResult::failure( 'drift_detected' );
		}

		if ( ! $fresh_plan->has_changes() ) {
			return MigrationExecutionResult::success( 'no_changes' );
		}

		if ( ! $approved_plan->is_applicable() || ! $approved_plan->matches( $fresh_plan ) ) {
			return MigrationExecutionResult::failure( 'preview_changed' );
		}

		try {
			$snapshot = $this->store->capture_snapshot();
		} catch ( Throwable $exception ) {
			return MigrationExecutionResult::failure( 'snapshot_failed' );
		}

		try {
			foreach ( $fresh_plan->get_changes() as $change ) {
				$this->store->replace_relationship_values( $change->get_video_id(), $change->get_after_values() );
			}

			$verification = $this->preview();

			if ( ! $verification->is_applicable() || $verification->has_changes() ) {
				return $this->restore_after_failure( $snapshot );
			}
		} catch ( Throwable $exception ) {
			return $this->restore_after_failure( $snapshot );
		}

		return MigrationExecutionResult::success(
			'applied',
			count( $fresh_plan->get_changes() ),
			$snapshot
		);
	}

	/**
	 * Restore an approved pre-write snapshot.
	 *
	 * @param MigrationSnapshot $snapshot Rollback snapshot.
	 * @return MigrationExecutionResult
	 */
	public function rollback( MigrationSnapshot $snapshot ): MigrationExecutionResult {
		try {
			$this->store->restore_snapshot( $snapshot );
		} catch ( Throwable $exception ) {
			return MigrationExecutionResult::failure( 'rollback_failed' );
		}

		if ( ! $this->store->snapshot_matches( $snapshot ) ) {
			return MigrationExecutionResult::failure( 'rollback_verification_failed' );
		}

		return MigrationExecutionResult::success( 'rolled_back' );
	}

	/**
	 * Restore the pre-write state after an apply failure.
	 *
	 * @param MigrationSnapshot $snapshot Pre-write snapshot.
	 * @return MigrationExecutionResult
	 */
	private function restore_after_failure( MigrationSnapshot $snapshot ): MigrationExecutionResult {
		$result = $this->rollback( $snapshot );

		if ( ! $result->is_success() ) {
			return MigrationExecutionResult::failure( 'automatic_rollback_failed' );
		}

		return MigrationExecutionResult::failure( 'apply_failed_rolled_back' );
	}
}
