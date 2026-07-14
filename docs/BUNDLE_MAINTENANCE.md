# Bundle Maintenance

This guide is the operational procedure for measuring teacher content, maintaining an existing bundle, introducing a new teacher bundle, and preserving historical access.

## Commercial policy

- A teacher bundle is sold for USD `50.00`.
- The commercial runtime target is 3,600 seconds (one hour).
- The approved qualifying cutoff is 3,540 seconds. This uniform 60-second grace window includes content that is only slightly short because of encoding or segment-boundary differences, including the approved Raw Chef Gail bundle.
- Qualification is based on the combined verified runtime of the explicit videos in each bundle. Videos from different teachers must never be combined.
- One teacher may have multiple bundles. Every bundle independently qualifies, sells for USD `50.00`, and uses a distinct explicit manifest.
- One video may belong to only one managed bundle, including while products are draft or private.

The plugin applies these rules server-side. A retired individual product or a nonqualifying managed bundle remains unavailable even if somebody crafts a direct product or add-to-cart URL.

## Remeasure a video

Use the actual playable HLS media as the source of truth. Sum the `#EXTINF` durations from the complete media playlist, including every segment. Do not estimate from titles, descriptions, thumbnails, upload timestamps, or a rounded player label.

Before changing production data:

1. Create or confirm a current GridPane restore point.
2. Confirm that the playlist is reachable and complete.
3. Record the video post ID, teacher owner, playlist URL, measured seconds, measurement date, and method in local operational evidence. Do not put private URLs, customer data, credentials, or production exports in this public repository.

In WordPress, edit the Video post and save the positive measured value in the **Verified runtime (seconds)** field. The `_isha_verified_runtime_seconds` metadata value takes precedence over the embedded 2026-07-13 baseline.

If the playlist cannot be measured reliably, retain the last verified value and do not change eligibility until measurement succeeds.

## Add a video to an existing bundle

1. Publish the Video post and confirm that its WordPress author is the correct teacher owner.
2. Save its verified runtime as described above.
3. Edit the teacher's existing WooCommerce bundle product.
4. In the ISHA teacher-bundle fields, add the video post ID to the explicit ordered manifest. Keep all existing IDs that should remain included.
5. Confirm that every manifest video is published, belongs to the same teacher, appears only once, and is intentionally part of the offer.
6. Save the product and confirm the calculated runtime and qualification result.
7. Keep the product simple, virtual, sold individually, in stock, and priced at USD `50.00`.
8. For a published qualifying bundle, confirm the `Teacher Bundles` product category and the `video` product tag are assigned. Retired individual products must remain without the `video` tag.
9. Run the verification checklist below.

Bundle entitlement is resolved from the purchased product's current explicit manifest. Therefore, customers who already completed a purchase of that bundle automatically receive access to a video added later. Adding a video to one bundle does not grant it to purchasers of the teacher's other bundles.

## Remove a video or disable a sold bundle

Removing a manifest video revokes access for customers whose only entitlement came from that bundle. Disabling bundle mode removes the entire manifest and has the same consequence for every included video.

Before either action:

1. Confirm a current GridPane restore point and the exact product being edited.
2. Review the completed-order count displayed in the bundle fields.
3. Confirm that the intended videos are being removed and that no historical-access promise requires them to remain.
4. Check **Confirm sold-bundle removal** and enter a concise non-sensitive reason. Do not include customer names, email addresses, order details, or other personal information.
5. Save once and verify the resulting manifest, qualification, product availability, customer access, and application logs.

The plugin records the administrator user ID, UTC timestamp, removed video IDs, reason, and completed-order count in append-only `_isha_bundle_manifest_audit` product metadata. If impact lookup fails, the removal is blocked. If the audit cannot be saved, the plugin attempts to restore the complete previous manifest and reports the failure. An unsold bundle can be reorganized without sold-bundle confirmation, but cross-bundle video conflicts are always blocked.

## Create another bundle for a teacher

1. Measure every candidate video and confirm the combined verified runtime is at least 3,540 seconds.
2. Create a WooCommerce simple product for that teacher and enable the ISHA teacher-bundle fields.
3. Set the exact teacher owner ID and explicit ordered video post IDs.
4. Save while the product is draft and confirm the normalized manifest, calculated runtime, qualification flag, USD `50.00` price, virtual state, stock state, and sold-individually behavior.
5. Confirm that none of the candidate video IDs appears in any other managed bundle, including drafts. A teacher ID may be shared across bundles; a video ID may not.
6. Give the product a content-specific title and description so customers can distinguish it from the teacher's other bundles.
7. Assign the `Teacher Bundles` category and `video` product tag only when the bundle qualifies and is ready for discovery.
8. Publish only after the verification checklist passes.

If the combined runtime is below 3,540 seconds, retain the product as a draft for future additions or remove it from publication and discovery. The teacher, videos, legacy mappings, orders, and entitlement records must not be deleted.

## Verification checklist

After a manifest or eligibility change, verify all of the following:

- The bundle product is USD `50.00`, visible, purchasable, sold individually, and in stock.
- The saved teacher ID, video IDs, calculated runtime, and qualifying result are exact.
- The teacher resolves to all of their published qualifying bundles without affecting unrelated bundles.
- Every included video appears once on the teacher page and a nonbuyer is routed to the exact qualifying bundle containing that video.
- No video ID appears in more than one managed bundle manifest.
- A completed bundle buyer can access every current manifest video.
- A buyer of another bundle from the same teacher does not receive this bundle unless they also purchased it.
- Historical buyers of individual products can still access the original video.
- Nonqualifying teachers and their videos are absent from discovery.
- The Video Shop contains the expected bundle cards and no retired individual product cards.
- Retired individual products are non-visible, non-purchasable, blocked on direct add-to-cart, and have no `video` tag.
- The teacher directory contains only teachers with published qualifying bundles.
- `wp isha-content-bundles migration preview` reports no conflicts or unexpected relationship changes.
- Key routes return HTTP 200, the public maintenance experience is unchanged when enabled, and WordPress/PHP application logs contain no new plugin anomaly.

Use an isolated test customer and a non-payment checkout method only under a separately approved production QA plan. Remove temporary users, roles, coupons, orders, sessions, contacts, and carts when that plan requires cleanup; retain normal audit/history records.

## Legacy compatibility

The canonical video-to-individual-product relationships and completed historical orders are permanent entitlement inputs. Do not delete or repurpose the 17 legacy products, remove their relationship metadata, edit historical orders, or use uninstall as cleanup. Retirement means unavailable for new discovery and purchase, not erased.

After any relationship work, preview first:

```shell
wp isha-content-bundles migration preview
```

Apply and rollback are production writes and require an independently reviewed signature, a current restore point, and explicit authorization:

```shell
wp isha-content-bundles migration apply --signature=<sha256> --yes
wp isha-content-bundles migration rollback --yes
```

## Deployment and rollback

- Treat the plugin repository as the authoritative source. Package and deploy only a verified release artifact with the exact `alynt-isha-content-bundles` top-level folder.
- Confirm local tests, coding standards, syntax, dependency audit, package exclusions, and file hashes before deployment.
- Deploy code before applying any approval-gated database migration, then verify site health while the migration preview remains read-only.
- Keep a current GridPane backup as the durable full-site restore point. A short-lived server-side file copy may be retained during observation, but its deletion is a separate cleanup decision.
- Prefer code-file restoration for a code regression. Use the signed migration rollback only for state covered by its stored snapshot and only with explicit approval.
- Activation, deactivation, and uninstall are intentionally write-free; uninstall preserves bundle and migration records.

The site-wide Coming Soon system is external to this plugin. Bundle maintenance must not change or bypass it unless that behavior is separately authorized and tested.
