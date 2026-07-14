# Entitlement Resolution

The central resolver owns the access policy. WordPress and WooCommerce adapters supply facts; they do not independently decide access.

## Provider contracts

- `UserAccessProvider` determines whether a positive user ID has administrator access.
- `PurchaseProvider` returns product purchases and their order statuses.
- `ContentMapProvider` returns a legacy product's one-video mapping, a bundle product's explicit manifest, and the complete video inventory used for administrator libraries.

## Resolution rules

1. Anonymous and invalid user IDs receive no access.
2. Administrators may access any valid video request and their library resolves to the known video inventory.
3. Only `completed` or `wc-completed` purchases are entitlement-bearing.
4. A legacy product grants only its mapped individual video.
5. A bundle product grants every valid positive video ID in its explicit manifest.
6. A customer who purchased multiple bundles receives the union of their current manifests.
7. Overlapping legacy and bundle entitlements are deduplicated and returned in numeric order.
8. Invalid or malformed manifest values are ignored without invalidating the remaining manifest.

Manifests are read dynamically rather than copied into order metadata. An administrator-approved video addition therefore becomes immediately available to every completed-order purchaser of that specific bundle. A purchase of another bundle by the same teacher does not grant the added video.

The resolver performs no writes. The WordPress and WooCommerce adapters supply administrator capability, completed HPOS-compatible orders, the canonical legacy map, explicit bundle manifests, and the video inventory.

## Direct video access decisions

`VideoAccessController` converts entitlement facts and route facts into a small decision object:

- `allow` for entitled legacy purchasers, entitled bundle purchasers, administrators, and unprotected requests.
- `redirect` to the qualifying bundle offer when a protected video has an available bundle route.
- `redirect` to the unavailable-content destination when a protected video has no available bundle route.
- `deny` when the video ID is invalid or no safe redirect target exists.

The controller prevents redirect loops by refusing a target that matches the current request URL. `RuntimeHooks` executes its decision at `template_redirect` priority `1`. Entitled requests pass through; non-buyers go to a qualifying bundle or the filterable unavailable destination. At `init` priority `99`, the plugin removes the active legacy `restrict_video_access` callback so it cannot override bundle entitlements later in the same request.
