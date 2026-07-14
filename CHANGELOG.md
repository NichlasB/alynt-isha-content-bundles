# Changelog

All notable changes to this project are documented in this file.

## Unreleased

## 0.3.0 - 2026-07-14

- Allow one teacher to have multiple independently qualifying USD 50 bundles without making their catalog state ambiguous.
- Route each video to the qualifying bundle whose explicit manifest contains it and aggregate all qualifying bundle manifests on teacher pages.
- Preserve dynamic completed-order entitlements so videos appended to a sold bundle become available to its existing purchasers immediately.
- Reject cross-bundle video assignments across published and unpublished managed bundle products.
- Show completed-order impact in the product editor and require explicit confirmation plus a reason before removing videos from, or disabling, a sold bundle.
- Store append-only non-sensitive removal audit metadata and restore the prior manifest when audit persistence fails.
- Add fail-closed conflict and order-impact handling plus expanded catalog, append, removal, disabling, audit, and rollback tests.
- Update operator, manifest, entitlement, discovery, runtime, and release documentation for administrator-only multi-bundle management.

## 0.2.0 - 2026-07-14

- Add provider contracts and a central entitlement resolver for administrator, legacy-product, and explicit bundle access.
- Enforce completed-order status, malformed-manifest filtering, and deduplicated video resolution in the policy core.
- Add the T5 entitlement acceptance matrix and architecture documentation.
- Add the bundle manifest metadata schema, admin capability/nonce boundary, manifest normalizer, persistence contract, and validation tests.
- Add the video access decision layer for entitled pass-through, bundle redirects, unavailable-content redirects, and redirect-loop prevention.
- Add the entitlement-backed purchased-video library resolver, legacy-compatible card renderer, presentation value object, and bundle/legacy/deduplication tests.
- Add shared catalog eligibility, discovery, and purchase-blocking policy for qualifying bundles, nonqualifying teachers, retired legacy products, and unrelated-product pass-through.
- Add the canonical T3 legacy mapping definition and approval-safe migration preview, drift detection, idempotent apply, durable-snapshot boundary, automatic failure rollback, and complete logical rollback tests.
- Connect the policy core to WordPress, WooCommerce, and HPOS-compatible order APIs through production adapters.
- Add deterministic access, catalog, add-to-cart, shortcode, discovery-query, and bundle product administration hooks.
- Supersede the active legacy access and shortcode callbacks at controlled priorities without changing Advanced Scripts during plugin activation.
- Add signed WP-CLI migration preview/apply/rollback commands with explicit `--yes` write gates and a durable complete snapshot.
- Embed the approved non-sensitive runtime baseline, including the Raw Chef Gail grace qualification, with a per-video metadata override for future content.
- Filter the compiled Brizy teacher-directory snapshot through the central eligibility policy so nonqualifying cards stay hidden until a qualifying bundle is published.
- Document the approved runtime remeasurement, existing/new bundle maintenance, verification, legacy-access, deployment, and rollback procedure.
- Split runtime and migration responsibilities into focused adapters and add request-local caching for repeated eligibility and purchase reads.
- Add controlled manifest persistence failures, verified metadata restoration without a second deserialization pass, safe-redirect failure handling, and incompatible-snapshot CLI reporting.
- Add translated front-end and administration strings, a source POT catalog, accessible watch-link labels, and namespaced presentation classes alongside the legacy markup contract.
- Expand installation, usage, FAQ, public-hook, and uninstall-retention documentation for the first feature release.

## 0.1.0 - 2026-07-13

- Add the inert plugin bootstrap and namespaced runtime shell.
- Add side-effect-free activation, deactivation, and uninstall behavior.
- Add PHPUnit, WordPress Coding Standards, PHP compatibility, and continuous-integration configuration.
- Add Alynt Plugin Updater-compatible GitHub release packaging.
