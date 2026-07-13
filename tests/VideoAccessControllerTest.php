<?php
/**
 * Video access controller tests.
 *
 * @package Alynt_ISHA_Content_Bundles
 */

namespace Alynt\ISHAContentBundles\Tests;

use Alynt\ISHAContentBundles\Services\EntitlementResolver;
use Alynt\ISHAContentBundles\Services\VideoAccessController;
use Alynt\ISHAContentBundles\Tests\Support\FakeContentMapProvider;
use Alynt\ISHAContentBundles\Tests\Support\FakePurchaseProvider;
use Alynt\ISHAContentBundles\Tests\Support\FakeUserAccessProvider;
use Alynt\ISHAContentBundles\Tests\Support\FakeVideoRouteProvider;
use Alynt\ISHAContentBundles\Value\Purchase;
use Alynt\ISHAContentBundles\Value\VideoAccessDecision;
use PHPUnit\Framework\TestCase;

/**
 * Verifies direct-video access and redirect decisions.
 */
final class VideoAccessControllerTest extends TestCase {

	/**
	 * Legacy purchasers may open the exact video they bought.
	 *
	 * @return void
	 */
	public function test_legacy_customer_can_open_purchased_video() {
		$controller = $this->create_controller(
			array(),
			array( 1 => array( new Purchase( 100, 'completed' ) ) ),
			array( 100 => 10 ),
			array(),
			array( 10 )
		);

		$decision = $controller->decide( 1, 10, 'https://example.test/video/10/' );

		$this->assertTrue( $decision->is_allowed() );
		$this->assertSame( 'entitled', $decision->get_code() );
	}

	/**
	 * Bundle purchasers may open every video in the explicit bundle manifest.
	 *
	 * @return void
	 */
	public function test_bundle_customer_can_open_included_video() {
		$controller = $this->create_controller(
			array(),
			array( 1 => array( new Purchase( 200, 'completed' ) ) ),
			array(),
			array( 200 => array( 10, 20 ) ),
			array( 10, 20 )
		);

		$decision = $controller->decide( 1, 20, 'https://example.test/video/20/' );

		$this->assertTrue( $decision->is_allowed() );
		$this->assertSame( 'entitled', $decision->get_code() );
	}

	/**
	 * Anonymous visitors to qualifying videos are redirected to the bundle offer.
	 *
	 * @return void
	 */
	public function test_anonymous_visitor_redirects_to_qualified_bundle() {
		$controller = $this->create_controller(
			array(),
			array(),
			array(),
			array(),
			array( 10 ),
			array( 10 => 'https://example.test/product/marie-bundle/' )
		);

		$decision = $controller->decide( 0, 10, 'https://example.test/video/10/' );

		$this->assertTrue( $decision->is_redirect() );
		$this->assertSame( 'bundle_available', $decision->get_code() );
		$this->assertSame( 'https://example.test/product/marie-bundle/', $decision->get_redirect_url() );
	}

	/**
	 * Logged-in non-buyers also redirect to the bundle offer.
	 *
	 * @return void
	 */
	public function test_nonbuyer_redirects_to_qualified_bundle() {
		$controller = $this->create_controller(
			array(),
			array( 3 => array() ),
			array(),
			array(),
			array( 10 ),
			array( 10 => 'https://example.test/product/pele-bundle/' )
		);

		$decision = $controller->decide( 3, 10, 'https://example.test/video/10/' );

		$this->assertTrue( $decision->is_redirect() );
		$this->assertSame( 'bundle_available', $decision->get_code() );
		$this->assertSame( 'https://example.test/product/pele-bundle/', $decision->get_redirect_url() );
	}

	/**
	 * Nonqualifying videos redirect to the unavailable destination.
	 *
	 * @return void
	 */
	public function test_nonqualifying_video_redirects_to_unavailable_destination() {
		$controller = $this->create_controller(
			array(),
			array(),
			array(),
			array(),
			array( 514 ),
			array(),
			array( 514 => 'https://example.test/content-unavailable/' )
		);

		$decision = $controller->decide( 0, 514, 'https://example.test/video/matt/' );

		$this->assertTrue( $decision->is_redirect() );
		$this->assertSame( 'content_unavailable', $decision->get_code() );
		$this->assertSame( 'https://example.test/content-unavailable/', $decision->get_redirect_url() );
	}

	/**
	 * Administrators can open protected videos through the entitlement resolver.
	 *
	 * @return void
	 */
	public function test_administrator_can_open_protected_video() {
		$controller = $this->create_controller(
			array( 9 ),
			array(),
			array(),
			array(),
			array( 10 )
		);

		$decision = $controller->decide( 9, 10, 'https://example.test/video/10/' );

		$this->assertTrue( $decision->is_allowed() );
		$this->assertSame( 'entitled', $decision->get_code() );
	}

	/**
	 * Non-video or unprotected requests pass through unchanged.
	 *
	 * @return void
	 */
	public function test_unprotected_video_is_allowed() {
		$controller = $this->create_controller();

		$decision = $controller->decide( 0, 999, 'https://example.test/page/' );

		$this->assertTrue( $decision->is_allowed() );
		$this->assertSame( 'unprotected_video', $decision->get_code() );
	}

	/**
	 * Invalid video IDs are denied.
	 *
	 * @return void
	 */
	public function test_invalid_video_id_is_denied() {
		$controller = $this->create_controller();

		$decision = $controller->decide( 0, 0, '' );

		$this->assertTrue( $decision->is_denied() );
		$this->assertSame( 'invalid_video', $decision->get_code() );
	}

	/**
	 * A bundle target matching the current URL falls back to unavailable routing.
	 *
	 * @return void
	 */
	public function test_matching_bundle_target_falls_back_to_unavailable_url() {
		$controller = $this->create_controller(
			array(),
			array(),
			array(),
			array(),
			array( 10 ),
			array( 10 => 'https://example.test/video/10/' ),
			array( 10 => 'https://example.test/content-unavailable/' )
		);

		$decision = $controller->decide( 0, 10, 'https://example.test/video/10/' );

		$this->assertTrue( $decision->is_redirect() );
		$this->assertSame( 'content_unavailable', $decision->get_code() );
		$this->assertSame( 'https://example.test/content-unavailable/', $decision->get_redirect_url() );
	}

	/**
	 * Matching or absent redirect targets deny without causing a loop.
	 *
	 * @return void
	 */
	public function test_missing_safe_redirect_target_is_denied() {
		$controller = $this->create_controller(
			array(),
			array(),
			array(),
			array(),
			array( 10 ),
			array( 10 => 'https://example.test/video/10/' ),
			array( 10 => 'https://example.test/video/10/' )
		);

		$decision = $controller->decide( 0, 10, 'https://example.test/video/10/' );

		$this->assertTrue( $decision->is_denied() );
		$this->assertSame( 'no_redirect_target', $decision->get_code() );
	}

	/**
	 * Create a controller with deterministic collaborators.
	 *
	 * @param int[]             $administrator_ids Administrator IDs.
	 * @param array<int,array>  $purchases_by_user Purchases keyed by user ID.
	 * @param array<int,int>    $legacy_map        Legacy product mappings.
	 * @param array<int,array>  $bundle_map        Bundle product mappings.
	 * @param int[]             $protected_videos  Protected video IDs.
	 * @param array<int,string> $bundle_urls       Bundle URLs keyed by video ID.
	 * @param array<int,string> $unavailable_urls  Unavailable URLs keyed by video ID.
	 * @return VideoAccessController
	 */
	private function create_controller(
		array $administrator_ids = array(),
		array $purchases_by_user = array(),
		array $legacy_map = array(),
		array $bundle_map = array(),
		array $protected_videos = array(),
		array $bundle_urls = array(),
		array $unavailable_urls = array()
	): VideoAccessController {
		$resolver = new EntitlementResolver(
			new FakeUserAccessProvider( $administrator_ids ),
			new FakePurchaseProvider( $purchases_by_user ),
			new FakeContentMapProvider( $legacy_map, $bundle_map, $protected_videos )
		);

		return new VideoAccessController(
			$resolver,
			new FakeVideoRouteProvider( $protected_videos, $bundle_urls, $unavailable_urls )
		);
	}
}
