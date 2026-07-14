# Runtime Integration

`Plugin::run()` composes the tested policy services with WordPress and WooCommerce adapters after all plugins load. Activation, deactivation, and uninstall do not write data.

`RuntimeHooks` is a small coordinator. Direct route access, shortcode rendering, catalog/discovery policy, and WooCommerce bundle administration are registered by separate focused adapters. Request-local caches prevent repeated product-manifest, teacher-bundle, video-owner, and completed-purchase reads during a single page request. Teacher pages aggregate and deduplicate the published videos from all of that teacher's qualifying explicit manifests rather than scanning every video by that author.

## Hook Order

- `template_redirect` priority `1`: decide protected-video access and block direct visits to unavailable managed products before the active legacy access script can redirect.
- `init` priority `99`: remove the legacy `restrict_video_access` callback and replace `[purchased_videos]` and `[teacher_videos]` after Advanced Scripts has registered them.
- WooCommerce product filters priority `10`: preserve ordinary WooCommerce results for unrelated products and fail closed for retired or nonqualifying managed offers.
- `woocommerce_add_to_cart_validation` priority `10`: reject crafted add-to-cart requests for blocked offers.
- `the_posts` priority `20`: remove unavailable managed products, videos, and teachers from non-singular front-end discovery without suppressing singular redirect handling.
- WooCommerce product admin: render and save the explicit manifest through capability, nonce, teacher, publication, cross-bundle assignment, runtime, completed-order impact, confirmation, and audit validation.
- Video post admin: store a positive verified runtime through an `edit_post` capability and nonce-protected meta box before future content enters a manifest.

## Runtime Data Sources

- Completed orders are read with `wc_get_orders()` and therefore support WooCommerce HPOS.
- Legacy individual entitlements use the approved canonical 17-video mapping.
- Bundle entitlements use `_isha_bundle_video_ids` on the purchased bundle product.
- Verified video runtime first uses `_isha_verified_runtime_seconds`; the approved 2026-07-13 non-sensitive baseline is the fallback for the original 17 videos.
- Teacher ownership is the video or teacher post's WordPress author ID.
- Published qualifying bundles are resolved as a product-ID list through `_isha_bundle_teacher_id`; each product qualifies independently, and video routes use exact manifest membership.
- Completed-order impact for a sold-bundle removal is counted with HPOS-compatible `wc_get_orders()` reads.

## Controlled Coexistence

Initial inactive deployment is safe because no plugin code runs until activation. After activation, the plugin supersedes the three relevant active Advanced Scripts behaviors at runtime without modifying their stored terms. The stored scripts can therefore remain intact until replacement QA succeeds. Disabling them is a later approved production step, not an activation side effect.

## Filters

- `alynt_isha_content_bundles_unavailable_url` changes the fallback for a protected video without a qualifying bundle.
- `alynt_isha_content_bundles_blocked_product_url` changes the destination for direct visits to retired or nonqualifying products.

Both defaults remain same-site and use `wp_safe_redirect()`.

See [`HOOKS.md`](HOOKS.md) for argument details, examples, and compatibility policy.
