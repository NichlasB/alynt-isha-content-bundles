<?php
/**
 * Runtime hook coordinator.
 *
 * @package Alynt_ISHA_Content_Bundles
 */

namespace Alynt\ISHAContentBundles\Integrations;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers the focused WordPress and WooCommerce hook adapters.
 *
 * @since 0.2.0
 */
final class RuntimeHooks {

	/**
	 * Direct-access hook adapter.
	 *
	 * @var DirectAccessHooks
	 */
	private $direct_access_hooks;

	/**
	 * Shortcode hook adapter.
	 *
	 * @var ShortcodeHooks
	 */
	private $shortcode_hooks;

	/**
	 * Catalog hook adapter.
	 *
	 * @var CatalogHooks
	 */
	private $catalog_hooks;

	/**
	 * Bundle-product admin adapter.
	 *
	 * @var BundleProductAdminHooks
	 */
	private $bundle_admin_hooks;

	/**
	 * Create the runtime hook coordinator.
	 *
	 * @param DirectAccessHooks       $direct_access_hooks Direct-access hooks.
	 * @param ShortcodeHooks          $shortcode_hooks     Shortcode hooks.
	 * @param CatalogHooks            $catalog_hooks       Catalog hooks.
	 * @param BundleProductAdminHooks $bundle_admin_hooks  Product-admin hooks.
	 *
	 * @since 0.2.0
	 */
	public function __construct(
		DirectAccessHooks $direct_access_hooks,
		ShortcodeHooks $shortcode_hooks,
		CatalogHooks $catalog_hooks,
		BundleProductAdminHooks $bundle_admin_hooks
	) {
		$this->direct_access_hooks = $direct_access_hooks;
		$this->shortcode_hooks     = $shortcode_hooks;
		$this->catalog_hooks       = $catalog_hooks;
		$this->bundle_admin_hooks  = $bundle_admin_hooks;
	}

	/**
	 * Register all runtime hooks at deterministic priorities.
	 *
	 * @return void
	 *
	 * @since 0.2.0
	 */
	public function register(): void {
		$this->direct_access_hooks->register();
		$this->shortcode_hooks->register();
		$this->catalog_hooks->register();
		$this->bundle_admin_hooks->register();
	}
}
