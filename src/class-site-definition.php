<?php
/**
 * Approved ISHA Classes content definition.
 *
 * @package Alynt_ISHA_Content_Bundles
 */

namespace Alynt\ISHAContentBundles;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Holds the non-sensitive IDs and verified runtimes approved for launch.
 */
final class SiteDefinition {

	const VIDEO_POST_TYPE           = 'video';
	const TEACHER_POST_TYPE         = 'teacher';
	const PRODUCT_POST_TYPE         = 'product';
	const TEACHER_DIRECTORY_PAGE_ID = 357;
	const RELATIONSHIP_META         = 'video_product_id';
	const RUNTIME_META              = '_isha_verified_runtime_seconds';
	const RETAINED_META             = '_isha_bundle_intentionally_retained';
	const SNAPSHOT_OPTION           = 'alynt_isha_content_bundles_migration_snapshot';
	const RUNTIME_FIELD             = 'alynt_isha_content_bundles_runtime_seconds';
	const RUNTIME_NONCE             = 'alynt_isha_content_bundles_runtime_nonce';
	const RUNTIME_ACTION            = 'alynt_isha_content_bundles_save_runtime';

	/**
	 * Get verified video runtimes in seconds.
	 *
	 * These measurements are the approved 2026-07-13 HLS baseline. A positive
	 * per-video runtime meta value takes precedence for future content.
	 *
	 * @return array<int,float>
	 */
	public static function video_runtimes(): array {
		return array(
			500  => 649.833333,
			507  => 651.8,
			510  => 1009.233333,
			514  => 2418.233333,
			522  => 905.633333,
			524  => 865.766667,
			531  => 1090.066667,
			536  => 1276.466667,
			705  => 649.1,
			718  => 686.633333,
			722  => 964.551644,
			727  => 1120.172389,
			733  => 1503.8,
			739  => 878.933333,
			1120 => 794.533333,
			1139 => 2766.125237,
			1157 => 1484.6,
		);
	}

	/**
	 * Get teacher post IDs keyed by their WordPress author IDs.
	 *
	 * @return array<int,int>
	 */
	public static function teacher_posts_by_author(): array {
		return array(
			4    => 385,
			5    => 443,
			6    => 441,
			7    => 333,
			1303 => 1106,
		);
	}

	/**
	 * Resolve a teacher post to its author ID.
	 *
	 * @param int $teacher_post_id Teacher post ID.
	 * @return int|null
	 */
	public static function get_teacher_author_id( int $teacher_post_id ): ?int {
		$author_id = array_search( $teacher_post_id, self::teacher_posts_by_author(), true );

		return false === $author_id ? null : (int) $author_id;
	}
}
