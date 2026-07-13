# Alynt ISHA Content Bundles Development Rules

## Project identity

- Plugin name: `Alynt ISHA Content Bundles`
- Slug and text domain: `alynt-isha-content-bundles`
- PHP namespace: `Alynt\ISHAContentBundles`
- Function, option, hook, and metadata prefix: `alynt_isha_content_bundles_`
- Constant prefix: `ALYNT_ISHA_CONTENT_BUNDLES_`
- Minimum PHP version: 7.4
- Minimum WordPress version: 6.0

## Safety boundaries

- Activation and deactivation must never perform product migrations, entitlement changes, or destructive cleanup.
- Production migrations must be explicit, idempotent, previewable, and separately approved.
- Preserve legacy individual-purchase access unless an approved migration rule explicitly says otherwise.
- Do not commit credentials, customer data, production exports, order records, private evidence, logs, backups, or local deployment configuration.
- Do not make retired products purchasable through direct add-to-cart requests.

## Engineering standards

- Follow WordPress Coding Standards and retain PHP 7.4 compatibility.
- Keep runtime code independent of Composer because `vendor/` is not shipped.
- Use namespaced, single-responsibility classes and explicit dependency boundaries.
- Sanitize input, validate identifiers and state, escape output, check capabilities, and verify nonces for every write operation.
- Use WooCommerce APIs for orders and products; support High-Performance Order Storage.
- Write automated tests for entitlement, migration, access, and deduplication behavior before production deployment.
- Keep public repository content environment-neutral and non-sensitive.

## Release rules

- Keep the plugin header version, `ALYNT_ISHA_CONTENT_BUNDLES_VERSION`, changelog, stable tag, Git tag, and release version aligned.
- Release ZIPs must contain exactly one top-level `alynt-isha-content-bundles` folder.
- Do not package tests, development dependencies, private planning artifacts, logs, or local evidence.
- Commit, push, tag, release, deploy, activate, and migrate only at their explicit approval gates.
