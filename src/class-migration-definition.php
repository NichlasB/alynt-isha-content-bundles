<?php
/**
 * ISHA Classes migration definition.
 *
 * @package Alynt_ISHA_Content_Bundles
 */

namespace Alynt\ISHAContentBundles;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Defines the approved T3 legacy relationship baseline and snapshot scope.
 */
final class MigrationDefinition {

	/**
	 * Get the canonical video-to-legacy-product map.
	 *
	 * @return array<int,int>
	 */
	public static function legacy_product_map(): array {
		return array(
			500  => 504,
			507  => 509,
			510  => 512,
			514  => 516,
			522  => 519,
			524  => 525,
			531  => 530,
			536  => 534,
			705  => 704,
			718  => 719,
			722  => 723,
			727  => 728,
			733  => 734,
			739  => 738,
			1120 => 1121,
			1139 => 1140,
			1157 => 1158,
		);
	}

	/**
	 * Get the exact T3 relationship-row values before cleanup.
	 *
	 * @return array<int,string[]>
	 */
	public static function baseline_relationships(): array {
		$baseline = self::target_relationships();

		$baseline[522] = array( '519', '519' );
		$baseline[524] = array( '525', '525' );
		$baseline[722] = array( '', '723', '723' );
		$baseline[727] = array( '728', '728', '728' );

		return $baseline;
	}

	/**
	 * Get normalized relationship-row values.
	 *
	 * @return array<int,string[]>
	 */
	public static function target_relationships(): array {
		$targets = array();

		foreach ( self::legacy_product_map() as $video_id => $product_id ) {
			$targets[ $video_id ] = array( (string) $product_id );
		}

		return $targets;
	}

	/**
	 * Get legacy products that must remain snapshot-protected.
	 *
	 * @return int[]
	 */
	public static function legacy_product_ids(): array {
		return array_values( self::legacy_product_map() );
	}

	/**
	 * Get teacher posts that must remain snapshot-protected.
	 *
	 * @return int[]
	 */
	public static function teacher_post_ids(): array {
		return array( 333, 385, 441, 443, 1106 );
	}

	/**
	 * Get Advanced Scripts terms that must remain snapshot-protected.
	 *
	 * @return int[]
	 */
	public static function script_term_ids(): array {
		return array( 44, 45, 51, 54 );
	}
}
