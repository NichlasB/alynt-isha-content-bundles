<?php
/**
 * Central entitlement resolver.
 *
 * @package Alynt_ISHA_Content_Bundles
 */

namespace Alynt\ISHAContentBundles\Services;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Alynt\ISHAContentBundles\Contracts\ContentMapProvider;
use Alynt\ISHAContentBundles\Contracts\PurchaseProvider;
use Alynt\ISHAContentBundles\Contracts\UserAccessProvider;
use Alynt\ISHAContentBundles\Value\Purchase;

/**
 * Resolves administrator, legacy-product, and bundle-product access.
 *
 * @since 0.2.0
 */
final class EntitlementResolver {

	/**
	 * User access provider.
	 *
	 * @var UserAccessProvider
	 */
	private $user_access_provider;

	/**
	 * Purchase provider.
	 *
	 * @var PurchaseProvider
	 */
	private $purchase_provider;

	/**
	 * Content map provider.
	 *
	 * @var ContentMapProvider
	 */
	private $content_map_provider;

	/**
	 * Create the resolver.
	 *
	 * @param UserAccessProvider $user_access_provider User access provider.
	 * @param PurchaseProvider   $purchase_provider    Purchase provider.
	 * @param ContentMapProvider $content_map_provider Content map provider.
	 *
	 * @since 0.2.0
	 */
	public function __construct(
		UserAccessProvider $user_access_provider,
		PurchaseProvider $purchase_provider,
		ContentMapProvider $content_map_provider
	) {
		$this->user_access_provider = $user_access_provider;
		$this->purchase_provider    = $purchase_provider;
		$this->content_map_provider = $content_map_provider;
	}

	/**
	 * Determine whether a user can access a video.
	 *
	 * @param int $user_id  WordPress user ID.
	 * @param int $video_id Video post ID.
	 * @return bool
	 *
	 * @since 0.2.0
	 */
	public function can_access_video( int $user_id, int $video_id ): bool {
		if ( $user_id <= 0 || $video_id <= 0 ) {
			return false;
		}

		if ( $this->user_access_provider->is_administrator( $user_id ) ) {
			return true;
		}

		return in_array( $video_id, $this->resolve_video_ids( $user_id ), true );
	}

	/**
	 * Resolve every video a user can access.
	 *
	 * @param int $user_id WordPress user ID.
	 * @return int[]
	 *
	 * @since 0.2.0
	 */
	public function resolve_video_ids( int $user_id ): array {
		if ( $user_id <= 0 ) {
			return array();
		}

		if ( $this->user_access_provider->is_administrator( $user_id ) ) {
			return $this->normalize_video_ids( $this->content_map_provider->get_all_video_ids() );
		}

		$video_ids = array();
		$purchases = $this->purchase_provider->get_purchases( $user_id );

		foreach ( $purchases as $purchase ) {
			if ( ! $purchase instanceof Purchase || ! $purchase->is_completed() ) {
				continue;
			}

			$product_id      = $purchase->get_product_id();
			$legacy_video_id = $this->content_map_provider->get_legacy_video_id( $product_id );

			if ( null !== $legacy_video_id ) {
				$video_ids[] = $legacy_video_id;
			}

			$video_ids = array_merge(
				$video_ids,
				$this->content_map_provider->get_bundle_video_ids( $product_id )
			);
		}

		return $this->normalize_video_ids( $video_ids );
	}

	/**
	 * Normalize, deduplicate, and sort video IDs.
	 *
	 * @param array<int|string|mixed> $video_ids Candidate video IDs.
	 * @return int[]
	 */
	private function normalize_video_ids( array $video_ids ): array {
		$normalized = array();

		foreach ( $video_ids as $video_id ) {
			if ( is_int( $video_id ) && $video_id > 0 ) {
				$normalized[ $video_id ] = $video_id;
				continue;
			}

			if ( is_string( $video_id ) && 1 === preg_match( '/^[1-9][0-9]*$/D', $video_id ) ) {
				$normalized[ (int) $video_id ] = (int) $video_id;
			}
		}

		$normalized = array_values( $normalized );
		sort( $normalized, SORT_NUMERIC );

		return $normalized;
	}
}
