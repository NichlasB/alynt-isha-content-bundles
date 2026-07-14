# Bundle Manifests

Teacher bundle membership is represented by explicit product-level metadata. The WooCommerce simple-product editor renders and persists these fields through the same tested validation boundary.

## Metadata schema

- `_isha_bundle_video_ids`: ordered list of included video post IDs.
- `_isha_bundle_teacher_id`: owning teacher ID for validation and reporting.
- `_isha_bundle_runtime_seconds`: aggregate verified runtime for the manifest.
- `_isha_bundle_qualifies`: whether the runtime reaches the approved 3,540-second cutoff.
- `_isha_bundle_manifest_audit`: append-only sold-bundle removal records containing version, administrator user ID, UTC timestamp, removed video IDs, non-sensitive reason, and completed-order count.

The commercial target remains 3,600 seconds. The approved operational cutoff is 3,540 seconds, which gives a consistent 60-second grace window for every teacher.

## Save boundary

`BundleManifestAdminService` accepts product admin request data only when the bundle form marker is present. This prevents unrelated product saves from creating or deleting bundle metadata.

Before persistence, the service requires:

- `manage_woocommerce` capability through `AdminSecurityProvider`.
- A valid product-specific nonce using `alynt_isha_content_bundles_save_manifest_{product_id}`.
- Explicit bundle enablement.
- A normalized list of video IDs belonging to one teacher.

The normalizer rejects duplicate video IDs, cross-teacher video IDs, unknown videos, and non-published videos unless the video is intentionally retained. The persistence boundary also rejects a video assigned to any other non-trashed managed bundle. It calculates aggregate runtime and exposes the threshold result before publication decisions are made.

The WordPress adapter stores the normalized manifest, calculated runtime, qualification result, the approved `50.00` regular/current price, and virtual-product flag. A nonqualifying manifest may be retained for future additions, but catalog and purchase policy keeps it unavailable. Saving requires the normal WooCommerce product-save action, `manage_woocommerce`, and the product-specific nonce.

One teacher ID may appear on multiple products. Each product independently qualifies and grants only its own current manifest. The same video ID cannot appear in more than one managed bundle, which keeps public video routing deterministic.

Adding videos to any manifest, including a sold bundle, needs no order mutation: existing completed-order purchasers resolve the current manifest. Removing videos from, or disabling, a bundle with completed orders requires explicit confirmation and a non-empty reason. The change is accepted only when its audit entry is persisted; otherwise the previous manifest is restored when possible. Audit metadata is intentionally retained if bundle mode is later disabled.
