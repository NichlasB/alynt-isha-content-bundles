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
use Alynt\ISHAContentBundles\Value\BundleManifest;
use Alynt\ISHAContentBundles\Value\BundleManifestSaveResult;
use Throwable;

/**
 * Handles capability, nonce, and normalized bundle manifest persistence.
 *
 * @since 0.2.0
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
	 *
	 * @since 0.2.0
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
	 *
	 * @since 0.2.0
	 */
	public function save_from_request( int $product_id, int $user_id, array $request ): BundleManifestSaveResult {
		if ( ! isset( $request[ BundleMetadata::FIELD_PRESENT ] ) ) {
			return BundleManifestSaveResult::success( 'not_present' );
		}

		if ( ! $this->security_provider->user_can( $user_id, BundleMetadata::SAVE_CAPABILITY ) ) {
			return BundleManifestSaveResult::failure( 'forbidden', array( __( 'Current user cannot manage bundles.', 'alynt-isha-content-bundles' ) ) );
		}

		$nonce = isset( $request[ BundleMetadata::FIELD_NONCE ] ) ? (string) $request[ BundleMetadata::FIELD_NONCE ] : '';

		if ( ! $this->security_provider->verify_nonce( $nonce, BundleMetadata::nonce_action( $product_id ) ) ) {
			return BundleManifestSaveResult::failure( 'invalid_nonce', array( __( 'Bundle manifest nonce check failed.', 'alynt-isha-content-bundles' ) ) );
		}

		$current_manifest = $this->manifest_store->get_manifest( $product_id );

		if ( empty( $request[ BundleMetadata::FIELD_ENABLED ] ) ) {
			return $this->delete_manifest( $product_id, $user_id, $request, $current_manifest );
		}

		$teacher_id = isset( $request[ BundleMetadata::FIELD_TEACHER_ID ] )
			? abs( (int) $request[ BundleMetadata::FIELD_TEACHER_ID ] )
			: 0;
		$video_ids  = $request[ BundleMetadata::FIELD_VIDEO_IDS ] ?? array();
		$result     = $this->normalizer->normalize( $video_ids, $teacher_id );

		if ( ! $result->is_success() || null === $result->get_manifest() ) {
			return $result;
		}

		$manifest = $result->get_manifest();
		try {
			$conflicts = $this->manifest_store->get_video_conflicts( $product_id, $manifest->get_video_ids() );
		} catch ( Throwable $exception ) {
			unset( $exception );
			return BundleManifestSaveResult::failure(
				'conflict_check_failed',
				array( __( 'Existing bundle assignments could not be checked, so no manifest changes were saved.', 'alynt-isha-content-bundles' ) )
			);
		}
		if ( ! empty( $conflicts ) ) {
			return BundleManifestSaveResult::failure( 'duplicate_assignment', $this->format_conflict_messages( $conflicts ) );
		}

		$removed_video_ids = null === $current_manifest
			? array()
			: array_values( array_diff( $current_manifest->get_video_ids(), $manifest->get_video_ids() ) );
		$removal_context   = $this->get_removal_context( $product_id, $removed_video_ids, $request );

		if ( $removal_context instanceof BundleManifestSaveResult ) {
			return $removal_context;
		}

		try {
			$this->manifest_store->save_manifest( $product_id, $manifest );
		} catch ( Throwable $exception ) {
			unset( $exception );
			return BundleManifestSaveResult::failure(
				'save_failed',
				array( __( 'The bundle manifest could not be saved. Please retry after checking the site logs.', 'alynt-isha-content-bundles' ) )
			);
		}

		if ( ! empty( $removal_context ) && null !== $current_manifest ) {
			$audit_result = $this->record_removal_or_restore(
				$product_id,
				$user_id,
				$current_manifest,
				$removed_video_ids,
				$removal_context
			);
			if ( null !== $audit_result ) {
				return $audit_result;
			}
		}

		return BundleManifestSaveResult::success( 'saved', $manifest );
	}

	/**
	 * Get completed-order impact for the product editor.
	 *
	 * @param int $product_id Product ID.
	 * @return int
	 *
	 * @since 0.3.0
	 */
	public function get_completed_order_count( int $product_id ): int {
		try {
			return $this->manifest_store->get_completed_order_count( $product_id );
		} catch ( Throwable $exception ) {
			unset( $exception );
			return 0;
		}
	}

	/**
	 * Delete a manifest with sold-bundle removal protection.
	 *
	 * @param int                 $product_id      Product ID.
	 * @param int                 $user_id         Administrator user ID.
	 * @param array               $request         Admin request.
	 * @param BundleManifest|null $current_manifest Current manifest.
	 * @return BundleManifestSaveResult
	 */
	private function delete_manifest( int $product_id, int $user_id, array $request, ?BundleManifest $current_manifest ): BundleManifestSaveResult {
		$removed_video_ids = null === $current_manifest ? array() : $current_manifest->get_video_ids();
		$removal_context   = $this->get_removal_context( $product_id, $removed_video_ids, $request );

		if ( $removal_context instanceof BundleManifestSaveResult ) {
			return $removal_context;
		}

		try {
			$this->manifest_store->delete_manifest( $product_id );
		} catch ( Throwable $exception ) {
			unset( $exception );
			return BundleManifestSaveResult::failure(
				'delete_failed',
				array( __( 'The bundle manifest could not be removed. No changes were confirmed.', 'alynt-isha-content-bundles' ) )
			);
		}

		if ( ! empty( $removal_context ) && null !== $current_manifest ) {
			$audit_result = $this->record_removal_or_restore(
				$product_id,
				$user_id,
				$current_manifest,
				$removed_video_ids,
				$removal_context
			);
			if ( null !== $audit_result ) {
				return $audit_result;
			}
		}

		return BundleManifestSaveResult::success( 'deleted' );
	}

	/**
	 * Validate an access-revoking removal and return its audit context.
	 *
	 * @param int   $product_id       Product ID.
	 * @param int[] $removed_video_ids Removed video IDs.
	 * @param array $request          Admin request.
	 * @return array{reason:string,completed_order_count:int}|BundleManifestSaveResult
	 */
	private function get_removal_context( int $product_id, array $removed_video_ids, array $request ) {
		if ( empty( $removed_video_ids ) ) {
			return array();
		}

		try {
			$order_count = $this->manifest_store->get_completed_order_count( $product_id );
		} catch ( Throwable $exception ) {
			unset( $exception );
			return BundleManifestSaveResult::failure(
				'impact_check_failed',
				array( __( 'Completed-order impact could not be checked, so no bundle videos were removed.', 'alynt-isha-content-bundles' ) )
			);
		}

		if ( $order_count <= 0 ) {
			return array();
		}

		$confirmed = ! empty( $request[ BundleMetadata::FIELD_REMOVAL_CONFIRMED ] );
		$reason    = isset( $request[ BundleMetadata::FIELD_REMOVAL_REASON ] )
			? trim( wp_strip_all_tags( (string) $request[ BundleMetadata::FIELD_REMOVAL_REASON ] ) )
			: '';

		if ( ! $confirmed || '' === $reason ) {
			return BundleManifestSaveResult::failure(
				'removal_confirmation_required',
				array(
					sprintf(
						/* translators: 1: removed video IDs, 2: completed order count. */
						__( 'Removing video IDs %1$s would affect %2$d completed orders. Confirm the removal and provide a reason.', 'alynt-isha-content-bundles' ),
						implode( ', ', array_map( 'intval', $removed_video_ids ) ),
						$order_count
					),
				)
			);
		}

		return array(
			'reason'                => substr( $reason, 0, 1000 ),
			'completed_order_count' => $order_count,
		);
	}

	/**
	 * Record a sold-bundle removal or restore the prior manifest.
	 *
	 * @param int            $product_id       Product ID.
	 * @param int            $user_id          Administrator user ID.
	 * @param BundleManifest $current_manifest Previous manifest.
	 * @param int[]          $removed_video_ids Removed video IDs.
	 * @param array          $context           Audit context.
	 * @return BundleManifestSaveResult|null
	 */
	private function record_removal_or_restore(
		int $product_id,
		int $user_id,
		BundleManifest $current_manifest,
		array $removed_video_ids,
		array $context
	): ?BundleManifestSaveResult {
		try {
			$this->manifest_store->record_removal_audit(
				$product_id,
				$user_id,
				$removed_video_ids,
				$context['reason'],
				$context['completed_order_count']
			);
			return null;
		} catch ( Throwable $exception ) {
			unset( $exception );
		}

		try {
			$this->manifest_store->save_manifest( $product_id, $current_manifest );
		} catch ( Throwable $rollback_exception ) {
			unset( $rollback_exception );
			return BundleManifestSaveResult::failure(
				'audit_failed_rollback_failed',
				array( __( 'The removal audit and automatic manifest restoration both failed. Check the site logs immediately.', 'alynt-isha-content-bundles' ) )
			);
		}

		return BundleManifestSaveResult::failure(
			'audit_failed',
			array( __( 'The removal audit could not be saved, so the previous bundle manifest was restored.', 'alynt-isha-content-bundles' ) )
		);
	}

	/**
	 * Format cross-bundle assignment conflicts.
	 *
	 * @param array<int,int[]> $conflicts Bundle IDs keyed by video ID.
	 * @return string[]
	 */
	private function format_conflict_messages( array $conflicts ): array {
		$messages = array();

		foreach ( $conflicts as $video_id => $bundle_ids ) {
			$messages[] = sprintf(
				/* translators: 1: video ID, 2: bundle product IDs. */
				__( 'Video ID %1$d is already assigned to bundle product(s) %2$s.', 'alynt-isha-content-bundles' ),
				(int) $video_id,
				implode( ', ', array_map( 'intval', $bundle_ids ) )
			);
		}

		return $messages;
	}
}
