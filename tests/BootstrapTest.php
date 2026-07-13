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
 * Verifies the inert plugin scaffold.
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
	public function test_runtime_is_registered_without_feature_hooks() {
		$actions = $GLOBALS['alynt_isha_content_bundles_test_hooks']['actions'];

		$this->assertCount( 1, $actions['plugins_loaded'] );
		$this->assertInstanceOf( Plugin::class, $actions['plugins_loaded'][0][0] );

		$plugin = new Plugin();
		$plugin->run();

		$this->assertContains(
			'alynt_isha_content_bundles_loaded',
			$GLOBALS['alynt_isha_content_bundles_test_actions']
		);
	}
}
