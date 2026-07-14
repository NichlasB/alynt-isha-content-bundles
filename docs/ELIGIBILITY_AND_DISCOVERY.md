# Eligibility And Discovery Policy

`CatalogEligibilityPolicy` is the shared decision layer for teacher discovery, video discovery, bundle-product discovery, and server-side purchase blocking. `RuntimeHooks` translates those decisions to WordPress query results, templates, WooCommerce product filters, direct add-to-cart validation, and redirect destinations.

## Qualification Source

A bundle is available only when all of these facts agree:

- the product is explicitly managed as a bundle
- the product is not a retired legacy individual-video offer
- a normalized explicit `BundleManifest` exists
- the manifest runtime is at least 3,540 seconds
- the teacher-to-bundle relationship points back to that same product

The policy never infers qualification from video count. The commercial target remains 3,600 seconds, while the approved operational cutoff is the consistent 3,540-second grace threshold.

At the currently verified runtimes, Marie Lohan, Rosemarie “Pele” Chen, and Raw Chef Gail qualify. Kristina Manasieva and Matt Bennett remain unavailable until a future explicit manifest reaches the cutoff.

## Product Rules

- Retired legacy products are hidden from discovery and blocked from new purchase.
- Qualifying reciprocal bundle products may remain discoverable and are not blocked by this policy.
- Nonqualifying, missing-manifest, or inconsistent bundle products are hidden and blocked.
- Unrelated WooCommerce products are left unchanged.
- A non-blocked result does not force a sale; ordinary WooCommerce status, stock, and purchasability rules still apply.

Legacy product records and mappings remain available to the entitlement resolver. Hiding or blocking a legacy offer therefore does not revoke access from a customer with a completed historical purchase.

## Teacher And Video Rules

A teacher is discoverable when at least one qualifying reciprocal bundle product can be resolved for that teacher owner. A teacher may have multiple independently qualifying bundles. A video is discoverable only when it appears in one of that teacher's qualifying explicit manifests. The exact manifest-membership lookup supplies the single qualifying bundle product used by video cards and non-buyer redirects.

Unknown, invalid, missing, or inconsistent managed relationships fail closed. If legacy data places one video in multiple qualifying manifests, that video's route fails closed until an administrator resolves the overlap; the teacher's other valid bundles remain available. Non-singular front-end query results remove unavailable managed products, videos, and teachers; singular requests remain present long enough for the access controller to redirect correctly. The WooCommerce purchasability, visibility, and add-to-cart filters enforce the same policy server-side while unrelated products pass through unchanged.

The Brizy Posts element on teacher-directory page `357` stores compiled card HTML rather than rerunning its teacher query on every request. A page-scoped rendered-content filter therefore removes only cards whose teacher permalinks fail the same central policy. Brizy's stored builder document is left unchanged, so publishing a future qualifying bundle automatically makes that teacher's existing card eligible again.
