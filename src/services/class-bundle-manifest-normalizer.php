<?php
/**
 * Bundle manifest normalizer.
 *
 * @package Alynt_ISHA_Content_Bundles
 */

namespace Alynt\ISHAContentBundles\Services;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Alynt\ISHAContentBundles\Contracts\BundleContentProvider;
use Alynt\ISHAContentBundles\Value\BundleManifest;
use Alynt\ISHAContentBundles\Value\BundleManifestSaveResult;

/**
 * Sanitizes and validates explicit teacher bundle manifests.
 *
 * @since 0.2.0
 */
final class BundleManifestNormalizer {

	/**
	 * Content provider.
	 *
	 * @var BundleContentProvider
	 */
	private $content_provider;

	/**
	 * Create the normalizer.
	 *
	 * @param BundleContentProvider $content_provider Content provider.
	 *
	 * @since 0.2.0
	 */
	public function __construct( BundleContentProvider $content_provider ) {
		$this->content_provider = $content_provider;
	}

	/**
	 * Normalize raw video IDs into a validated manifest.
	 *
	 * @param array<int|string|mixed>|string $raw_video_ids Raw video IDs.
	 * @param int                            $teacher_id    Expected teacher ID. Zero derives from first video.
	 * @return BundleManifestSaveResult
	 *
	 * @since 0.2.0
	 */
	public function normalize( $raw_video_ids, int $teacher_id = 0 ): BundleManifestSaveResult {
		$parsed              = $this->parse_video_ids( $raw_video_ids );
		$ids                 = $parsed['ids'];
		$seen                = array();
		$videos              = array();
		$errors              = $parsed['errors'];
		$runtime             = 0.0;
		$manifest_teacher_id = $teacher_id;

		if ( empty( $ids ) ) {
			return BundleManifestSaveResult::failure( 'empty_manifest', array( __( 'At least one video ID is required.', 'alynt-isha-content-bundles' ) ) );
		}

		foreach ( $ids as $video_id ) {
			if ( isset( $seen[ $video_id ] ) ) {
				/* translators: %d: Video post ID. */
				$errors[] = sprintf( __( 'Video ID %d is duplicated.', 'alynt-isha-content-bundles' ), $video_id );
				continue;
			}

			$seen[ $video_id ] = true;
			$video             = $this->content_provider->get_video( $video_id );

			if ( null === $video || ! $video->is_storable() ) {
				/* translators: %d: Video post ID. */
				$errors[] = sprintf( __( 'Video ID %d is not a valid storable video.', 'alynt-isha-content-bundles' ), $video_id );
				continue;
			}

			if ( 0 === $manifest_teacher_id ) {
				$manifest_teacher_id = $video->get_teacher_id();
			}

			if ( $video->get_teacher_id() !== $manifest_teacher_id ) {
				/* translators: %d: Video post ID. */
				$errors[] = sprintf( __( 'Video ID %d belongs to a different teacher.', 'alynt-isha-content-bundles' ), $video_id );
				continue;
			}

			$videos[] = $video_id;
			$runtime += $video->get_runtime_seconds();
		}

		if ( 0 === $manifest_teacher_id ) {
			$errors[] = __( 'A teacher ID could not be determined from the submitted videos.', 'alynt-isha-content-bundles' );
		}

		if ( ! empty( $errors ) ) {
			return BundleManifestSaveResult::failure( 'invalid_manifest', $errors );
		}

		return BundleManifestSaveResult::success(
			'valid_manifest',
			new BundleManifest( $manifest_teacher_id, $videos, $runtime )
		);
	}

	/**
	 * Parse raw admin input into positive integer IDs.
	 *
	 * @param array<int|string|mixed>|string $raw_video_ids Raw video IDs.
	 * @return array{ids:int[],errors:string[]}
	 */
	private function parse_video_ids( $raw_video_ids ): array {
		$parts  = is_array( $raw_video_ids ) ? $raw_video_ids : preg_split( '/[\s,]+/', (string) $raw_video_ids );
		$ids    = array();
		$errors = array();

		foreach ( (array) $parts as $part ) {
			$part = trim( (string) $part );

			if ( '' === $part ) {
				continue;
			}

			if ( 1 === preg_match( '/^[1-9][0-9]*$/D', $part ) ) {
				$ids[] = (int) $part;
				continue;
			}

			/* translators: %s: Submitted video ID value. */
			$errors[] = sprintf( __( 'Video ID value "%s" is not a positive integer.', 'alynt-isha-content-bundles' ), $part );
		}

		return array(
			'ids'    => $ids,
			'errors' => $errors,
		);
	}
}
