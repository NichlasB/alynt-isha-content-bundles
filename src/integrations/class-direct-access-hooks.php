<?php
/**
 * Direct-access runtime hooks.
 *
 * @package Alynt_ISHA_Content_Bundles
 */

namespace Alynt\ISHAContentBundles\Integrations;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Alynt\ISHAContentBundles\Services\CatalogEligibilityPolicy;
use Alynt\ISHAContentBundles\Services\VideoAccessController;
use Alynt\ISHAContentBundles\SiteDefinition;

/**
 * Enforces protected video and managed-product routes.
 *
 * @since 0.2.0
 */
final class DirectAccessHooks {

	/**
	 * Video access controller.
	 *
	 * @var VideoAccessController
	 */
	private $access_controller;

	/**
	 * Catalog policy.
	 *
	 * @var CatalogEligibilityPolicy
	 */
	private $catalog_policy;

	/**
	 * Create the direct-access adapter.
	 *
	 * @param VideoAccessController    $access_controller Access controller.
	 * @param CatalogEligibilityPolicy $catalog_policy    Catalog policy.
	 *
	 * @since 0.2.0
	 */
	public function __construct( VideoAccessController $access_controller, CatalogEligibilityPolicy $catalog_policy ) {
		$this->access_controller = $access_controller;
		$this->catalog_policy    = $catalog_policy;
	}

	/** Register the route guard. @return void
	 *
	 * @since 0.2.0
	 */
	public function register(): void {
		add_action( 'template_redirect', array( $this, 'enforce_direct_access' ), 1 );
	}

	/** Enforce protected routes before legacy scripts. @return void
	 *
	 * @since 0.2.0
	 */
	public function enforce_direct_access(): void {
		if ( is_singular( SiteDefinition::VIDEO_POST_TYPE ) ) {
			$decision = $this->access_controller->decide(
				get_current_user_id(),
				get_queried_object_id(),
				$this->get_current_url()
			);

			if ( $decision->is_redirect() ) {
				$this->redirect_and_exit( (string) $decision->get_redirect_url() );
			}
			if ( $decision->is_denied() ) {
				wp_die( esc_html__( 'This content is unavailable.', 'alynt-isha-content-bundles' ), '', array( 'response' => 403 ) );
			}
		}

		if ( is_singular( SiteDefinition::PRODUCT_POST_TYPE ) ) {
			$product_id = get_queried_object_id();
			if ( $this->catalog_policy->should_block_purchase( $product_id ) ) {
				$url = (string) apply_filters( 'alynt_isha_content_bundles_blocked_product_url', home_url( '/' ), $product_id );
				$this->redirect_and_exit( $url );
			}
		}
	}

	/** Get the current absolute request URL. @return string */
	private function get_current_url(): string {
		$request_uri = isset( $_SERVER['REQUEST_URI'] )
			? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) )
			: '/';

		return home_url( $request_uri );
	}

	/**
	 * Execute a safe redirect and stop request processing.
	 *
	 * @param string $url Destination URL.
	 * @return void
	 */
	private function redirect_and_exit( string $url ): void {
		if ( ! wp_safe_redirect( $url, 302, 'Alynt ISHA Content Bundles' ) ) {
			wp_die( esc_html__( 'The requested destination is unavailable.', 'alynt-isha-content-bundles' ), '', array( 'response' => 500 ) );
		}
		exit;
	}
}
