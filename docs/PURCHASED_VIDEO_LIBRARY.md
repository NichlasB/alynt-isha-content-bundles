# Purchased-Video Library Contract

The purchased-video library is split into three boundaries so the account area uses the same access policy as direct-video requests:

1. `EntitlementResolver` returns the complete, sorted, and deduplicated set of video IDs granted by completed legacy and bundle purchases.
2. `PurchasedVideoLibraryResolver` maps those IDs to `LibraryVideo` presentation records through `VideoLibraryProvider`.
3. `PurchasedVideoLibraryRenderer` renders the established account-library card structure from those records.

## Preserved Presentation

The renderer preserves the existing `[purchased_videos]` behavior:

- outer `purchased-videos` list and `video-item` cards
- featured-image thumbnail when available
- category names separated with ` | `
- 32-pixel WordPress avatar markup and author display name
- video title
- `Watch` link to the video permalink
- the existing logged-out and empty-library messages

Dynamic text and URL attributes are escaped. Unsafe URL schemes are rejected. Avatar HTML is the one trusted-markup boundary: the WordPress adapter supplies platform-generated `get_avatar()` output rather than arbitrary stored HTML.

## Entitlement Behavior

- A completed legacy product purchase contributes only its mapped video.
- A completed bundle purchase contributes every video in its explicit manifest.
- Multiple completed bundle purchases contribute the union of their current manifests, including later administrator-approved additions.
- A customer who owns both paths receives each video once.
- Missing video presentation records are skipped rather than replaced with a different post.
- Anonymous and invalid user IDs resolve to an empty library.

The runtime replaces `[purchased_videos]` at `init` priority `99`, after the current Advanced Scripts registration. The adapter reads completed orders with `wc_get_orders()`, resolves both legacy and bundle products through the central entitlement policy, and renders the deduplicated result. The same controlled replacement registers `[teacher_videos]`, preserving its established list classes while hiding teachers and videos without a published qualifying bundle.
