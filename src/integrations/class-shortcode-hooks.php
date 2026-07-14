<?php
/**
 * Shortcode runtime hooks.
 *
 * @package Alynt_ISHA_Content_Bundles
 */

namespace Alynt\ISHAContentBundles\Integrations;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Alynt\ISHAContentBundles\Services\CatalogEligibilityPolicy;
use Alynt\ISHAContentBundles\Services\PurchasedVideoLibraryRenderer;
use Alynt\ISHAContentBundles\Services\PurchasedVideoLibraryResolver;
use Alynt\ISHAContentBundles\Services\TeacherVideoRenderer;

/**
 * Replaces legacy video shortcodes with entitlement-aware renderers.
 *
 * @since 0.2.0
 */
final class ShortcodeHooks {

	/**
	 * Catalog policy.
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
	 * Teacher-video library.
	 *
	 * @var WordPressTeacherVideoLibrary
	 */
	private $teacher_library;

	/**
	 * Teacher-video renderer.
	 *
	 * @var TeacherVideoRenderer
	 */
	private $teacher_renderer;

	/**
	 * Create the shortcode adapter.
	 *
	 * @param CatalogEligibilityPolicy      $catalog_policy   Catalog policy.
	 * @param PurchasedVideoLibraryResolver $library_resolver Library resolver.
	 * @param PurchasedVideoLibraryRenderer $library_renderer Library renderer.
	 * @param WordPressTeacherVideoLibrary  $teacher_library  Teacher library.
	 * @param TeacherVideoRenderer          $teacher_renderer Teacher renderer.
	 *
	 * @since 0.2.0
	 */
	public function __construct(
		CatalogEligibilityPolicy $catalog_policy,
		PurchasedVideoLibraryResolver $library_resolver,
		PurchasedVideoLibraryRenderer $library_renderer,
		WordPressTeacherVideoLibrary $teacher_library,
		TeacherVideoRenderer $teacher_renderer
	) {
		$this->catalog_policy   = $catalog_policy;
		$this->library_resolver = $library_resolver;
		$this->library_renderer = $library_renderer;
		$this->teacher_library  = $teacher_library;
		$this->teacher_renderer = $teacher_renderer;
	}

	/** Register shortcode replacement at the legacy-safe priority. @return void
	 *
	 * @since 0.2.0
	 */
	public function register(): void {
		add_action( 'init', array( $this, 'register_shortcodes' ), 99 );
	}

	/** Replace legacy shortcode callbacks. @return void
	 *
	 * @since 0.2.0
	 */
	public function register_shortcodes(): void {
		remove_action( 'template_redirect', 'restrict_video_access', 10 );
		remove_shortcode( 'purchased_videos' );
		add_shortcode( 'purchased_videos', array( $this, 'render_purchased_videos' ) );
		remove_shortcode( 'teacher_videos' );
		add_shortcode( 'teacher_videos', array( $this, 'render_teacher_videos' ) );
	}

	/** Render the current user's purchased-video library. @return string
	 *
	 * @since 0.2.0
	 */
	public function render_purchased_videos(): string {
		$logged_in = is_user_logged_in();
		$user_id   = $logged_in ? get_current_user_id() : 0;

		return $this->library_renderer->render( $this->library_resolver->resolve_for_user( $user_id ), $logged_in );
	}

	/**
	 * Render qualifying videos for a teacher.
	 *
	 * @param array $attributes Shortcode attributes.
	 * @return string
	 *
	 * @since 0.2.0
	 */
	public function render_teacher_videos( array $attributes = array() ): string {
		$teacher_id = isset( $attributes['teacher_id'] ) ? absint( $attributes['teacher_id'] ) : 0;
		if ( $teacher_id <= 0 ) {
			$teacher_id = absint( get_post_field( 'post_author', get_queried_object_id() ) );
		}

		if ( ! $this->catalog_policy->is_teacher_discoverable( $teacher_id ) ) {
			return $this->teacher_renderer->render( array() );
		}

		return $this->teacher_renderer->render( $this->teacher_library->get_videos( $teacher_id ) );
	}
}
