<?php
/**
 * Catalog and discovery hooks.
 *
 * @package Alynt_ISHA_Content_Bundles
 */

namespace Alynt\ISHAContentBundles\Integrations;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Alynt\ISHAContentBundles\Services\CatalogEligibilityPolicy;
use Alynt\ISHAContentBundles\Services\TeacherDirectoryContentFilter;
use Alynt\ISHAContentBundles\SiteDefinition;

/**
 * Applies catalog policy to WooCommerce and WordPress discovery surfaces.
 *
 * @since 0.2.0
 */
final class CatalogHooks {

	/**
	 * Catalog policy.
	 *
	 * @var CatalogEligibilityPolicy
	 */
	private $catalog_policy;

	/**
	 * Compiled directory filter.
	 *
	 * @var TeacherDirectoryContentFilter
	 */
	private $directory_filter;

	/**
	 * Create the catalog adapter.
	 *
	 * @param CatalogEligibilityPolicy      $catalog_policy  Catalog policy.
	 * @param TeacherDirectoryContentFilter $directory_filter Directory filter.
	 *
	 * @since 0.2.0
	 */
	public function __construct( CatalogEligibilityPolicy $catalog_policy, TeacherDirectoryContentFilter $directory_filter ) {
		$this->catalog_policy   = $catalog_policy;
		$this->directory_filter = $directory_filter;
	}

	/** Register catalog and discovery filters. @return void
	 *
	 * @since 0.2.0
	 */
	public function register(): void {
		add_filter( 'woocommerce_is_purchasable', array( $this, 'filter_purchasable' ), 10, 2 );
		add_filter( 'woocommerce_variation_is_purchasable', array( $this, 'filter_purchasable' ), 10, 2 );
		add_filter( 'woocommerce_product_is_visible', array( $this, 'filter_product_visibility' ), 10, 2 );
		add_filter( 'woocommerce_add_to_cart_validation', array( $this, 'validate_add_to_cart' ), 10, 3 );
		add_filter( 'the_posts', array( $this, 'filter_discovery_posts' ), 20, 2 );
		add_filter( 'the_content', array( $this, 'filter_teacher_directory_content' ), 20 );
	}

	/**
	 * Apply policy without overriding normal WooCommerce failures.
	 *
	 * @param bool  $purchasable Existing result.
	 * @param mixed $product     WooCommerce product.
	 * @return bool
	 *
	 * @since 0.2.0
	 */
	public function filter_purchasable( bool $purchasable, $product ): bool {
		$product_id = is_object( $product ) && method_exists( $product, 'get_id' ) ? absint( $product->get_id() ) : 0;

		return $this->catalog_policy->should_block_purchase( $product_id ) ? false : $purchasable;
	}

	/**
	 * Hide blocked managed products without changing unrelated visibility.
	 *
	 * @param bool $visible    Existing visibility.
	 * @param int  $product_id Product ID.
	 * @return bool
	 *
	 * @since 0.2.0
	 */
	public function filter_product_visibility( bool $visible, int $product_id ): bool {
		return $this->catalog_policy->is_product_discoverable( $product_id ) ? $visible : false;
	}

	/**
	 * Reject direct add-to-cart attempts for blocked managed offers.
	 *
	 * @param bool $passed       Existing result.
	 * @param int  $product_id   Product ID.
	 * @param int  $quantity     Quantity.
	 * @return bool
	 *
	 * @since 0.2.0
	 */
	public function validate_add_to_cart( bool $passed, int $product_id, int $quantity ): bool {
		unset( $quantity );
		if ( ! $this->catalog_policy->should_block_purchase( $product_id ) ) {
			return $passed;
		}

		if ( function_exists( 'wc_add_notice' ) ) {
			wc_add_notice( __( 'This content is not currently available for purchase.', 'alynt-isha-content-bundles' ), 'error' );
		}

		return false;
	}

	/**
	 * Remove nonqualifying managed objects from non-singular front-end queries.
	 *
	 * @param array $posts Query posts.
	 * @param mixed $query WordPress query.
	 * @return array
	 *
	 * @since 0.2.0
	 */
	public function filter_discovery_posts( array $posts, $query ): array {
		if ( is_admin() || ( is_object( $query ) && method_exists( $query, 'is_singular' ) && $query->is_singular() ) ) {
			return $posts;
		}

		return array_values(
			array_filter(
				$posts,
				function ( $post ): bool {
					if ( ! is_object( $post ) || empty( $post->ID ) || empty( $post->post_type ) ) {
						return true;
					}
					if ( SiteDefinition::PRODUCT_POST_TYPE === $post->post_type ) {
						return $this->catalog_policy->is_product_discoverable( (int) $post->ID );
					}
					if ( SiteDefinition::VIDEO_POST_TYPE === $post->post_type ) {
						return $this->catalog_policy->is_video_discoverable( (int) $post->ID );
					}
					if ( SiteDefinition::TEACHER_POST_TYPE === $post->post_type ) {
						return $this->catalog_policy->is_teacher_discoverable( (int) $post->post_author );
					}

					return true;
				}
			)
		);
	}

	/**
	 * Remove nonqualifying cards from the compiled Brizy teacher directory.
	 *
	 * @param string $content Rendered content.
	 * @return string
	 *
	 * @since 0.2.0
	 */
	public function filter_teacher_directory_content( string $content ): string {
		if ( is_admin() || ! is_page( SiteDefinition::TEACHER_DIRECTORY_PAGE_ID ) ) {
			return $content;
		}

		$blocked_urls = array();
		foreach ( SiteDefinition::teacher_posts_by_author() as $teacher_id => $teacher_post_id ) {
			if ( $this->catalog_policy->is_teacher_discoverable( $teacher_id ) ) {
				continue;
			}
			$url = get_permalink( $teacher_post_id );
			if ( is_string( $url ) && '' !== $url ) {
				$blocked_urls[] = $url;
			}
		}

		return $this->directory_filter->filter( $content, $blocked_urls );
	}
}
