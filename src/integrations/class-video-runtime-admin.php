<?php
/**
 * Verified video runtime administration.
 *
 * @package Alynt_ISHA_Content_Bundles
 */

namespace Alynt\ISHAContentBundles\Integrations;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Alynt\ISHAContentBundles\SiteDefinition;

/**
 * Provides a secure editor for future video runtime measurements.
 *
 * @since 0.2.0
 */
final class VideoRuntimeAdmin {

	/**
	 * Register video administration hooks.
	 *
	 * @return void
	 *
	 * @since 0.2.0
	 */
	public function register(): void {
		add_action( 'add_meta_boxes_' . SiteDefinition::VIDEO_POST_TYPE, array( $this, 'add_meta_box' ) );
		add_action( 'save_post_' . SiteDefinition::VIDEO_POST_TYPE, array( $this, 'save_runtime' ), 20, 3 );
	}

	/**
	 * Add the verified runtime meta box.
	 *
	 * @return void
	 *
	 * @since 0.2.0
	 */
	public function add_meta_box(): void {
		add_meta_box(
			'alynt-isha-content-bundles-runtime',
			__( 'ISHA Verified Runtime', 'alynt-isha-content-bundles' ),
			array( $this, 'render_meta_box' ),
			SiteDefinition::VIDEO_POST_TYPE,
			'side',
			'default'
		);
	}

	/**
	 * Render the verified runtime editor.
	 *
	 * @param mixed $post Video post object.
	 * @return void
	 *
	 * @since 0.2.0
	 */
	public function render_meta_box( $post ): void {
		$post_id = is_object( $post ) && isset( $post->ID ) ? absint( $post->ID ) : 0;
		$runtime = (float) get_post_meta( $post_id, SiteDefinition::RUNTIME_META, true );

		if ( 0 >= $runtime ) {
			$runtimes = SiteDefinition::video_runtimes();
			$runtime  = isset( $runtimes[ $post_id ] ) ? $runtimes[ $post_id ] : 0;
		}

		wp_nonce_field( SiteDefinition::RUNTIME_ACTION, SiteDefinition::RUNTIME_NONCE );
		echo '<p><label for="' . esc_attr( SiteDefinition::RUNTIME_FIELD ) . '">';
		echo esc_html__( 'Verified seconds', 'alynt-isha-content-bundles' ) . '</label></p>';
		echo '<input class="widefat" type="number" min="0.001" step="0.001" id="';
		echo esc_attr( SiteDefinition::RUNTIME_FIELD ) . '" name="' . esc_attr( SiteDefinition::RUNTIME_FIELD ) . '" value="';
		echo esc_attr( (string) $runtime ) . '">';
		echo '<p class="description">';
		echo esc_html__( 'Measure the complete playable video before adding it to a bundle manifest.', 'alynt-isha-content-bundles' );
		echo '</p>';
	}

	/**
	 * Save a positive verified runtime.
	 *
	 * @param int   $post_id Video post ID.
	 * @param mixed $post    Video post object.
	 * @param bool  $update  Whether this is an update.
	 * @return void
	 *
	 * @since 0.2.0
	 */
	public function save_runtime( int $post_id, $post, bool $update ): void {
		unset( $post, $update );

		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return;
		}

		$nonce = isset( $_POST[ SiteDefinition::RUNTIME_NONCE ] )
			? sanitize_text_field( wp_unslash( $_POST[ SiteDefinition::RUNTIME_NONCE ] ) )
			: '';
		if ( ! current_user_can( 'edit_post', $post_id ) || false === wp_verify_nonce( $nonce, SiteDefinition::RUNTIME_ACTION ) ) {
			return;
		}

		$value   = isset( $_POST[ SiteDefinition::RUNTIME_FIELD ] )
			? sanitize_text_field( wp_unslash( $_POST[ SiteDefinition::RUNTIME_FIELD ] ) )
			: '';
		$runtime = is_numeric( $value ) ? (float) $value : 0.0;

		if ( 0 < $runtime ) {
			update_post_meta( $post_id, SiteDefinition::RUNTIME_META, $runtime );
		}
	}
}
