<?php
/**
 * WordPress and WooCommerce runtime hooks.
 *
 * @package Alynt_ISHA_Content_Bundles
 */

namespace Alynt\ISHAContentBundles\Integrations;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Alynt\ISHAContentBundles\BundleMetadata;
use Alynt\ISHAContentBundles\Contracts\VideoLibraryProvider;
use Alynt\ISHAContentBundles\Services\BundleManifestAdminService;
use Alynt\ISHAContentBundles\Services\CatalogEligibilityPolicy;
use Alynt\ISHAContentBundles\Services\PurchasedVideoLibraryRenderer;
use Alynt\ISHAContentBundles\Services\PurchasedVideoLibraryResolver;
use Alynt\ISHAContentBundles\Services\TeacherDirectoryContentFilter;
use Alynt\ISHAContentBundles\Services\TeacherVideoRenderer;
use Alynt\ISHAContentBundles\Services\VideoAccessController;
use Alynt\ISHAContentBundles\SiteDefinition;

/**
 * Translates tested service decisions into platform behavior.
 */
final class RuntimeHooks {

	/**
	 * Direct-video access controller.
	 *
	 * @var VideoAccessController
	 */
	private $access_controller;

	/**
	 * Catalog and discovery policy.
	 *
	 * @var CatalogEligibilityPolicy
	 */
	private $catalog_policy;

	/**
	 * Purchased-library resolver.
	 *
	 * @var PurchasedVideoLibraryResolver
	 */
	private $library_resolver;

	/**
	 * Purchased-library renderer.
	 *
	 * @var PurchasedVideoLibraryRenderer
	 */
	private $library_renderer;

	/**
	 * WordPress video presentation provider.
	 *
	 * @var VideoLibraryProvider
	 */
	private $video_provider;

	/**
	 * Teacher-video renderer.
	 *
	 * @var TeacherVideoRenderer
	 */
	private $teacher_renderer;

	/**
	 * Bundle manifest admin service.
	 *
	 * @var BundleManifestAdminService
	 */
	private $manifest_admin;

	/**
	 * Create the runtime hook controller.
	 *
	 * @param VideoAccessController         $access_controller Access controller.
	 * @param CatalogEligibilityPolicy      $catalog_policy    Catalog policy.
	 * @param PurchasedVideoLibraryResolver $library_resolver  Library resolver.
	 * @param PurchasedVideoLibraryRenderer $library_renderer  Library renderer.
	 * @param VideoLibraryProvider          $video_provider    Video provider.
	 * @param TeacherVideoRenderer          $teacher_renderer  Teacher renderer.
	 * @param BundleManifestAdminService    $manifest_admin    Manifest admin service.
	 */
	public function __construct(
		VideoAccessController $access_controller,
		CatalogEligibilityPolicy $catalog_policy,
		PurchasedVideoLibraryResolver $library_resolver,
		PurchasedVideoLibraryRenderer $library_renderer,
		VideoLibraryProvider $video_provider,
		TeacherVideoRenderer $teacher_renderer,
		BundleManifestAdminService $manifest_admin
	) {
		$this->access_controller = $access_controller;
		$this->catalog_policy    = $catalog_policy;
		$this->library_resolver  = $library_resolver;
		$this->library_renderer  = $library_renderer;
		$this->video_provider    = $video_provider;
		$this->teacher_renderer  = $teacher_renderer;
		$this->manifest_admin    = $manifest_admin;
	}

	/**
	 * Register all runtime hooks at deterministic priorities.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'init', array( $this, 'register_shortcodes' ), 99 );
		add_action( 'template_redirect', array( $this, 'enforce_direct_access' ), 1 );
		add_action( 'woocommerce_product_options_general_product_data', array( $this, 'render_bundle_fields' ) );
		add_action( 'woocommerce_process_product_meta', array( $this, 'save_bundle_fields' ), 20, 2 );
		add_filter( 'woocommerce_is_purchasable', array( $this, 'filter_purchasable' ), 10, 2 );
		add_filter( 'woocommerce_variation_is_purchasable', array( $this, 'filter_purchasable' ), 10, 2 );
		add_filter( 'woocommerce_product_is_visible', array( $this, 'filter_product_visibility' ), 10, 2 );
		add_filter( 'woocommerce_add_to_cart_validation', array( $this, 'validate_add_to_cart' ), 10, 3 );
		add_filter( 'the_posts', array( $this, 'filter_discovery_posts' ), 20, 2 );
		add_filter( 'the_content', array( $this, 'filter_teacher_directory_content' ), 20 );
	}

	/**
	 * Supersede legacy shortcodes after Advanced Scripts has registered them.
	 *
	 * @return void
	 */
	public function register_shortcodes(): void {
		remove_action( 'template_redirect', 'restrict_video_access', 10 );
		remove_shortcode( 'purchased_videos' );
		add_shortcode( 'purchased_videos', array( $this, 'render_purchased_videos' ) );
		remove_shortcode( 'teacher_videos' );
		add_shortcode( 'teacher_videos', array( $this, 'render_teacher_videos' ) );
	}

	/**
	 * Render the current user's deduplicated purchased-video library.
	 *
	 * @return string
	 */
	public function render_purchased_videos(): string {
		$logged_in = is_user_logged_in();
		$user_id   = $logged_in ? get_current_user_id() : 0;

		return $this->library_renderer->render( $this->library_resolver->resolve_for_user( $user_id ), $logged_in );
	}

	/**
	 * Render discoverable videos for the current teacher post.
	 *
	 * @param array $attributes Shortcode attributes.
	 * @return string
	 */
	public function render_teacher_videos( array $attributes = array() ): string {
		$teacher_id = isset( $attributes['teacher_id'] ) ? absint( $attributes['teacher_id'] ) : 0;
		if ( $teacher_id <= 0 ) {
			$teacher_id = absint( get_post_field( 'post_author', get_queried_object_id() ) );
		}

		if ( ! $this->catalog_policy->is_teacher_discoverable( $teacher_id ) ) {
			return $this->teacher_renderer->render( array() );
		}

		$ids    = get_posts(
			array(
				'post_type'      => SiteDefinition::VIDEO_POST_TYPE,
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'author'         => $teacher_id,
				'orderby'        => 'date',
				'order'          => 'DESC',
				'no_found_rows'  => true,
			)
		);
		$videos = array();

		foreach ( $ids as $video_id ) {
			$video_id = absint( $video_id );
			if ( ! $this->catalog_policy->is_video_discoverable( $video_id ) ) {
				continue;
			}
			$video = $this->video_provider->get_video( $video_id );
			if ( null !== $video ) {
				$videos[] = $video;
			}
		}

		return $this->teacher_renderer->render( $videos );
	}

	/**
	 * Enforce protected video and blocked product routes before legacy scripts.
	 *
	 * @return void
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

	/**
	 * Apply the policy without overriding normal WooCommerce failures.
	 *
	 * @param bool  $purchasable Existing result.
	 * @param mixed $product     WooCommerce product.
	 * @return bool
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
	 */
	public function filter_product_visibility( bool $visible, int $product_id ): bool {
		return $this->catalog_policy->is_product_discoverable( $product_id ) ? $visible : false;
	}

	/**
	 * Reject direct add-to-cart attempts for blocked managed offers.
	 *
	 * @param bool $passed       Existing validation result.
	 * @param int  $product_id   Product ID.
	 * @param int  $quantity     Quantity.
	 * @return bool
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
	 * The Brizy Posts element stores a compiled HTML snapshot in post content,
	 * so filtering its source query alone does not change the live directory.
	 * Filtering the rendered snapshot keeps the builder document intact and
	 * automatically restores a card when its teacher later qualifies.
	 *
	 * @param string $content Rendered post content.
	 * @return string
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

		return ( new TeacherDirectoryContentFilter() )->filter( $content, $blocked_urls );
	}

	/**
	 * Render bundle fields in WooCommerce product admin.
	 *
	 * @return void
	 */
	public function render_bundle_fields(): void {
		global $post;
		$product_id = is_object( $post ) ? absint( $post->ID ) : 0;
		$video_ids  = get_post_meta( $product_id, BundleMetadata::META_VIDEO_IDS, true );

		echo '<div class="options_group show_if_simple">';
		echo '<input type="hidden" name="' . esc_attr( BundleMetadata::FIELD_PRESENT ) . '" value="1">';
		wp_nonce_field( BundleMetadata::nonce_action( $product_id ), BundleMetadata::FIELD_NONCE );
		woocommerce_wp_checkbox(
			array(
				'id'          => BundleMetadata::FIELD_ENABLED,
				'label'       => __( 'ISHA teacher bundle', 'alynt-isha-content-bundles' ),
				'value'       => metadata_exists( 'post', $product_id, BundleMetadata::META_TEACHER_ID ) ? 'yes' : 'no',
				'description' => __( 'Enforces the approved $50 bundle and runtime policy.', 'alynt-isha-content-bundles' ),
			)
		);
		woocommerce_wp_text_input(
			array(
				'id'                => BundleMetadata::FIELD_TEACHER_ID,
				'label'             => __( 'Teacher author ID', 'alynt-isha-content-bundles' ),
				'value'             => get_post_meta( $product_id, BundleMetadata::META_TEACHER_ID, true ),
				'type'              => 'number',
				'custom_attributes' => array(
					'min'  => '1',
					'step' => '1',
				),
			)
		);
		woocommerce_wp_text_input(
			array(
				'id'          => BundleMetadata::FIELD_VIDEO_IDS,
				'label'       => __( 'Video IDs', 'alynt-isha-content-bundles' ),
				'value'       => implode( ', ', array_map( 'absint', (array) $video_ids ) ),
				'description' => __( 'Comma-separated explicit manifest. Verified runtime is calculated on save.', 'alynt-isha-content-bundles' ),
			)
		);
		echo '</div>';
	}

	/**
	 * Validate and persist product bundle fields.
	 *
	 * @param int   $product_id Product ID.
	 * @param mixed $post       Product object supplied by WooCommerce.
	 * @return void
	 */
	public function save_bundle_fields( int $product_id, $post = null ): void {
		unset( $post );
		$request = array_map( 'wp_unslash', $_POST ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$result  = $this->manifest_admin->save_from_request( $product_id, get_current_user_id(), $request );

		if ( ! $result->is_success() && class_exists( 'WC_Admin_Meta_Boxes' ) ) {
			foreach ( $result->get_messages() as $message ) {
				\WC_Admin_Meta_Boxes::add_error( esc_html( $message ) );
			}
		}
	}

	/**
	 * Get the current absolute request URL.
	 *
	 * @return string
	 */
	private function get_current_url(): string {
		$request_uri = isset( $_SERVER['REQUEST_URI'] )
			? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) )
			: '/';

		return home_url( $request_uri );
	}

	/**
	 * Execute a safe redirect and end request processing.
	 *
	 * @param string $url Destination URL.
	 * @return void
	 */
	private function redirect_and_exit( string $url ): void {
		wp_safe_redirect( $url, 302, 'Alynt ISHA Content Bundles' );
		exit;
	}
}
