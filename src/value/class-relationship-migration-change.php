<?php
/**
 * Relationship migration change value object.
 *
 * @package Alynt_ISHA_Content_Bundles
 */

namespace Alynt\ISHAContentBundles\Value;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use InvalidArgumentException;

/**
 * Represents one exact video relationship replacement.
 */
final class RelationshipMigrationChange {

	/**
	 * Video post ID.
	 *
	 * @var int
	 */
	private $video_id;

	/**
	 * Expected current row values.
	 *
	 * @var string[]
	 */
	private $before_values;

	/**
	 * Replacement row values.
	 *
	 * @var string[]
	 */
	private $after_values;

	/**
	 * Create a relationship change.
	 *
	 * @param int      $video_id      Video post ID.
	 * @param string[] $before_values Expected current row values.
	 * @param string[] $after_values  Replacement row values.
	 * @throws InvalidArgumentException When the video ID is invalid.
	 */
	public function __construct( int $video_id, array $before_values, array $after_values ) {
		if ( $video_id <= 0 ) {
			throw new InvalidArgumentException( 'A relationship change requires a positive video ID.' );
		}

		$this->video_id      = $video_id;
		$this->before_values = array_values( $before_values );
		$this->after_values  = array_values( $after_values );
	}

	/**
	 * Get the video post ID.
	 *
	 * @return int
	 */
	public function get_video_id(): int {
		return $this->video_id;
	}

	/**
	 * Get expected current row values.
	 *
	 * @return string[]
	 */
	public function get_before_values(): array {
		return $this->before_values;
	}

	/**
	 * Get replacement row values.
	 *
	 * @return string[]
	 */
	public function get_after_values(): array {
		return $this->after_values;
	}

	/**
	 * Export the change for previews or serialization.
	 *
	 * @return array{video_id:int,before:string[],after:string[]}
	 */
	public function to_array(): array {
		return array(
			'video_id' => $this->video_id,
			'before'   => $this->before_values,
			'after'    => $this->after_values,
		);
	}
}
