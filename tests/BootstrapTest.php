<?php
/**
 * Bootstrap tests.
 *
 * @package Alynt_ISHA_Content_Bundles
 */

namespace Alynt\ISHAContentBundles\Tests;

use Alynt\ISHAContentBundles\Activator;
use Alynt\ISHAContentBundles\Deactivator;
use Alynt\ISHAContentBundles\Plugin;
use PHPUnit\Framework\TestCase;

/**
 * Verifies plugin identity, lifecycle, and runtime composition.
 */
final class BootstrapTest extends TestCase {

	/**
	 * Verify the stable plugin identity.
	 *
	 * @return void
	 */
	public function test_plugin_identity_constants_are_defined() {
		$this->assertSame( '0.1.0', ALYNT_ISHA_CONTENT_BUNDLES_VERSION );
		$this->assertSame(
			'alynt-isha-content-bundles/alynt-isha-content-bundles.php',
			ALYNT_ISHA_CONTENT_BUNDLES_PLUGIN_BASENAME
		);
	}

	/**
	 * Verify lifecycle callbacks are registered and safe to call.
	 *
	 * @return void
	 */
	public function test_lifecycle_callbacks_are_registered() {
		$hooks = $GLOBALS['alynt_isha_content_bundles_test_hooks'];

		$this->assertSame(
			array( Activator::class, 'activate' ),
			$hooks['activation'][1]
		);
		$this->assertSame(
			array( Deactivator::class, 'deactivate' ),
			$hooks['deactivation'][1]
		);

		Activator::activate();
		Deactivator::deactivate();
		$this->addToAssertionCount( 2 );
	}

	/**
	 * Verify runtime registration and the lifecycle action.
	 *
	 * @return void
	 */
	public function test_runtime_registers_feature_hooks_at_controlled_priorities() {
		$actions = $GLOBALS['alynt_isha_content_bundles_test_hooks']['actions'];

		$this->assertCount( 1, $actions['plugins_loaded'] );
		$this->assertInstanceOf( Plugin::class, $actions['plugins_loaded'][0][0] );

		$plugin = new Plugin();
		$plugin->run();
		$hooks  = $GLOBALS['alynt_isha_content_bundles_test_hooks'];

		$this->assertSame( 99, $hooks['action_details']['init'][0]['priority'] );
		$this->assertSame( 1, $hooks['action_details']['template_redirect'][0]['priority'] );
		$this->assertSame( 2, $hooks['filters']['woocommerce_is_purchasable'][0]['accepted_args'] );
		$this->assertSame( 3, $hooks['filters']['woocommerce_add_to_cart_validation'][0]['accepted_args'] );
		$this->assertSame( 20, $hooks['filters']['the_posts'][0]['priority'] );
		$this->assertArrayHasKey( 'add_meta_boxes_video', $hooks['actions'] );
		$this->assertSame( 3, $hooks['action_details']['save_post_video'][0]['accepted_args'] );

		$hooks['action_details']['init'][0]['callback']();
		$this->assertContains(
			array(
				'hook'     => 'template_redirect',
				'callback' => 'restrict_video_access',
				'priority' => 10,
			),
			$GLOBALS['alynt_isha_content_bundles_test_hooks']['removed_actions']
		);
		$this->assertArrayHasKey( 'purchased_videos', $GLOBALS['alynt_isha_content_bundles_test_shortcodes'] );
		$this->assertArrayHasKey( 'teacher_videos', $GLOBALS['alynt_isha_content_bundles_test_shortcodes'] );

		$this->assertContains(
			'alynt_isha_content_bundles_loaded',
			$GLOBALS['alynt_isha_content_bundles_test_actions']
		);
	}
}
