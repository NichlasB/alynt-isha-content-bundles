<?php
/**
 * Bundle manifest value object.
 *
 * @package Alynt_ISHA_Content_Bundles
 */

namespace Alynt\ISHAContentBundles\Value;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Alynt\ISHAContentBundles\BundleMetadata;

/**
 * Represents a normalized teacher bundle manifest.
 *
 * @since 0.2.0
 */
final class BundleManifest {

	/**
	 * Teacher ID.
	 *
	 * @var int
	 */
	private $teacher_id;

	/**
	 * Included video IDs.
	 *
	 * @var int[]
	 */
	private $video_ids;

	/**
	 * Aggregate runtime in seconds.
	 *
	 * @var float
	 */
	private $runtime_seconds;

	/**
	 * Create a manifest.
	 *
	 * @param int   $teacher_id       Teacher ID.
	 * @param int[] $video_ids        Included video IDs.
	 * @param float $runtime_seconds  Aggregate runtime in seconds.
	 *
	 * @since 0.2.0
	 */
	public function __construct( int $teacher_id, array $video_ids, float $runtime_seconds ) {
		$this->teacher_id      = $teacher_id;
		$this->video_ids       = array_values( $video_ids );
		$this->runtime_seconds = $runtime_seconds;
	}

	/**
	 * Get the teacher ID.
	 *
	 * @return int
	 *
	 * @since 0.2.0
	 */
	public function get_teacher_id(): int {
		return $this->teacher_id;
	}

	/**
	 * Get the included video IDs.
	 *
	 * @return int[]
	 *
	 * @since 0.2.0
	 */
	public function get_video_ids(): array {
		return $this->video_ids;
	}

	/**
	 * Get the aggregate runtime in seconds.
	 *
	 * @return float
	 *
	 * @since 0.2.0
	 */
	public function get_runtime_seconds(): float {
		return $this->runtime_seconds;
	}

	/**
	 * Determine whether the manifest reaches the approved cutoff.
	 *
	 * @return bool
	 *
	 * @since 0.2.0
	 */
	public function qualifies(): bool {
		return $this->runtime_seconds >= BundleMetadata::QUALIFYING_SECONDS;
	}
}
