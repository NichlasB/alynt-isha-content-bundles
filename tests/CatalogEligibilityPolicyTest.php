<?php
/**
 * Catalog eligibility policy tests.
 *
 * @package Alynt_ISHA_Content_Bundles
 */

namespace Alynt\ISHAContentBundles\Tests;

use Alynt\ISHAContentBundles\Services\CatalogEligibilityPolicy;
use Alynt\ISHAContentBundles\Services\EntitlementResolver;
use Alynt\ISHAContentBundles\Tests\Support\FakeCatalogEligibilityProvider;
use Alynt\ISHAContentBundles\Tests\Support\FakeContentMapProvider;
use Alynt\ISHAContentBundles\Tests\Support\FakePurchaseProvider;
use Alynt\ISHAContentBundles\Tests\Support\FakeUserAccessProvider;
use Alynt\ISHAContentBundles\Value\BundleManifest;
use Alynt\ISHAContentBundles\Value\Purchase;
use PHPUnit\Framework\TestCase;

/**
 * Verifies purchase blocking and discovery from explicit bundle runtime.
 */
final class CatalogEligibilityPolicyTest extends TestCase {

	/**
	 * Marie, Pele, and Gail qualify at their verified current runtimes.
	 *
	 * @return void
	 */
	public function test_current_qualifying_teachers_and_bundles_are_available() {
		$policy = $this->create_current_policy();

		foreach ( array( 1 => 1001, 2 => 1002, 3 => 1003 ) as $teacher_id => $product_id ) {
			$this->assertTrue( $policy->is_teacher_discoverable( $teacher_id ) );
			$this->assertSame( $product_id, $policy->get_available_bundle_product_id_for_teacher( $teacher_id ) );
			$this->assertTrue( $policy->is_product_discoverable( $product_id ) );
			$this->assertFalse( $policy->should_block_purchase( $product_id ) );
		}
	}

	/**
	 * Kristina and Matt stay absent because their runtime is below the cutoff.
	 *
	 * @return void
	 */
	public function test_current_nonqualifying_teachers_and_videos_are_hidden() {
		$policy = $this->create_current_policy();

		foreach ( array( 4 => 1004, 5 => 1005 ) as $teacher_id => $product_id ) {
			$this->assertFalse( $policy->is_teacher_discoverable( $teacher_id ) );
			$this->assertNull( $policy->get_available_bundle_product_id_for_teacher( $teacher_id ) );
			$this->assertFalse( $policy->is_product_discoverable( $product_id ) );
			$this->assertTrue( $policy->should_block_purchase( $product_id ) );
		}

		$this->assertFalse( $policy->is_video_discoverable( 401 ) );
		$this->assertFalse( $policy->is_video_discoverable( 501 ) );
		$this->assertNull( $policy->get_available_bundle_product_id_for_video( 401 ) );
	}

	/**
	 * Retiring a legacy offer does not revoke its completed entitlement.
	 *
	 * @return void
	 */
	public function test_legacy_product_is_blocked_while_previous_access_remains() {
		$policy = new CatalogEligibilityPolicy(
			new FakeCatalogEligibilityProvider( array( 500 ) )
		);
		$entitlements = new EntitlementResolver(
			new FakeUserAccessProvider(),
			new FakePurchaseProvider( array( 1 => array( new Purchase( 500, 'completed' ) ) ) ),
			new FakeContentMapProvider( array( 500 => 504 ) )
		);

		$this->assertFalse( $policy->is_product_discoverable( 500 ) );
		$this->assertTrue( $policy->should_block_purchase( 500 ) );
		$this->assertTrue( $entitlements->can_access_video( 1, 504 ) );
		$this->assertSame( array( 504 ), $entitlements->resolve_video_ids( 1 ) );
	}

	/**
	 * Products outside the managed legacy and bundle sets remain unchanged.
	 *
	 * @return void
	 */
	public function test_unrelated_products_are_not_blocked() {
		$policy = new CatalogEligibilityPolicy( new FakeCatalogEligibilityProvider() );

		$this->assertTrue( $policy->is_product_discoverable( 9999 ) );
		$this->assertFalse( $policy->should_block_purchase( 9999 ) );
	}

	/**
	 * The cutoff is inclusive and video count cannot replace runtime.
	 *
	 * @return void
	 */
	public function test_cutoff_is_inclusive_and_video_count_cannot_bypass_it() {
		$provider = new FakeCatalogEligibilityProvider(
			array(),
			array( 600, 700 ),
			array(
				600 => new BundleManifest( 6, array( 60 ), 3540.0 ),
				700 => new BundleManifest( 7, range( 701, 750 ), 3539.999 ),
			),
			array( 6 => 600, 7 => 700 ),
			array( 60 => 6, 701 => 7 )
		);
		$policy = new CatalogEligibilityPolicy( $provider );

		$this->assertTrue( $policy->is_teacher_discoverable( 6 ) );
		$this->assertTrue( $policy->is_video_discoverable( 60 ) );
		$this->assertFalse( $policy->should_block_purchase( 600 ) );
		$this->assertFalse( $policy->is_teacher_discoverable( 7 ) );
		$this->assertFalse( $policy->is_video_discoverable( 701 ) );
		$this->assertTrue( $policy->should_block_purchase( 700 ) );
	}

	/**
	 * Missing or inconsistent bundle data fails closed.
	 *
	 * @return void
	 */
	public function test_invalid_or_inconsistent_managed_data_fails_closed() {
		$provider = new FakeCatalogEligibilityProvider(
			array(),
			array( 800, 900 ),
			array( 900 => new BundleManifest( 9, array( 90 ), 4000.0 ) ),
			array( 8 => 800, 9 => 901 ),
			array( 80 => 8, 90 => 9 )
		);
		$policy = new CatalogEligibilityPolicy( $provider );

		foreach ( array( 800, 900 ) as $product_id ) {
			$this->assertFalse( $policy->is_product_discoverable( $product_id ) );
			$this->assertTrue( $policy->should_block_purchase( $product_id ) );
		}

		$this->assertFalse( $policy->is_teacher_discoverable( 8 ) );
		$this->assertFalse( $policy->is_teacher_discoverable( 9 ) );
		$this->assertFalse( $policy->is_video_discoverable( 80 ) );
		$this->assertFalse( $policy->is_video_discoverable( 90 ) );
		$this->assertFalse( $policy->is_product_discoverable( 0 ) );
		$this->assertTrue( $policy->should_block_purchase( 0 ) );
	}

	/**
	 * Create policy fixtures using the current verified teacher runtimes.
	 *
	 * Teacher IDs are stable local fixture identifiers, not production IDs.
	 *
	 * @return CatalogEligibilityPolicy
	 */
	private function create_current_policy(): CatalogEligibilityPolicy {
		$manifests = array(
			1001 => new BundleManifest( 1, array( 11, 12, 13, 14, 15, 16 ), 5803.190699 ),
			1002 => new BundleManifest( 2, array( 21, 22, 23, 24, 25 ), 5622.533334 ),
			1003 => new BundleManifest( 3, array( 31, 32 ), 3560.65857 ),
			1004 => new BundleManifest( 4, array( 41 ), 2418.233333 ),
			1005 => new BundleManifest( 5, array( 51, 52, 53 ), 2310.866666 ),
		);

		return new CatalogEligibilityPolicy(
			new FakeCatalogEligibilityProvider(
				array( 500, 507, 510, 514 ),
				array_keys( $manifests ),
				$manifests,
				array( 1 => 1001, 2 => 1002, 3 => 1003, 4 => 1004, 5 => 1005 ),
				array( 101 => 1, 201 => 2, 301 => 3, 401 => 4, 501 => 5 )
			)
		);
	}
}
