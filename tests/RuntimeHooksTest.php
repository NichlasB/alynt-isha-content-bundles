<?php
/**
 * Runtime hook adapter tests.
 *
 * @package Alynt_ISHA_Content_Bundles
 */

namespace Alynt\ISHAContentBundles\Tests;

use Alynt\ISHAContentBundles\Integrations\RuntimeHooks;
use Alynt\ISHAContentBundles\Services\CatalogEligibilityPolicy;
use Alynt\ISHAContentBundles\Tests\Support\FakeCatalogEligibilityProvider;
use Alynt\ISHAContentBundles\Value\BundleManifest;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Verifies policy decisions are translated to WooCommerce and query results.
 */
final class RuntimeHooksTest extends TestCase {

	/**
	 * Managed offers are blocked while unrelated WooCommerce results survive.
	 *
	 * @return void
	 */
	public function test_purchase_and_visibility_filters_fail_closed_only_for_managed_offers() {
		$hooks = $this->create_hooks();

		$this->assertFalse( $hooks->filter_purchasable( true, $this->product( 504 ) ) );
		$this->assertTrue( $hooks->filter_purchasable( true, $this->product( 100 ) ) );
		$this->assertFalse( $hooks->filter_purchasable( true, $this->product( 101 ) ) );
		$this->assertFalse( $hooks->filter_purchasable( false, $this->product( 999 ) ) );

		$this->assertFalse( $hooks->filter_product_visibility( true, 504 ) );
		$this->assertTrue( $hooks->filter_product_visibility( true, 100 ) );
		$this->assertFalse( $hooks->filter_product_visibility( true, 101 ) );
		$this->assertTrue( $hooks->filter_product_visibility( true, 999 ) );

		$this->assertFalse( $hooks->validate_add_to_cart( true, 504, 1 ) );
		$this->assertFalse( $hooks->validate_add_to_cart( true, 101, 1 ) );
		$this->assertTrue( $hooks->validate_add_to_cart( true, 999, 1 ) );
	}

	/**
	 * Non-singular discovery removes nonqualifying managed objects.
	 *
	 * @return void
	 */
	public function test_discovery_filter_keeps_qualifying_and_unrelated_objects() {
		$posts = array(
			$this->post( 504, 'product' ),
			$this->post( 100, 'product' ),
			$this->post( 101, 'product' ),
			$this->post( 999, 'product' ),
			$this->post( 705, 'video', 6 ),
			$this->post( 500, 'video', 4 ),
			$this->post( 441, 'teacher', 6 ),
			$this->post( 385, 'teacher', 4 ),
			$this->post( 2000, 'page' ),
		);
		$query = new class() {
			/** @return bool */
			public function is_singular(): bool {
				return false;
			}
		};

		$filtered = $this->create_hooks()->filter_discovery_posts( $posts, $query );

		$this->assertSame( array( 100, 999, 705, 441, 2000 ), array_column( $filtered, 'ID' ) );
	}

	/**
	 * Build a hook controller with only its catalog dependency initialized.
	 *
	 * @return RuntimeHooks
	 */
	private function create_hooks(): RuntimeHooks {
		$provider = new FakeCatalogEligibilityProvider(
			array( 504 ),
			array( 100, 101 ),
			array(
				100 => new BundleManifest( 6, array( 705 ), 3600.0 ),
				101 => new BundleManifest( 4, array( 500 ), 2300.0 ),
			),
			array( 6 => 100, 4 => 101 ),
			array( 705 => 6, 500 => 4 )
		);
		$class    = new ReflectionClass( RuntimeHooks::class );
		$hooks    = $class->newInstanceWithoutConstructor();
		$property = $class->getProperty( 'catalog_policy' );
		if ( PHP_VERSION_ID < 80100 ) {
			$property->setAccessible( true );
		}
		$property->setValue( $hooks, new CatalogEligibilityPolicy( $provider ) );

		return $hooks;
	}

	/**
	 * Create a minimal product object.
	 *
	 * @param int $product_id Product ID.
	 * @return object
	 */
	private function product( int $product_id ) {
		return new class( $product_id ) {
			/** @var int */
			private $id;

			/** @param int $id Product ID. */
			public function __construct( int $id ) {
				$this->id = $id;
			}

			/** @return int */
			public function get_id(): int {
				return $this->id;
			}
		};
	}

	/**
	 * Create a minimal query post object.
	 *
	 * @param int    $id        Post ID.
	 * @param string $post_type Post type.
	 * @param int    $author_id Author ID.
	 * @return object
	 */
	private function post( int $id, string $post_type, int $author_id = 0 ) {
		return (object) array(
			'ID'          => $id,
			'post_type'   => $post_type,
			'post_author' => $author_id,
		);
	}
}
