<?php
/**
 * Bundle product admin hooks.
 *
 * @package Alynt_ISHA_Content_Bundles
 */

namespace Alynt\ISHAContentBundles\Integrations;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Alynt\ISHAContentBundles\BundleMetadata;
use Alynt\ISHAContentBundles\Services\BundleManifestAdminService;

/**
 * Renders and persists the WooCommerce bundle manifest fields.
 *
 * @since 0.2.0
 */
final class BundleProductAdminHooks {

	/**
	 * Manifest admin service.
	 *
	 * @var BundleManifestAdminService
	 */
	private $manifest_admin;

	/**
	 * Create the product-admin adapter.
	 *
	 * @param BundleManifestAdminService $manifest_admin Manifest service.
	 *
	 * @since 0.2.0
	 */
	public function __construct( BundleManifestAdminService $manifest_admin ) {
		$this->manifest_admin = $manifest_admin;
	}

	/** Register product-admin hooks. @return void
	 *
	 * @since 0.2.0
	 */
	public function register(): void {
		add_action( 'woocommerce_product_options_general_product_data', array( $this, 'render_bundle_fields' ) );
		add_action( 'woocommerce_process_product_meta', array( $this, 'save_bundle_fields' ), 20, 2 );
	}

	/** Render bundle fields in WooCommerce product admin. @return void
	 *
	 * @since 0.2.0
	 */
	public function render_bundle_fields(): void {
		global $post;
		$product_id = is_object( $post ) ? absint( $post->ID ) : 0;
		$video_ids  = get_post_meta( $product_id, BundleMetadata::META_VIDEO_IDS, true );
		$is_bundle  = metadata_exists( 'post', $product_id, BundleMetadata::META_TEACHER_ID );

		echo '<div class="options_group show_if_simple">';
		echo '<input type="hidden" name="' . esc_attr( BundleMetadata::FIELD_PRESENT ) . '" value="1">';
		wp_nonce_field( BundleMetadata::nonce_action( $product_id ), BundleMetadata::FIELD_NONCE );
		woocommerce_wp_checkbox(
			array(
				'id'          => BundleMetadata::FIELD_ENABLED,
				'label'       => __( 'ISHA teacher bundle', 'alynt-isha-content-bundles' ),
				'value'       => $is_bundle ? 'yes' : 'no',
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

		if ( $is_bundle ) {
			$order_count = $this->manifest_admin->get_completed_order_count( $product_id );
			if ( $order_count > 0 ) {
				woocommerce_wp_checkbox(
					array(
						'id'          => BundleMetadata::FIELD_REMOVAL_CONFIRMED,
						'label'       => __( 'Confirm sold-bundle removal', 'alynt-isha-content-bundles' ),
						'value'       => 'no',
						'description' => sprintf(
							/* translators: %d: completed order count. */
							__( 'This bundle has %d completed orders. Check only when intentionally removing videos or disabling bundle mode.', 'alynt-isha-content-bundles' ),
							$order_count
						),
					)
				);
				woocommerce_wp_textarea_input(
					array(
						'id'          => BundleMetadata::FIELD_REMOVAL_REASON,
						'label'       => __( 'Removal reason', 'alynt-isha-content-bundles' ),
						'value'       => '',
						'description' => __( 'Required only when removing videos from a bundle with completed orders. Do not include customer information.', 'alynt-isha-content-bundles' ),
					)
				);
			}
		}
		echo '</div>';
	}

	/**
	 * Validate and persist product bundle fields.
	 *
	 * @param int   $product_id Product ID.
	 * @param mixed $post       WooCommerce product object.
	 * @return void
	 *
	 * @since 0.2.0
	 */
	public function save_bundle_fields( int $product_id, $post = null ): void {
		unset( $post );
		$request = array_map( 'wp_unslash', $_POST ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Service verifies the plugin nonce before writing.
		$result  = $this->manifest_admin->save_from_request( $product_id, get_current_user_id(), $request );

		if ( ! $result->is_success() && class_exists( 'WC_Admin_Meta_Boxes' ) ) {
			foreach ( $result->get_messages() as $message ) {
				\WC_Admin_Meta_Boxes::add_error( esc_html( $message ) );
			}
		}
	}
}
