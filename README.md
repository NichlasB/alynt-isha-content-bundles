# Alynt ISHA Content Bundles

A site-specific WordPress plugin for managing explicit teacher content bundles while preserving compatible access to previously purchased individual videos.

## Status

Version `0.1.0` contains the activation-ready WordPress/WooCommerce integration for explicit teacher bundles, legacy entitlements, direct-video access, account and teacher shortcodes, catalog discovery, product administration, and approval-gated WP-CLI migration tooling. Activation and deactivation themselves remain write-free. Runtime writes occur only through an authorized bundle product save or an explicit signed migration apply/rollback command.

## Requirements

- WordPress 6.0 or newer
- PHP 7.4 or newer
- WooCommerce 10.9 or a compatible current release

## Development

Install local development dependencies with Composer:

```shell
php composer.phar install
```

Run the quality checks:

```shell
php ./vendor/bin/phpcs --standard=.phpcs.xml .
php ./vendor/bin/phpunit -c phpunit.xml.dist
```

The integration and hook priorities are documented in [`docs/RUNTIME_INTEGRATION.md`](docs/RUNTIME_INTEGRATION.md). The access-policy contract is documented in [`docs/ENTITLEMENTS.md`](docs/ENTITLEMENTS.md). The bundle metadata schema and admin validation boundary are documented in [`docs/BUNDLE_MANIFESTS.md`](docs/BUNDLE_MANIFESTS.md). The account-library data and presentation contract is documented in [`docs/PURCHASED_VIDEO_LIBRARY.md`](docs/PURCHASED_VIDEO_LIBRARY.md). Product, teacher, and video availability rules are documented in [`docs/ELIGIBILITY_AND_DISCOVERY.md`](docs/ELIGIBILITY_AND_DISCOVERY.md). The dry-run, apply, snapshot, idempotency, and rollback contract is documented in [`docs/MIGRATION_AND_ROLLBACK.md`](docs/MIGRATION_AND_ROLLBACK.md). The production-safe runtime remeasurement, bundle maintenance, verification, legacy-access, and rollback procedure is documented in [`docs/BUNDLE_MAINTENANCE.md`](docs/BUNDLE_MAINTENANCE.md).

## Release packaging

Publishing a GitHub release runs the release workflow and attaches an updater-compatible ZIP containing the exact top-level folder `alynt-isha-content-bundles`.

The plugin header identifies the public source as `NichlasB/alynt-isha-content-bundles`. No production credentials, customer records, database exports, migration evidence, or environment-specific secrets belong in this repository.

## Data and uninstall policy

Activation, deactivation, and uninstall perform no data changes. Bundle manifests are product metadata and the latest pre-write migration snapshot is stored in the `alynt_isha_content_bundles_migration_snapshot` option. Uninstall intentionally preserves those records so historical access and rollback evidence are not destroyed implicitly.

## License

GPL-2.0-or-later.
