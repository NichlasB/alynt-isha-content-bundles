<?php
/**
 * Entitlement resolver tests.
 *
 * @package Alynt_ISHA_Content_Bundles
 */

namespace Alynt\ISHAContentBundles\Tests;

use Alynt\ISHAContentBundles\Services\EntitlementResolver;
use Alynt\ISHAContentBundles\Tests\Support\FakeContentMapProvider;
use Alynt\ISHAContentBundles\Tests\Support\FakePurchaseProvider;
use Alynt\ISHAContentBundles\Tests\Support\FakeUserAccessProvider;
use Alynt\ISHAContentBundles\Value\Purchase;
use PHPUnit\Framework\TestCase;

/**
 * Verifies central entitlement policy without WordPress or WooCommerce.
 */
final class EntitlementResolverTest extends TestCase {

	/**
	 * Administrators can access any positive video ID and list known videos.
	 *
	 * @return void
	 */
	public function test_administrator_access() {
		$resolver = $this->create_resolver( array( 99 ), array(), array(), array(), array( 20, '10', 20, 0, 'bad' ) );

		$this->assertTrue( $resolver->can_access_video( 99, 999 ) );
		$this->assertSame( array( 10, 20 ), $resolver->resolve_video_ids( 99 ) );
	}

	/**
	 * Anonymous users receive no entitlements.
	 *
	 * @return void
	 */
	public function test_anonymous_access_is_denied() {
		$resolver = $this->create_resolver(
			array(),
			array( 0 => array( new Purchase( 100, 'completed' ) ) ),
			array( 100 => 10 )
		);

		$this->assertFalse( $resolver->can_access_video( 0, 10 ) );
		$this->assertSame( array(), $resolver->resolve_video_ids( 0 ) );
	}

	/**
	 * A completed legacy product grants only its mapped video.
	 *
	 * @return void
	 */
	public function test_legacy_purchase_grants_only_mapped_video() {
		$resolver = $this->create_resolver(
			array(),
			array( 1 => array( new Purchase( 100, 'completed' ) ) ),
			array( 100 => 10 ),
			array( 200 => array( 20, 30 ) )
		);

		$this->assertSame( array( 10 ), $resolver->resolve_video_ids( 1 ) );
		$this->assertTrue( $resolver->can_access_video( 1, 10 ) );
		$this->assertFalse( $resolver->can_access_video( 1, 20 ) );
	}

	/**
	 * A completed bundle product grants every manifest video.
	 *
	 * @return void
	 */
	public function test_bundle_purchase_grants_explicit_manifest() {
		$resolver = $this->create_resolver(
			array(),
			array( 1 => array( new Purchase( 200, 'completed' ) ) ),
			array(),
			array( 200 => array( 30, 10, 20 ) )
		);

		$this->assertSame( array( 10, 20, 30 ), $resolver->resolve_video_ids( 1 ) );
	}

	/**
	 * Overlapping legacy and bundle access returns each video once.
	 *
	 * @return void
	 */
	public function test_overlapping_entitlements_are_deduplicated() {
		$resolver = $this->create_resolver(
			array(),
			array( 1 => array( new Purchase( 100, 'completed' ), new Purchase( 200, 'completed' ) ) ),
			array( 100 => 10 ),
			array( 200 => array( 10, 20, 20 ) )
		);

		$this->assertSame( array( 10, 20 ), $resolver->resolve_video_ids( 1 ) );
	}

	/**
	 * Malformed manifest values are ignored without losing valid IDs.
	 *
	 * @return void
	 */
	public function test_malformed_manifest_values_are_ignored() {
		$resolver = $this->create_resolver(
			array(),
			array( 1 => array( new Purchase( 200, 'completed' ) ) ),
			array(),
			array( 200 => array( 10, '20', 0, -1, 'bad', 12.5, '0021', null, 10 ) )
		);

		$this->assertSame( array( 10, 20 ), $resolver->resolve_video_ids( 1 ) );
	}

	/**
	 * Incomplete orders do not grant access.
	 *
	 * @return void
	 */
	public function test_incomplete_orders_do_not_grant_access() {
		$resolver = $this->create_resolver(
			array(),
			array(
				1 => array(
					new Purchase( 100, 'processing' ),
					new Purchase( 101, 'pending' ),
					new Purchase( 102, 'on-hold' ),
					new Purchase( 103, 'cancelled' ),
					new Purchase( 200, 'completed' ),
				),
			),
			array( 100 => 10, 101 => 11, 102 => 12, 103 => 13 ),
			array( 200 => array( 20 ) )
		);

		$this->assertSame( array( 20 ), $resolver->resolve_video_ids( 1 ) );
		$this->assertFalse( $resolver->can_access_video( 1, 10 ) );
	}

	/**
	 * WooCommerce-prefixed completed status is accepted.
	 *
	 * @return void
	 */
	public function test_wc_completed_status_grants_access() {
		$resolver = $this->create_resolver(
			array(),
			array( 1 => array( new Purchase( 100, 'wc-completed' ) ) ),
			array( 100 => 10 )
		);

		$this->assertSame( array( 10 ), $resolver->resolve_video_ids( 1 ) );
	}

	/**
	 * Every valid manifest video remains accessible through the same resolver.
	 *
	 * @return void
	 */
	public function test_no_qualifying_bundle_video_is_denied() {
		$resolver = $this->create_resolver(
			array(),
			array( 1 => array( new Purchase( 200, 'completed' ) ) ),
			array(),
			array( 200 => array( 10, 20, 30 ) )
		);

		foreach ( array( 10, 20, 30 ) as $video_id ) {
			$this->assertTrue( $resolver->can_access_video( 1, $video_id ) );
		}
	}

	/**
	 * Create a resolver with deterministic providers.
	 *
	 * @param int[]            $administrator_ids Administrator IDs.
	 * @param array<int,array> $purchases_by_user Purchases keyed by user ID.
	 * @param array<int,int>   $legacy_map        Legacy product mappings.
	 * @param array<int,array> $bundle_map        Bundle product mappings.
	 * @param array            $all_video_ids     All known video IDs.
	 * @return EntitlementResolver
	 */
	private function create_resolver(
		array $administrator_ids = array(),
		array $purchases_by_user = array(),
		array $legacy_map = array(),
		array $bundle_map = array(),
		array $all_video_ids = array()
	): EntitlementResolver {
		return new EntitlementResolver(
			new FakeUserAccessProvider( $administrator_ids ),
			new FakePurchaseProvider( $purchases_by_user ),
			new FakeContentMapProvider( $legacy_map, $bundle_map, $all_video_ids )
		);
	}
}
