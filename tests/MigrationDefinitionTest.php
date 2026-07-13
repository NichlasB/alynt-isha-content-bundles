<?php
/**
 * Migration definition tests.
 *
 * @package Alynt_ISHA_Content_Bundles
 */

namespace Alynt\ISHAContentBundles\Tests;

use Alynt\ISHAContentBundles\MigrationDefinition;
use Alynt\ISHAContentBundles\Value\MigrationSnapshot;
use Alynt\ISHAContentBundles\Value\MigrationPlan;
use Alynt\ISHAContentBundles\Value\RelationshipMigrationChange;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * Verifies the non-sensitive T3 migration baseline encoded in the plugin.
 */
final class MigrationDefinitionTest extends TestCase {

	/**
	 * Preview signatures are deterministic and state-sensitive.
	 *
	 * @return void
	 */
	public function test_migration_plan_signature_is_deterministic_and_state_sensitive() {
		$first  = new MigrationPlan( array( new RelationshipMigrationChange( 522, array( '519', '519' ), array( '519' ) ) ) );
		$second = new MigrationPlan( array( new RelationshipMigrationChange( 522, array( '519', '519' ), array( '519' ) ) ) );
		$third  = new MigrationPlan( array( new RelationshipMigrationChange( 524, array( '525', '525' ), array( '525' ) ) ) );

		$this->assertMatchesRegularExpression( '/^[a-f0-9]{64}$/', $first->get_signature() );
		$this->assertSame( $first->get_signature(), $second->get_signature() );
		$this->assertNotSame( $first->get_signature(), $third->get_signature() );
	}

	/**
	 * All 17 legacy product mappings match the approved baseline.
	 *
	 * @return void
	 */
	public function test_legacy_product_map_is_complete() {
		$expected = array(
			500 => 504, 507 => 509, 510 => 512, 514 => 516,
			522 => 519, 524 => 525, 531 => 530, 536 => 534,
			705 => 704, 718 => 719, 722 => 723, 727 => 728,
			733 => 734, 739 => 738, 1120 => 1121, 1139 => 1140,
			1157 => 1158,
		);

		$this->assertSame( $expected, MigrationDefinition::legacy_product_map() );
		$this->assertCount( 17, MigrationDefinition::legacy_product_ids() );
	}

	/**
	 * Only the four captured duplicate relationships require replacement.
	 *
	 * @return void
	 */
	public function test_baseline_and_targets_encode_exact_duplicate_rows() {
		$baseline = MigrationDefinition::baseline_relationships();
		$targets  = MigrationDefinition::target_relationships();
		$changed  = array();

		foreach ( $targets as $video_id => $values ) {
			if ( $baseline[ $video_id ] !== $values ) {
				$changed[] = $video_id;
			}
		}

		$this->assertSame( array( 522, 524, 722, 727 ), $changed );
		$this->assertSame( array( '519', '519' ), $baseline[522] );
		$this->assertSame( array( '525', '525' ), $baseline[524] );
		$this->assertSame( array( '', '723', '723' ), $baseline[722] );
		$this->assertSame( array( '728', '728', '728' ), $baseline[727] );
		$this->assertSame( array( '519' ), $targets[522] );
		$this->assertSame( array( '525' ), $targets[524] );
		$this->assertSame( array( '723' ), $targets[722] );
		$this->assertSame( array( '728' ), $targets[727] );
	}

	/**
	 * Snapshot scope protects the captured teacher and script baselines.
	 *
	 * @return void
	 */
	public function test_snapshot_scope_matches_t3_baseline() {
		$this->assertSame( array( 333, 385, 441, 443, 1106 ), MigrationDefinition::teacher_post_ids() );
		$this->assertSame( array( 44, 45, 51, 54 ), MigrationDefinition::script_term_ids() );
	}

	/**
	 * Rollback snapshots survive adapter serialization.
	 *
	 * @return void
	 */
	public function test_snapshot_array_round_trip_is_lossless() {
		$snapshot = new MigrationSnapshot(
			array( 522 => array( '519', '519' ) ),
			array( 519 => array( 'status' => 'publish', 'price' => '5.00' ) ),
			array( 333 => array( 'status' => 'publish' ) ),
			array( 44 => array( 'status' => '1', 'hash' => 'safe-fixture-hash' ) )
		);

		$restored = MigrationSnapshot::from_array( $snapshot->to_array() );

		$this->assertSame( $snapshot->to_array(), $restored->to_array() );
	}

	/**
	 * Incompatible rollback snapshots fail closed.
	 *
	 * @return void
	 */
	public function test_incompatible_snapshot_is_rejected() {
		$this->expectException( InvalidArgumentException::class );

		MigrationSnapshot::from_array(
			array(
				'version'       => 99,
				'relationships' => array(),
				'products'      => array(),
				'teachers'      => array(),
				'scripts'       => array(),
			)
		);
	}
}
