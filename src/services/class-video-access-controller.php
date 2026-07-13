<?php
/**
 * Video access controller.
 *
 * @package Alynt_ISHA_Content_Bundles
 */

namespace Alynt\ISHAContentBundles\Services;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Alynt\ISHAContentBundles\Contracts\VideoRouteProvider;
use Alynt\ISHAContentBundles\Value\VideoAccessDecision;

/**
 * Resolves allow, redirect, or deny decisions for direct video requests.
 */
final class VideoAccessController {

	/**
	 * Entitlement resolver.
	 *
	 * @var EntitlementResolver
	 */
	private $entitlement_resolver;

	/**
	 * Route provider.
	 *
	 * @var VideoRouteProvider
	 */
	private $route_provider;

	/**
	 * Create the controller.
	 *
	 * @param EntitlementResolver $entitlement_resolver Entitlement resolver.
	 * @param VideoRouteProvider  $route_provider       Route provider.
	 */
	public function __construct( EntitlementResolver $entitlement_resolver, VideoRouteProvider $route_provider ) {
		$this->entitlement_resolver = $entitlement_resolver;
		$this->route_provider       = $route_provider;
	}

	/**
	 * Decide how to handle a direct video request.
	 *
	 * @param int    $user_id     WordPress user ID, or zero for anonymous.
	 * @param int    $video_id    Video post ID.
	 * @param string $current_url Current request URL, used only for redirect-loop prevention.
	 * @return VideoAccessDecision
	 */
	public function decide( int $user_id, int $video_id, string $current_url = '' ): VideoAccessDecision {
		if ( $video_id <= 0 ) {
			return VideoAccessDecision::deny( 'invalid_video' );
		}

		if ( ! $this->route_provider->is_protected_video( $video_id ) ) {
			return VideoAccessDecision::allow( 'unprotected_video' );
		}

		if ( $this->entitlement_resolver->can_access_video( $user_id, $video_id ) ) {
			return VideoAccessDecision::allow( 'entitled' );
		}

		$bundle_url = $this->route_provider->get_bundle_redirect_url( $video_id );

		if ( $this->is_safe_redirect_target( $bundle_url, $current_url ) ) {
			return VideoAccessDecision::redirect( 'bundle_available', $bundle_url );
		}

		$unavailable_url = $this->route_provider->get_unavailable_redirect_url( $video_id );

		if ( $this->is_safe_redirect_target( $unavailable_url, $current_url ) ) {
			return VideoAccessDecision::redirect( 'content_unavailable', $unavailable_url );
		}

		return VideoAccessDecision::deny( 'no_redirect_target' );
	}

	/**
	 * Determine whether a redirect target is usable and not the current request.
	 *
	 * @param string|null $target_url  Candidate target.
	 * @param string      $current_url Current request URL.
	 * @return bool
	 */
	private function is_safe_redirect_target( ?string $target_url, string $current_url ): bool {
		$target_url  = trim( (string) $target_url );
		$current_url = trim( $current_url );

		if ( '' === $target_url ) {
			return false;
		}

		return '' === $current_url || $target_url !== $current_url;
	}
}
