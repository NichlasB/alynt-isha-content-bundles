<?php
/**
 * Relationship migration service tests.
 *
 * @package Alynt_ISHA_Content_Bundles
 */

namespace Alynt\ISHAContentBundles\Tests;

use Alynt\ISHAContentBundles\MigrationDefinition;
use Alynt\ISHAContentBundles\Services\RelationshipMigrationService;
use Alynt\ISHAContentBundles\Tests\Support\FakeMigrationStore;
use PHPUnit\Framework\TestCase;

/**
 * Verifies preview, drift protection, idempotency, and rollback.
 */
final class RelationshipMigrationServiceTest extends TestCase {

	/**
	 * Dry run previews the four exact relationship replacements.
	 *
	 * @return void
	 */
	public function test_preview_reports_every_intended_write() {
		$service = new RelationshipMigrationService( new FakeMigrationStore() );
		$plan    = $service->preview();
		$changes = $plan->get_changes();
		$ids     = array_map(
			static function ( $change ): int {
				return $change->get_video_id();
			},
			$changes
		);

		$this->assertTrue( $plan->is_applicable() );
		$this->assertSame( array( 522, 524, 722, 727 ), $ids );
		$this->assertSame( array( '519', '519' ), $changes[0]->get_before_values() );
		$this->assertSame( array( '519' ), $changes[0]->get_after_values() );
		$this->assertSame( array( '', '723', '723' ), $changes[2]->get_before_values() );
		$this->assertSame( array( '723' ), $changes[2]->get_after_values() );
		$this->assertSame( array(), $plan->get_conflicts() );
	}

	/**
	 * Apply normalizes once and a second run is a verified no-op.
	 *
	 * @return void
	 */
	public function test_apply_is_verified_and_idempotent() {
		$store   = new FakeMigrationStore();
		$service = new RelationshipMigrationService( $store );
		$result  = $service->apply( $service->preview() );

		$this->assertTrue( $result->is_success() );
		$this->assertSame( 'applied', $result->get_code() );
		$this->assertSame( 4, $result->get_change_count() );
		$this->assertNotNull( $result->get_snapshot() );
		$this->assertSame( MigrationDefinition::baseline_relationships(), $result->get_snapshot()->get_relationships() );

		foreach ( array( 522, 524, 722, 727 ) as $video_id ) {
			$this->assertSame( MigrationDefinition::target_relationships()[ $video_id ], $store->get_relationship_values( $video_id ) );
		}

		$rerun_plan   = $service->preview();
		$rerun_result = $service->apply( $rerun_plan );

		$this->assertTrue( $rerun_plan->is_applicable() );
		$this->assertFalse( $rerun_plan->has_changes() );
		$this->assertTrue( $rerun_result->is_success() );
		$this->assertSame( 'no_changes', $rerun_result->get_code() );
		$this->assertSame( 4, $store->get_write_count() );
	}

	/**
	 * Unexpected live state is reported as drift and never written.
	 *
	 * @return void
	 */
	public function test_unexpected_relationship_state_blocks_apply() {
		$relationships      = MigrationDefinition::baseline_relationships();
		$relationships[522] = array( '999' );
		$store              = new FakeMigrationStore( $relationships );
		$service            = new RelationshipMigrationService( $store );
		$plan               = $service->preview();
		$result             = $service->apply( $plan );

		$this->assertFalse( $plan->is_applicable() );
		$this->assertSame( 522, $plan->get_conflicts()[0]['video_id'] );
		$this->assertSame( array( '999' ), $plan->get_conflicts()[0]['current'] );
		$this->assertFalse( $result->is_success() );
		$this->assertSame( 'drift_detected', $result->get_code() );
		$this->assertSame( 0, $store->get_write_count() );
		$this->assertNull( $store->get_latest_snapshot() );
	}

	/**
	 * State changes between preview and apply require a new preview.
	 *
	 * @return void
	 */
	public function test_stale_preview_is_rejected_before_snapshot_or_write() {
		$store         = new FakeMigrationStore();
		$service       = new RelationshipMigrationService( $store );
		$approved_plan = $service->preview();

		$store->set_relationship_values( 524, array( '525' ) );
		$result = $service->apply( $approved_plan );

		$this->assertFalse( $result->is_success() );
		$this->assertSame( 'preview_changed', $result->get_code() );
		$this->assertSame( 0, $store->get_write_count() );
		$this->assertNull( $store->get_latest_snapshot() );
	}

	/**
	 * Partial write failure automatically restores the captured snapshot.
	 *
	 * @return void
	 */
	public function test_partial_failure_automatically_rolls_back() {
		$store   = new FakeMigrationStore( array(), array(), array(), array(), 722 );
		$service = new RelationshipMigrationService( $store );
		$result  = $service->apply( $service->preview() );

		$this->assertFalse( $result->is_success() );
		$this->assertSame( 'apply_failed_rolled_back', $result->get_code() );
		$this->assertNotNull( $store->get_latest_snapshot() );
		$this->assertTrue( $store->snapshot_matches( $store->get_latest_snapshot() ) );
		$this->assertSame( MigrationDefinition::baseline_relationships(), $store->get_latest_snapshot()->get_relationships() );
	}

	/**
	 * Manual rollback restores relationships, products, teachers, and scripts.
	 *
	 * @return void
	 */
	public function test_manual_rollback_restores_complete_logical_state() {
		$store = new FakeMigrationStore(
			array(),
			array( 519 => array( 'status' => 'publish', 'price' => '5.00' ) ),
			array( 333 => array( 'status' => 'publish' ) ),
			array( 44 => array( 'status' => '1', 'hash' => 'baseline-hash' ) )
		);
		$service  = new RelationshipMigrationService( $store );
		$applied  = $service->apply( $service->preview() );
		$snapshot = $applied->get_snapshot();

		$store->set_relationship_values( 522, array( '999' ) );
		$store->set_product_state( 519, array( 'status' => 'draft' ) );
		$store->set_teacher_state( 333, array( 'status' => 'private' ) );
		$store->set_script_state( 44, array( 'status' => '0', 'hash' => 'changed-hash' ) );

		$result = $service->rollback( $snapshot );

		$this->assertTrue( $result->is_success() );
		$this->assertSame( 'rolled_back', $result->get_code() );
		$this->assertTrue( $store->snapshot_matches( $snapshot ) );
	}
}
