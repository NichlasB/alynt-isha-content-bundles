<?php
/**
 * Plugin runtime.
 *
 * @package Alynt_ISHA_Content_Bundles
 */

namespace Alynt\ISHAContentBundles;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Coordinates plugin services and integrations.
 *
 * @since 0.1.0
 */
final class Plugin {

	/**
	 * Whether runtime registration has completed.
	 *
	 * @var bool
	 */
	private $started = false;

	/**
	 * Start the plugin runtime.
	 *
	 * @return void
	 *
	 * @since 0.1.0
	 */
	public function run() {
		if ( $this->started ) {
			return;
		}

		$this->started = true;
		load_plugin_textdomain(
			'alynt-isha-content-bundles',
			false,
			dirname( ALYNT_ISHA_CONTENT_BUNDLES_PLUGIN_BASENAME ) . '/languages'
		);
		$catalog_provider  = new Integrations\WordPressCatalogEligibilityProvider();
		$catalog_policy    = new Services\CatalogEligibilityPolicy( $catalog_provider );
		$access_provider   = new Integrations\WordPressAccessContentProvider();
		$purchase_provider = new Integrations\WooCommercePurchaseProvider();
		$entitlements      = new Services\EntitlementResolver(
			$access_provider,
			$purchase_provider,
			$access_provider
		);
		$video_provider    = new Integrations\WordPressVideoLibraryProvider();
		$library_resolver  = new Services\PurchasedVideoLibraryResolver( $entitlements, $video_provider );
		$manifest_admin    = new Services\BundleManifestAdminService(
			new Integrations\WordPressAdminSecurityProvider(),
			new Services\BundleManifestNormalizer( new Integrations\WordPressBundleContentProvider() ),
			new Integrations\WordPressBundleManifestStore()
		);
		$route_provider    = new Integrations\WordPressVideoRouteProvider( $catalog_policy );
		$runtime           = new Integrations\RuntimeHooks(
			new Integrations\DirectAccessHooks(
				new Services\VideoAccessController( $entitlements, $route_provider ),
				$catalog_policy
			),
			new Integrations\ShortcodeHooks(
				$catalog_policy,
				$library_resolver,
				new Services\PurchasedVideoLibraryRenderer(),
				new Integrations\WordPressTeacherVideoLibrary( $catalog_provider, $video_provider ),
				new Services\TeacherVideoRenderer()
			),
			new Integrations\CatalogHooks(
				$catalog_policy,
				new Services\TeacherDirectoryContentFilter()
			),
			new Integrations\BundleProductAdminHooks( $manifest_admin )
		);
		$runtime->register();
		$video_runtime_admin = new Integrations\VideoRuntimeAdmin();
		$video_runtime_admin->register();

		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			$migration_store = new Integrations\WordPressMigrationStore();
			$migration       = new Services\RelationshipMigrationService( $migration_store );
			\WP_CLI::add_command(
				'isha-content-bundles migration',
				new Integrations\MigrationCommand( $migration, $migration_store )
			);
		}

		/**
		 * Fires after the plugin runtime has loaded.
		 *
		 * @since 0.1.0
		 */
		do_action( 'alynt_isha_content_bundles_loaded' );
	}
}
