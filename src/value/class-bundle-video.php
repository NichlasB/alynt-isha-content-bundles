<?php
/**
 * Bundle video value object.
 *
 * @package Alynt_ISHA_Content_Bundles
 */

namespace Alynt\ISHAContentBundles\Value;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Represents the video facts used during bundle validation.
 *
 * @since 0.2.0
 */
final class BundleVideo {

	/**
	 * Video post ID.
	 *
	 * @var int
	 */
	private $id;

	/**
	 * Owning teacher ID.
	 *
	 * @var int
	 */
	private $teacher_id;

	/**
	 * WordPress post status.
	 *
	 * @var string
	 */
	private $status;

	/**
	 * Runtime in seconds.
	 *
	 * @var float
	 */
	private $runtime_seconds;

	/**
	 * Whether this non-published video is intentionally retained.
	 *
	 * @var bool
	 */
	private $intentionally_retained;

	/**
	 * Create a video record.
	 *
	 * @param int    $id                     Video post ID.
	 * @param int    $teacher_id             Owning teacher ID.
	 * @param string $status                 WordPress post status.
	 * @param float  $runtime_seconds        Runtime in seconds.
	 * @param bool   $intentionally_retained Whether a non-published video can be stored.
	 *
	 * @since 0.2.0
	 */
	public function __construct(
		int $id,
		int $teacher_id,
		string $status,
		float $runtime_seconds,
		bool $intentionally_retained = false
	) {
		$this->id                     = $id;
		$this->teacher_id             = $teacher_id;
		$this->status                 = $status;
		$this->runtime_seconds        = $runtime_seconds;
		$this->intentionally_retained = $intentionally_retained;
	}

	/**
	 * Get the video ID.
	 *
	 * @return int
	 *
	 * @since 0.2.0
	 */
	public function get_id(): int {
		return $this->id;
	}

	/**
	 * Get the owning teacher ID.
	 *
	 * @return int
	 *
	 * @since 0.2.0
	 */
	public function get_teacher_id(): int {
		return $this->teacher_id;
	}

	/**
	 * Get the runtime in seconds.
	 *
	 * @return float
	 *
	 * @since 0.2.0
	 */
	public function get_runtime_seconds(): float {
		return $this->runtime_seconds;
	}

	/**
	 * Determine whether the video can be stored in a manifest.
	 *
	 * @return bool
	 *
	 * @since 0.2.0
	 */
	public function is_storable(): bool {
		return 'publish' === $this->status || $this->intentionally_retained;
	}
}
