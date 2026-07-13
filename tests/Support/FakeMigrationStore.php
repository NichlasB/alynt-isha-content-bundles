<?php
/**
 * Fake migration store.
 *
 * @package Alynt_ISHA_Content_Bundles
 */

namespace Alynt\ISHAContentBundles\Tests\Support;

use Alynt\ISHAContentBundles\Contracts\MigrationStore;
use Alynt\ISHAContentBundles\MigrationDefinition;
use Alynt\ISHAContentBundles\Value\MigrationSnapshot;
use RuntimeException;

/**
 * Supplies deterministic migration state and failures in unit tests.
 */
final class FakeMigrationStore implements MigrationStore {

	/** @var array<int,string[]> */
	private $relationships;

	/** @var array<int,array<string,mixed>> */
	private $products;

	/** @var array<int,array<string,mixed>> */
	private $teachers;

	/** @var array<int,array<string,mixed>> */
	private $scripts;

	/** @var int|null */
	private $fail_on_video_id;

	/** @var int */
	private $write_count = 0;

	/** @var MigrationSnapshot|null */
	private $latest_snapshot;

	/**
	 * Create the fake store.
	 *
	 * @param array<int,string[]>                $relationships Relationship values.
	 * @param array<int,array<string,mixed>> $products      Product states.
	 * @param array<int,array<string,mixed>> $teachers      Teacher states.
	 * @param array<int,array<string,mixed>> $scripts       Script states.
	 * @param int|null                       $fail_on_video_id Video ID that triggers an apply failure.
	 */
	public function __construct(
		array $relationships = array(),
		array $products = array(),
		array $teachers = array(),
		array $scripts = array(),
		?int $fail_on_video_id = null
	) {
		$this->relationships   = empty( $relationships ) ? MigrationDefinition::baseline_relationships() : $relationships;
		$this->products        = $products;
		$this->teachers        = $teachers;
		$this->scripts         = $scripts;
		$this->fail_on_video_id = $fail_on_video_id;
	}

	/** {@inheritdoc} */
	public function get_relationship_values( int $video_id ): array {
		return $this->relationships[ $video_id ] ?? array();
	}

	/** {@inheritdoc} */
	public function replace_relationship_values( int $video_id, array $values ): void {
		if ( $video_id === $this->fail_on_video_id ) {
			throw new RuntimeException( 'Simulated relationship write failure.' );
		}

		$this->relationships[ $video_id ] = array_values( $values );
		++$this->write_count;
	}

	/** {@inheritdoc} */
	public function capture_snapshot(): MigrationSnapshot {
		$this->latest_snapshot = new MigrationSnapshot(
			$this->relationships,
			$this->products,
			$this->teachers,
			$this->scripts
		);

		return $this->latest_snapshot;
	}

	/** {@inheritdoc} */
	public function restore_snapshot( MigrationSnapshot $snapshot ): void {
		$this->relationships = $snapshot->get_relationships();
		$this->products      = $snapshot->get_products();
		$this->teachers      = $snapshot->get_teachers();
		$this->scripts       = $snapshot->get_scripts();
	}

	/** {@inheritdoc} */
	public function snapshot_matches( MigrationSnapshot $snapshot ): bool {
		return $this->relationships === $snapshot->get_relationships()
			&& $this->products === $snapshot->get_products()
			&& $this->teachers === $snapshot->get_teachers()
			&& $this->scripts === $snapshot->get_scripts();
	}

	/** @return int */
	public function get_write_count(): int {
		return $this->write_count;
	}

	/** @return MigrationSnapshot|null */
	public function get_latest_snapshot(): ?MigrationSnapshot {
		return $this->latest_snapshot;
	}

	/** @param string[] $values Relationship values. */
	public function set_relationship_values( int $video_id, array $values ): void {
		$this->relationships[ $video_id ] = $values;
	}

	/** @param array<string,mixed> $state Product state. */
	public function set_product_state( int $product_id, array $state ): void {
		$this->products[ $product_id ] = $state;
	}

	/** @param array<string,mixed> $state Teacher state. */
	public function set_teacher_state( int $teacher_id, array $state ): void {
		$this->teachers[ $teacher_id ] = $state;
	}

	/** @param array<string,mixed> $state Script state. */
	public function set_script_state( int $script_id, array $state ): void {
		$this->scripts[ $script_id ] = $state;
	}
}
