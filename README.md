# Alynt ISHA Content Bundles

A site-specific WordPress plugin for managing explicit teacher content bundles while preserving compatible access to previously purchased individual videos.

## Features

- Sells one explicit, same-teacher video bundle for USD `50.00` when its verified runtime reaches the approved 3,540-second qualifying cutoff.
- Includes the approved Raw Chef Gail grace case without weakening the one-minute grace policy for other teachers.
- Retires individual video products from new discovery and purchase while preserving access from completed historical orders.
- Resolves bundle purchases to the current explicit video manifest, so later approved additions are available to existing bundle customers.
- Protects direct video routes, bundle redirects, the account library, teacher pages, the Video Shop, and the compiled teacher directory through one eligibility policy.
- Provides capability- and nonce-protected WooCommerce product fields for bundle administration.
- Provides preview-first, signature-gated WP-CLI migration and verified rollback commands.

## Status

Version `0.2.0` contains the activation-ready WordPress/WooCommerce integration for explicit teacher bundles, legacy entitlements, direct-video access, account and teacher shortcodes, catalog discovery, product administration, and approval-gated WP-CLI migration tooling. Activation and deactivation themselves remain write-free. Runtime writes occur only through an authorized bundle product save or an explicit signed migration apply/rollback command.

## Requirements

- WordPress 6.0 or newer
- PHP 7.4 or newer
- WooCommerce 10.9 or a compatible current release

## Installation

1. Download the release ZIP attached to the matching GitHub release.
2. In WordPress, open **Plugins > Add New > Upload Plugin** and upload the ZIP.
3. Activate **Alynt ISHA Content Bundles**.
4. Confirm WooCommerce is active and run the verification checklist in [`docs/BUNDLE_MAINTENANCE.md`](docs/BUNDLE_MAINTENANCE.md).

Activation is write-free. Do not run migration apply or rollback commands unless a fresh preview, current GridPane restore point, and explicit production authorization are in place.

## Usage

Administrators maintain a teacher bundle from the WooCommerce product editor. Enable **ISHA teacher bundle**, enter the teacher author ID, and provide the explicit comma-separated Video post IDs. On save, the plugin verifies ownership, publication/retention state, duplicates, measured runtime, fixed price, and virtual-product state.

Video runtime is maintained on the Video post in **Verified runtime (seconds)**. Use the HLS segment-duration method and operational procedure in [`docs/BUNDLE_MAINTENANCE.md`](docs/BUNDLE_MAINTENANCE.md); do not estimate from a rounded player display.

The production shortcodes remain:

```text
[purchased_videos]
[teacher_videos]
[teacher_videos teacher_id="123"]
```

Migration commands are documented in [`docs/MIGRATION_AND_ROLLBACK.md`](docs/MIGRATION_AND_ROLLBACK.md). Public actions and filters are documented in [`docs/HOOKS.md`](docs/HOOKS.md).

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

The integration and hook priorities are documented in [`docs/RUNTIME_INTEGRATION.md`](docs/RUNTIME_INTEGRATION.md). The access-policy contract is documented in [`docs/ENTITLEMENTS.md`](docs/ENTITLEMENTS.md). The bundle metadata schema and admin validation boundary are documented in [`docs/BUNDLE_MANIFESTS.md`](docs/BUNDLE_MANIFESTS.md). The account-library data and presentation contract is documented in [`docs/PURCHASED_VIDEO_LIBRARY.md`](docs/PURCHASED_VIDEO_LIBRARY.md). Product, teacher, and video availability rules are documented in [`docs/ELIGIBILITY_AND_DISCOVERY.md`](docs/ELIGIBILITY_AND_DISCOVERY.md). The dry-run, apply, snapshot, idempotency, and rollback contract is documented in [`docs/MIGRATION_AND_ROLLBACK.md`](docs/MIGRATION_AND_ROLLBACK.md). The production-safe runtime remeasurement, bundle maintenance, verification, legacy-access, and rollback procedure is documented in [`docs/BUNDLE_MAINTENANCE.md`](docs/BUNDLE_MAINTENANCE.md). Release history is maintained in [`CHANGELOG.md`](CHANGELOG.md).

## Frequently asked questions

### Why is the cutoff 3,540 seconds instead of exactly 3,600 seconds?

The approved uniform 60-second grace window accounts for small encoding and media-segment boundary differences. It includes Raw Chef Gail's approved bundle while remaining a clear, repeatable rule.

### What happens when a teacher does not qualify?

Their managed bundle, videos, and teacher listing are removed from public discovery and new purchase. Content and historical entitlement records are retained so the teacher can requalify after adding enough verified content.

### Do existing bundle buyers receive videos added later?

Yes. A completed bundle purchase resolves against the bundle's current explicit manifest. This is the approved access default.

### Does uninstall remove plugin data?

No. Uninstall intentionally preserves bundle metadata, verified runtimes, legacy relationships, and migration evidence to prevent accidental entitlement loss.

### Is NovaMira MCP required?

No. This repository and the live GridPane workflow do not require a LocalWP database import. NovaMira is only relevant when a task needs the separately defined LocalWP database workflow.

## Release packaging

Publishing a GitHub release runs the release workflow and attaches an updater-compatible ZIP containing the exact top-level folder `alynt-isha-content-bundles`.

The plugin header identifies the public source as `NichlasB/alynt-isha-content-bundles`. No production credentials, customer records, database exports, migration evidence, or environment-specific secrets belong in this repository.

## Data and uninstall policy

Activation, deactivation, and uninstall perform no data changes. Bundle manifests are product metadata and the latest pre-write migration snapshot is stored in the `alynt_isha_content_bundles_migration_snapshot` option. Uninstall intentionally preserves those records so historical access and rollback evidence are not destroyed implicitly.

## License

GPL-2.0-or-later.
