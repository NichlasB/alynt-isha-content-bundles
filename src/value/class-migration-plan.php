<?php
/**
 * Migration plan value object.
 *
 * @package Alynt_ISHA_Content_Bundles
 */

namespace Alynt\ISHAContentBundles\Value;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Represents an exact dry-run preview and any detected drift.
 *
 * @since 0.2.0
 */
final class MigrationPlan {

	/**
	 * Planned relationship replacements.
	 *
	 * @var RelationshipMigrationChange[]
	 */
	private $changes;

	/**
	 * Detected drift records.
	 *
	 * @var array<int,array<string,mixed>>
	 */
	private $conflicts;

	/**
	 * Create a migration plan.
	 *
	 * @param RelationshipMigrationChange[]  $changes   Planned replacements.
	 * @param array<int,array<string,mixed>> $conflicts Detected drift records.
	 *
	 * @since 0.2.0
	 */
	public function __construct( array $changes, array $conflicts = array() ) {
		$this->changes   = array_values( $changes );
		$this->conflicts = array_values( $conflicts );
	}

	/**
	 * Get planned relationship replacements.
	 *
	 * @return RelationshipMigrationChange[]
	 *
	 * @since 0.2.0
	 */
	public function get_changes(): array {
		return $this->changes;
	}

	/**
	 * Get detected drift records.
	 *
	 * @return array<int,array<string,mixed>>
	 *
	 * @since 0.2.0
	 */
	public function get_conflicts(): array {
		return $this->conflicts;
	}

	/**
	 * Determine whether the preview contains writes.
	 *
	 * @return bool
	 *
	 * @since 0.2.0
	 */
	public function has_changes(): bool {
		return ! empty( $this->changes );
	}

	/**
	 * Determine whether no drift conflicts block apply mode.
	 *
	 * @return bool
	 *
	 * @since 0.2.0
	 */
	public function is_applicable(): bool {
		return empty( $this->conflicts );
	}

	/**
	 * Determine whether two previews describe the same exact state.
	 *
	 * @param MigrationPlan $other Other preview.
	 * @return bool
	 *
	 * @since 0.2.0
	 */
	public function matches( MigrationPlan $other ): bool {
		return $this->to_array() === $other->to_array();
	}

	/**
	 * Get a deterministic signature for approval-gated apply mode.
	 *
	 * @return string
	 *
	 * @since 0.2.0
	 */
	public function get_signature(): string {
		$json = wp_json_encode( $this->to_array(), JSON_UNESCAPED_SLASHES );

		return hash( 'sha256', false === $json ? '' : $json );
	}

	/**
	 * Export the plan for a human-readable dry run.
	 *
	 * @return array{changes:array,conflicts:array}
	 *
	 * @since 0.2.0
	 */
	public function to_array(): array {
		$changes = array_map(
			static function ( RelationshipMigrationChange $change ): array {
				return $change->to_array();
			},
			$this->changes
		);

		return array(
			'changes'   => $changes,
			'conflicts' => $this->conflicts,
		);
	}
}
