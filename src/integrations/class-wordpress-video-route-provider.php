<?php
/**
 * WordPress video-route adapter.
 *
 * @package Alynt_ISHA_Content_Bundles
 */

namespace Alynt\ISHAContentBundles\Integrations;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Alynt\ISHAContentBundles\Contracts\VideoRouteProvider;
use Alynt\ISHAContentBundles\MigrationDefinition;
use Alynt\ISHAContentBundles\Services\CatalogEligibilityPolicy;
use Alynt\ISHAContentBundles\SiteDefinition;

/**
 * Supplies protected routes and approved redirect destinations.
 *
 * @since 0.2.0
 */
final class WordPressVideoRouteProvider implements VideoRouteProvider {

	/**
	 * Catalog policy used to resolve qualifying bundles.
	 *
	 * @var CatalogEligibilityPolicy
	 */
	private $catalog_policy;

	/**
	 * Create the route provider.
	 *
	 * @param CatalogEligibilityPolicy $catalog_policy Catalog policy.
	 *
	 * @since 0.2.0
	 */
	public function __construct( CatalogEligibilityPolicy $catalog_policy ) {
		$this->catalog_policy = $catalog_policy;
	}

	/**
	 * Determine whether a video is protected.
	 *
	 * @param int $video_id Video post ID.
	 * @return bool
	 *
	 * @since 0.2.0
	 */
	public function is_protected_video( int $video_id ): bool {
		return SiteDefinition::VIDEO_POST_TYPE === get_post_type( $video_id )
			&& array_key_exists( $video_id, MigrationDefinition::legacy_product_map() );
	}

	/**
	 * Get a qualifying bundle URL for a video.
	 *
	 * @param int $video_id Video post ID.
	 * @return string|null
	 *
	 * @since 0.2.0
	 */
	public function get_bundle_redirect_url( int $video_id ): ?string {
		$product_id = $this->catalog_policy->get_available_bundle_product_id_for_video( $video_id );
		$url        = null === $product_id ? '' : (string) get_permalink( $product_id );

		return '' === $url ? null : $url;
	}

	/**
	 * Get the configured unavailable-content URL.
	 *
	 * @param int $video_id Video post ID.
	 * @return string|null
	 *
	 * @since 0.2.0
	 */
	public function get_unavailable_redirect_url( int $video_id ): ?string {
		$url = '';

		if ( function_exists( 'wc_get_page_id' ) ) {
			$shop_id = (int) wc_get_page_id( 'shop' );
			$url     = $shop_id > 0 ? (string) get_permalink( $shop_id ) : '';
		}

		if ( '' === $url ) {
			$url = (string) home_url( '/' );
		}

		$url = (string) apply_filters( 'alynt_isha_content_bundles_unavailable_url', $url, $video_id );

		return '' === trim( $url ) ? null : $url;
	}
}
