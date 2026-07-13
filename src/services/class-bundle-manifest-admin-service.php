<?php
/**
 * Bundle manifest admin service.
 *
 * @package Alynt_ISHA_Content_Bundles
 */

namespace Alynt\ISHAContentBundles\Services;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Alynt\ISHAContentBundles\BundleMetadata;
use Alynt\ISHAContentBundles\Contracts\AdminSecurityProvider;
use Alynt\ISHAContentBundles\Contracts\BundleManifestStore;
use Alynt\ISHAContentBundles\Value\BundleManifestSaveResult;

/**
 * Handles capability, nonce, and normalized bundle manifest persistence.
 */
final class BundleManifestAdminService {

	/**
	 * Security provider.
	 *
	 * @var AdminSecurityProvider
	 */
	private $security_provider;

	/**
	 * Manifest normalizer.
	 *
	 * @var BundleManifestNormalizer
	 */
	private $normalizer;

	/**
	 * Manifest store.
	 *
	 * @var BundleManifestStore
	 */
	private $manifest_store;

	/**
	 * Create the admin service.
	 *
	 * @param AdminSecurityProvider    $security_provider Security provider.
	 * @param BundleManifestNormalizer $normalizer        Manifest normalizer.
	 * @param BundleManifestStore      $manifest_store    Manifest store.
	 */
	public function __construct(
		AdminSecurityProvider $security_provider,
		BundleManifestNormalizer $normalizer,
		BundleManifestStore $manifest_store
	) {
		$this->security_provider = $security_provider;
		$this->normalizer        = $normalizer;
		$this->manifest_store    = $manifest_store;
	}

	/**
	 * Save a bundle manifest from product admin input.
	 *
	 * @param int   $product_id Product ID.
	 * @param int   $user_id    WordPress user ID.
	 * @param array $request    Admin request data.
	 * @return BundleManifestSaveResult
	 */
	public function save_from_request( int $product_id, int $user_id, array $request ): BundleManifestSaveResult {
		if ( ! isset( $request[ BundleMetadata::FIELD_PRESENT ] ) ) {
			return BundleManifestSaveResult::success( 'not_present' );
		}

		if ( ! $this->security_provider->user_can( $user_id, BundleMetadata::SAVE_CAPABILITY ) ) {
			return BundleManifestSaveResult::failure( 'forbidden', array( 'Current user cannot manage bundles.' ) );
		}

		$nonce = isset( $request[ BundleMetadata::FIELD_NONCE ] ) ? (string) $request[ BundleMetadata::FIELD_NONCE ] : '';

		if ( ! $this->security_provider->verify_nonce( $nonce, BundleMetadata::nonce_action( $product_id ) ) ) {
			return BundleManifestSaveResult::failure( 'invalid_nonce', array( 'Bundle manifest nonce check failed.' ) );
		}

		if ( empty( $request[ BundleMetadata::FIELD_ENABLED ] ) ) {
			$this->manifest_store->delete_manifest( $product_id );
			return BundleManifestSaveResult::success( 'deleted' );
		}

		$teacher_id = isset( $request[ BundleMetadata::FIELD_TEACHER_ID ] )
			? abs( (int) $request[ BundleMetadata::FIELD_TEACHER_ID ] )
			: 0;
		$video_ids  = $request[ BundleMetadata::FIELD_VIDEO_IDS ] ?? array();
		$result     = $this->normalizer->normalize( $video_ids, $teacher_id );

		if ( ! $result->is_success() || null === $result->get_manifest() ) {
			return $result;
		}

		$this->manifest_store->save_manifest( $product_id, $result->get_manifest() );

		return BundleManifestSaveResult::success( 'saved', $result->get_manifest() );
	}
}
