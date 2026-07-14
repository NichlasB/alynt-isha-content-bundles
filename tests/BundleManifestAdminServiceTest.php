<?php
/**
 * Bundle manifest admin service tests.
 *
 * @package Alynt_ISHA_Content_Bundles
 */

namespace Alynt\ISHAContentBundles\Tests;

use Alynt\ISHAContentBundles\BundleMetadata;
use Alynt\ISHAContentBundles\Services\BundleManifestAdminService;
use Alynt\ISHAContentBundles\Services\BundleManifestNormalizer;
use Alynt\ISHAContentBundles\Tests\Support\FakeAdminSecurityProvider;
use Alynt\ISHAContentBundles\Tests\Support\FakeBundleContentProvider;
use Alynt\ISHAContentBundles\Tests\Support\FakeBundleManifestStore;
use Alynt\ISHAContentBundles\Value\BundleManifest;
use Alynt\ISHAContentBundles\Value\BundleVideo;
use PHPUnit\Framework\TestCase;

/**
 * Verifies bundle manifest persistence boundaries without WordPress writes.
 */
final class BundleManifestAdminServiceTest extends TestCase {

	/**
	 * Valid published same-teacher videos are saved as explicit metadata.
	 *
	 * @return void
	 */
	public function test_valid_manifest_is_saved() {
		$store   = new FakeBundleManifestStore();
		$service = $this->create_service(
			$store,
			array(
				10 => new BundleVideo( 10, 7, 'publish', 1800.0 ),
				20 => new BundleVideo( 20, 7, 'publish', 1800.5 ),
			)
		);

		$result   = $service->save_from_request( 200, 1, $this->request( '10, 20', 7 ) );
		$manifest = $store->get_saved_manifest( 200 );

		$this->assertTrue( $result->is_success() );
		$this->assertSame( 'saved', $result->get_code() );
		$this->assertNotNull( $manifest );
		$this->assertSame( 7, $manifest->get_teacher_id() );
		$this->assertSame( array( 10, 20 ), $manifest->get_video_ids() );
		$this->assertSame( 3600.5, $manifest->get_runtime_seconds() );
		$this->assertTrue( $manifest->qualifies() );
	}

	/**
	 * Appending to a sold bundle does not require removal confirmation.
	 *
	 * @return void
	 */
	public function test_sold_bundle_can_receive_appended_videos() {
		$store = new FakeBundleManifestStore();
		$store->seed_manifest( 200, new BundleManifest( 7, array( 10 ), 3540.0 ) );
		$store->set_completed_order_count( 200, 3 );
		$service = $this->create_service(
			$store,
			array(
				10 => new BundleVideo( 10, 7, 'publish', 3540.0 ),
				20 => new BundleVideo( 20, 7, 'publish', 600.0 ),
			)
		);

		$result = $service->save_from_request( 200, 1, $this->request( '10, 20', 7 ) );

		$this->assertTrue( $result->is_success() );
		$this->assertSame( array( 10, 20 ), $store->get_saved_manifest( 200 )->get_video_ids() );
		$this->assertSame( array(), $store->get_audits( 200 ) );
	}

	/**
	 * Removing sold-bundle content requires confirmation and a reason.
	 *
	 * @return void
	 */
	public function test_sold_bundle_removal_requires_confirmation_and_reason() {
		$store = new FakeBundleManifestStore();
		$store->seed_manifest( 200, new BundleManifest( 7, array( 10, 20 ), 4140.0 ) );
		$store->set_completed_order_count( 200, 3 );
		$service = $this->create_service(
			$store,
			array( 10 => new BundleVideo( 10, 7, 'publish', 3540.0 ) )
		);

		$result = $service->save_from_request( 200, 1, $this->request( '10', 7 ) );

		$this->assertFalse( $result->is_success() );
		$this->assertSame( 'removal_confirmation_required', $result->get_code() );
		$this->assertSame( array( 10, 20 ), $store->get_saved_manifest( 200 )->get_video_ids() );
		$this->assertSame( array(), $store->get_audits( 200 ) );
	}

	/**
	 * Confirmed sold-bundle removals are saved and audited.
	 *
	 * @return void
	 */
	public function test_confirmed_sold_bundle_removal_is_audited() {
		$store = new FakeBundleManifestStore();
		$store->seed_manifest( 200, new BundleManifest( 7, array( 10, 20 ), 4140.0 ) );
		$store->set_completed_order_count( 200, 3 );
		$service = $this->create_service(
			$store,
			array( 10 => new BundleVideo( 10, 7, 'publish', 3540.0 ) )
		);
		$request = $this->request( '10', 7 );
		$request[ BundleMetadata::FIELD_REMOVAL_CONFIRMED ] = '1';
		$request[ BundleMetadata::FIELD_REMOVAL_REASON ]    = 'Replace an incorrect lesson.';

		$result = $service->save_from_request( 200, 1, $request );
		$audits = $store->get_audits( 200 );

		$this->assertTrue( $result->is_success() );
		$this->assertSame( array( 10 ), $store->get_saved_manifest( 200 )->get_video_ids() );
		$this->assertCount( 1, $audits );
		$this->assertSame( array( 20 ), $audits[0]['removed_video_ids'] );
		$this->assertSame( 3, $audits[0]['completed_order_count'] );
		$this->assertSame( 'Replace an incorrect lesson.', $audits[0]['reason'] );
	}

	/**
	 * Unsold bundles can be reorganized without sold-bundle confirmation.
	 *
	 * @return void
	 */
	public function test_unsold_bundle_can_remove_videos_without_confirmation() {
		$store = new FakeBundleManifestStore();
		$store->seed_manifest( 200, new BundleManifest( 7, array( 10, 20 ), 4140.0 ) );
		$service = $this->create_service(
			$store,
			array( 10 => new BundleVideo( 10, 7, 'publish', 3540.0 ) )
		);

		$result = $service->save_from_request( 200, 1, $this->request( '10', 7 ) );

		$this->assertTrue( $result->is_success() );
		$this->assertSame( array( 10 ), $store->get_saved_manifest( 200 )->get_video_ids() );
		$this->assertSame( array(), $store->get_audits( 200 ) );
	}

	/**
	 * Videos cannot be assigned to more than one managed bundle.
	 *
	 * @return void
	 */
	public function test_cross_bundle_video_assignment_is_rejected() {
		$store = new FakeBundleManifestStore();
		$store->set_conflicts( array( 10 => array( 300 ) ) );
		$service = $this->create_service(
			$store,
			array( 10 => new BundleVideo( 10, 7, 'publish', 3540.0 ) )
		);

		$result = $service->save_from_request( 200, 1, $this->request( '10', 7 ) );

		$this->assertFalse( $result->is_success() );
		$this->assertSame( 'duplicate_assignment', $result->get_code() );
		$this->assertSame( 0, $store->count_saved() );
	}

	/**
	 * Assignment-check failures prevent unverified manifest changes.
	 *
	 * @return void
	 */
	public function test_cross_bundle_assignment_check_failure_is_reported() {
		$store = new FakeBundleManifestStore();
		$store->fail_conflict_checks();
		$service = $this->create_service(
			$store,
			array( 10 => new BundleVideo( 10, 7, 'publish', 3540.0 ) )
		);

		$result = $service->save_from_request( 200, 1, $this->request( '10', 7 ) );

		$this->assertFalse( $result->is_success() );
		$this->assertSame( 'conflict_check_failed', $result->get_code() );
		$this->assertSame( 0, $store->count_saved() );
	}

	/**
	 * An audit failure restores the previous sold-bundle manifest.
	 *
	 * @return void
	 */
	public function test_audit_failure_restores_previous_manifest() {
		$store = new FakeBundleManifestStore( false, true );
		$store->seed_manifest( 200, new BundleManifest( 7, array( 10, 20 ), 4140.0 ) );
		$store->set_completed_order_count( 200, 3 );
		$service = $this->create_service(
			$store,
			array( 10 => new BundleVideo( 10, 7, 'publish', 3540.0 ) )
		);
		$request = $this->request( '10', 7 );
		$request[ BundleMetadata::FIELD_REMOVAL_CONFIRMED ] = '1';
		$request[ BundleMetadata::FIELD_REMOVAL_REASON ]    = 'Remove an incorrect lesson.';

		$result = $service->save_from_request( 200, 1, $request );

		$this->assertFalse( $result->is_success() );
		$this->assertSame( 'audit_failed', $result->get_code() );
		$this->assertSame( array( 10, 20 ), $store->get_saved_manifest( 200 )->get_video_ids() );
	}

	/**
	 * The approved grace-window cutoff is reportable through the manifest.
	 *
	 * @return void
	 */
	public function test_manifest_qualifies_at_approved_grace_cutoff() {
		$store   = new FakeBundleManifestStore();
		$service = $this->create_service(
			$store,
			array(
				1120 => new BundleVideo( 1120, 9, 'publish', 1800.0 ),
				1139 => new BundleVideo( 1139, 9, 'publish', 1760.65857 ),
			)
		);

		$result = $service->save_from_request( 300, 1, $this->request( array( '1120', '1139' ), 9 ) );

		$this->assertTrue( $result->is_success() );
		$this->assertNotNull( $result->get_manifest() );
		$this->assertSame( 3560.65857, $result->get_manifest()->get_runtime_seconds() );
		$this->assertTrue( $result->get_manifest()->qualifies() );
	}

	/**
	 * Duplicate IDs are rejected and not persisted.
	 *
	 * @return void
	 */
	public function test_duplicate_video_ids_are_rejected() {
		$store   = new FakeBundleManifestStore();
		$service = $this->create_service(
			$store,
			array( 10 => new BundleVideo( 10, 7, 'publish', 100.0 ) )
		);

		$result = $service->save_from_request( 200, 1, $this->request( '10, 10', 7 ) );

		$this->assertFalse( $result->is_success() );
		$this->assertSame( 'invalid_manifest', $result->get_code() );
		$this->assertSame( 0, $store->count_saved() );
	}

	/**
	 * Cross-teacher IDs are rejected and not persisted.
	 *
	 * @return void
	 */
	public function test_cross_teacher_video_ids_are_rejected() {
		$store   = new FakeBundleManifestStore();
		$service = $this->create_service(
			$store,
			array(
				10 => new BundleVideo( 10, 7, 'publish', 100.0 ),
				20 => new BundleVideo( 20, 8, 'publish', 100.0 ),
			)
		);

		$result = $service->save_from_request( 200, 1, $this->request( '10, 20', 7 ) );

		$this->assertFalse( $result->is_success() );
		$this->assertSame( 0, $store->count_saved() );
	}

	/**
	 * Draft or unknown videos cannot be saved unless intentionally retained.
	 *
	 * @return void
	 */
	public function test_unpublished_unretained_video_is_rejected() {
		$store   = new FakeBundleManifestStore();
		$service = $this->create_service(
			$store,
			array( 10 => new BundleVideo( 10, 7, 'draft', 100.0 ) )
		);

		$result = $service->save_from_request( 200, 1, $this->request( '10, 99', 7 ) );

		$this->assertFalse( $result->is_success() );
		$this->assertSame( 0, $store->count_saved() );
	}

	/**
	 * Malformed video ID tokens are rejected instead of silently discarded.
	 *
	 * @return void
	 */
	public function test_malformed_video_ids_are_rejected() {
		$store   = new FakeBundleManifestStore();
		$service = $this->create_service(
			$store,
			array( 10 => new BundleVideo( 10, 7, 'publish', 100.0 ) )
		);

		$result = $service->save_from_request( 200, 1, $this->request( '10, bad, 0', 7 ) );

		$this->assertFalse( $result->is_success() );
		$this->assertSame( 'invalid_manifest', $result->get_code() );
		$this->assertSame( 0, $store->count_saved() );
	}

	/**
	 * Intentionally retained non-published videos can remain in a manifest.
	 *
	 * @return void
	 */
	public function test_intentionally_retained_video_can_be_saved() {
		$store   = new FakeBundleManifestStore();
		$service = $this->create_service(
			$store,
			array( 10 => new BundleVideo( 10, 7, 'private', 100.0, true ) )
		);

		$result = $service->save_from_request( 200, 1, $this->request( '10', 7 ) );

		$this->assertTrue( $result->is_success() );
		$this->assertSame( array( 10 ), $store->get_saved_manifest( 200 )->get_video_ids() );
	}

	/**
	 * Failed capability checks block persistence.
	 *
	 * @return void
	 */
	public function test_missing_capability_blocks_save() {
		$store   = new FakeBundleManifestStore();
		$service = $this->create_service(
			$store,
			array( 10 => new BundleVideo( 10, 7, 'publish', 100.0 ) ),
			false
		);

		$result = $service->save_from_request( 200, 1, $this->request( '10', 7 ) );

		$this->assertFalse( $result->is_success() );
		$this->assertSame( 'forbidden', $result->get_code() );
		$this->assertSame( 0, $store->count_saved() );
	}

	/**
	 * Failed nonce checks block persistence.
	 *
	 * @return void
	 */
	public function test_invalid_nonce_blocks_save() {
		$store   = new FakeBundleManifestStore();
		$service = $this->create_service(
			$store,
			array( 10 => new BundleVideo( 10, 7, 'publish', 100.0 ) )
		);
		$request = $this->request( '10', 7 );
		$request[ BundleMetadata::FIELD_NONCE ] = 'bad';

		$result = $service->save_from_request( 200, 1, $request );

		$this->assertFalse( $result->is_success() );
		$this->assertSame( 'invalid_nonce', $result->get_code() );
		$this->assertSame( 0, $store->count_saved() );
	}

	/**
	 * Product saves without the bundle form marker do not create metadata.
	 *
	 * @return void
	 */
	public function test_unrelated_product_save_does_not_create_metadata() {
		$store   = new FakeBundleManifestStore();
		$service = $this->create_service(
			$store,
			array( 10 => new BundleVideo( 10, 7, 'publish', 100.0 ) )
		);

		$result = $service->save_from_request(
			200,
			1,
			array(
				BundleMetadata::FIELD_VIDEO_IDS => '10',
				BundleMetadata::FIELD_TEACHER_ID => 7,
			)
		);

		$this->assertTrue( $result->is_success() );
		$this->assertSame( 'not_present', $result->get_code() );
		$this->assertSame( 0, $store->count_saved() );
		$this->assertSame( array(), $store->get_deleted_product_ids() );
	}

	/**
	 * Explicitly disabling bundle mode deletes stored bundle metadata.
	 *
	 * @return void
	 */
	public function test_disabled_manifest_deletes_metadata() {
		$store   = new FakeBundleManifestStore();
		$service = $this->create_service(
			$store,
			array( 10 => new BundleVideo( 10, 7, 'publish', 100.0 ) )
		);
		$request = $this->request( '10', 7 );
		unset( $request[ BundleMetadata::FIELD_ENABLED ] );

		$result = $service->save_from_request( 200, 1, $request );

		$this->assertTrue( $result->is_success() );
		$this->assertSame( 'deleted', $result->get_code() );
		$this->assertSame( array( 200 ), $store->get_deleted_product_ids() );
	}

	/**
	 * Disabling a sold bundle requires explicit confirmation and a reason.
	 *
	 * @return void
	 */
	public function test_disabling_sold_bundle_requires_confirmation_and_reason() {
		$store = new FakeBundleManifestStore();
		$store->seed_manifest( 200, new BundleManifest( 7, array( 10 ), 3540.0 ) );
		$store->set_completed_order_count( 200, 2 );
		$service = $this->create_service(
			$store,
			array( 10 => new BundleVideo( 10, 7, 'publish', 3540.0 ) )
		);
		$request = $this->request( '10', 7 );
		unset( $request[ BundleMetadata::FIELD_ENABLED ] );

		$result = $service->save_from_request( 200, 1, $request );

		$this->assertFalse( $result->is_success() );
		$this->assertSame( 'removal_confirmation_required', $result->get_code() );
		$this->assertSame( array(), $store->get_deleted_product_ids() );
		$this->assertNotNull( $store->get_saved_manifest( 200 ) );
	}

	/**
	 * A confirmed sold-bundle disable is deleted and audited.
	 *
	 * @return void
	 */
	public function test_confirmed_sold_bundle_disable_is_audited() {
		$store = new FakeBundleManifestStore();
		$store->seed_manifest( 200, new BundleManifest( 7, array( 10 ), 3540.0 ) );
		$store->set_completed_order_count( 200, 2 );
		$service = $this->create_service(
			$store,
			array( 10 => new BundleVideo( 10, 7, 'publish', 3540.0 ) )
		);
		$request = $this->request( '10', 7 );
		unset( $request[ BundleMetadata::FIELD_ENABLED ] );
		$request[ BundleMetadata::FIELD_REMOVAL_CONFIRMED ] = '1';
		$request[ BundleMetadata::FIELD_REMOVAL_REASON ]    = 'Retire this bundle.';

		$result = $service->save_from_request( 200, 1, $request );

		$this->assertTrue( $result->is_success() );
		$this->assertSame( 'deleted', $result->get_code() );
		$this->assertSame( array( 200 ), $store->get_deleted_product_ids() );
		$this->assertNull( $store->get_saved_manifest( 200 ) );
		$this->assertSame( array( 10 ), $store->get_audits( 200 )[0]['removed_video_ids'] );
	}

	/**
	 * Order-impact check failures prevent removals.
	 *
	 * @return void
	 */
	public function test_order_impact_check_failure_blocks_removal() {
		$store = new FakeBundleManifestStore();
		$store->seed_manifest( 200, new BundleManifest( 7, array( 10, 20 ), 4140.0 ) );
		$store->fail_order_checks();
		$service = $this->create_service(
			$store,
			array( 10 => new BundleVideo( 10, 7, 'publish', 3540.0 ) )
		);

		$result = $service->save_from_request( 200, 1, $this->request( '10', 7 ) );

		$this->assertFalse( $result->is_success() );
		$this->assertSame( 'impact_check_failed', $result->get_code() );
		$this->assertSame( array( 10, 20 ), $store->get_saved_manifest( 200 )->get_video_ids() );
	}

	/**
	 * Persistence exceptions become a stable admin result.
	 *
	 * @return void
	 */
	public function test_manifest_store_failure_is_reported_without_an_exception() {
		$store   = new FakeBundleManifestStore( true );
		$service = $this->create_service(
			$store,
			array( 10 => new BundleVideo( 10, 7, 'publish', 100.0 ) )
		);

		$result = $service->save_from_request( 200, 1, $this->request( '10', 7 ) );

		$this->assertFalse( $result->is_success() );
		$this->assertSame( 'save_failed', $result->get_code() );
		$this->assertNotEmpty( $result->get_messages() );
	}

	/**
	 * Create the service under test.
	 *
	 * @param FakeBundleManifestStore $store     Manifest store.
	 * @param array<int,BundleVideo>  $videos    Videos keyed by ID.
	 * @param bool                    $can_save  Whether capability checks pass.
	 * @return BundleManifestAdminService
	 */
	private function create_service(
		FakeBundleManifestStore $store,
		array $videos,
		bool $can_save = true
	): BundleManifestAdminService {
		return new BundleManifestAdminService(
			new FakeAdminSecurityProvider(
				$can_save,
				array( BundleMetadata::nonce_action( 200 ) => 'valid', BundleMetadata::nonce_action( 300 ) => 'valid' )
			),
			new BundleManifestNormalizer( new FakeBundleContentProvider( $videos ) ),
			$store
		);
	}

	/**
	 * Build a bundle manifest request.
	 *
	 * @param array<int|string|mixed>|string $video_ids  Submitted video IDs.
	 * @param int                            $teacher_id Teacher ID.
	 * @return array<string,mixed>
	 */
	private function request( $video_ids, int $teacher_id ): array {
		return array(
			BundleMetadata::FIELD_PRESENT => '1',
			BundleMetadata::FIELD_ENABLED => '1',
			BundleMetadata::FIELD_NONCE => 'valid',
			BundleMetadata::FIELD_VIDEO_IDS => $video_ids,
			BundleMetadata::FIELD_TEACHER_ID => $teacher_id,
		);
	}
}
