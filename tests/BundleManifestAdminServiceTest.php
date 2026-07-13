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
