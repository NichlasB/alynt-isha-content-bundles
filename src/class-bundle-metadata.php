<?php
/**
 * Bundle metadata schema.
 *
 * @package Alynt_ISHA_Content_Bundles
 */

namespace Alynt\ISHAContentBundles;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Defines bundle product metadata and admin form keys.
 *
 * @since 0.2.0
 */
final class BundleMetadata {

	const META_VIDEO_IDS       = '_isha_bundle_video_ids';
	const META_TEACHER_ID      = '_isha_bundle_teacher_id';
	const META_RUNTIME_SECONDS = '_isha_bundle_runtime_seconds';
	const META_QUALIFIES       = '_isha_bundle_qualifies';
	const META_MANIFEST_AUDIT  = '_isha_bundle_manifest_audit';

	const FIELD_PRESENT           = 'alynt_isha_content_bundles_manifest_present';
	const FIELD_ENABLED           = 'alynt_isha_content_bundles_is_bundle';
	const FIELD_VIDEO_IDS         = 'alynt_isha_content_bundles_video_ids';
	const FIELD_TEACHER_ID        = 'alynt_isha_content_bundles_teacher_id';
	const FIELD_NONCE             = 'alynt_isha_content_bundles_manifest_nonce';
	const FIELD_REMOVAL_CONFIRMED = 'alynt_isha_content_bundles_removal_confirmed';
	const FIELD_REMOVAL_REASON    = 'alynt_isha_content_bundles_removal_reason';

	const NONCE_ACTION_PREFIX = 'alynt_isha_content_bundles_save_manifest_';
	const SAVE_CAPABILITY     = 'manage_woocommerce';
	const TARGET_SECONDS      = 3600;
	const QUALIFYING_SECONDS  = 3540;
	const BUNDLE_PRICE        = '50.00';

	/**
	 * Build the nonce action for a product save.
	 *
	 * @param int $product_id Product ID.
	 * @return string
	 *
	 * @since 0.2.0
	 */
	public static function nonce_action( int $product_id ): string {
		return self::NONCE_ACTION_PREFIX . $product_id;
	}
}
