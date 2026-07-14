<?php
/**
 * Purchased-video library resolver.
 *
 * @package Alynt_ISHA_Content_Bundles
 */

namespace Alynt\ISHAContentBundles\Services;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Alynt\ISHAContentBundles\Contracts\VideoLibraryProvider;
use Alynt\ISHAContentBundles\Value\LibraryVideo;

/**
 * Resolves entitled video IDs into account-library presentation records.
 *
 * @since 0.2.0
 */
final class PurchasedVideoLibraryResolver {

	/**
	 * Central entitlement resolver.
	 *
	 * @var EntitlementResolver
	 */
	private $entitlement_resolver;

	/**
	 * Video presentation provider.
	 *
	 * @var VideoLibraryProvider
	 */
	private $video_library_provider;

	/**
	 * Create the library resolver.
	 *
	 * @param EntitlementResolver  $entitlement_resolver   Central entitlement resolver.
	 * @param VideoLibraryProvider $video_library_provider Video presentation provider.
	 *
	 * @since 0.2.0
	 */
	public function __construct(
		EntitlementResolver $entitlement_resolver,
		VideoLibraryProvider $video_library_provider
	) {
		$this->entitlement_resolver   = $entitlement_resolver;
		$this->video_library_provider = $video_library_provider;
	}

	/**
	 * Resolve the purchased-video library for a user.
	 *
	 * Missing records and provider records that do not match the requested video
	 * ID are ignored so an adapter cannot accidentally substitute other content.
	 *
	 * @param int $user_id WordPress user ID.
	 * @return LibraryVideo[]
	 *
	 * @since 0.2.0
	 */
	public function resolve_for_user( int $user_id ): array {
		$videos = array();

		foreach ( $this->entitlement_resolver->resolve_video_ids( $user_id ) as $video_id ) {
			$video = $this->video_library_provider->get_video( $video_id );

			if ( ! $video instanceof LibraryVideo || $video_id !== $video->get_id() ) {
				continue;
			}

			$videos[ $video_id ] = $video;
		}

		return array_values( $videos );
	}
}
