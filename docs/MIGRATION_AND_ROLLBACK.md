# Migration And Rollback Contract

`RelationshipMigrationService` provides the approval-safe core for previewing, applying, verifying, and rolling back the ISHA Classes relationship cleanup. `WordPressMigrationStore` supplies exact metadata-row storage and complete rollback snapshots; `MigrationCommand` exposes the operations through WP-CLI.

## Canonical Definition

`MigrationDefinition` encodes the non-sensitive T3 baseline:

- all 17 video-to-legacy-product mappings
- the exact duplicate row values captured for videos `522`, `524`, `722`, and `727`
- normalized targets containing one valid `video_product_id` row per video
- protected teacher posts `333`, `385`, `441`, `443`, and `1106`
- protected Advanced Scripts terms `44`, `45`, `51`, and `54`
- all 17 legacy products that must remain available to historical entitlement checks

The four planned relationship replacements are:

| Video | Before | After |
|---:|---|---|
| `522` | `519, 519` | `519` |
| `524` | `525, 525` | `525` |
| `722` | blank, `723, 723` | `723` |
| `727` | `728, 728, 728` | `728` |

Every other mapped video is checked for drift but requires no cleanup at the T3 baseline.

## Preview

`preview()` reads every canonical relationship and returns a `MigrationPlan` containing:

- every exact before/after replacement
- any relationship that matches neither the approved baseline nor the already-normalized target

Any conflict makes the plan non-applicable. Preview performs no writes.

## Apply

`apply()` requires the previously reviewed `MigrationPlan` and then:

1. Generates a fresh preview.
2. Rejects detected drift or a changed preview before any snapshot or write.
3. Returns `no_changes` when the site is already normalized.
4. Requires the storage adapter to capture and durably persist a complete snapshot before the first write.
5. Replaces only the rows listed in the reviewed plan.
6. Re-runs preview to verify there are no remaining changes or conflicts.
7. Automatically restores the snapshot if a write or verification step fails.

This makes a normal rerun idempotent and prevents an old approval from being applied after live state changes.

## Snapshot And Rollback

`MigrationSnapshot` contains logical state for:

- all protected relationship rows
- relevant legacy and managed products
- all five teacher posts
- the four Advanced Scripts records

Snapshots are persisted in `alynt_isha_content_bundles_migration_snapshot` before the first relationship write. They include protected post core fields, metadata, taxonomy assignments, Advanced Scripts term metadata, and exact relationship rows. They do not include orders, customers, credentials, tokens, or other private production data.

`rollback()` restores the complete snapshot and verifies that current state matches it. Product/teacher/script restoration is included even though the current cleanup engine writes only relationship rows, allowing the same pre-write snapshot to protect later approval-gated migration steps.

## Runtime Boundary

Preview is read-only:

```shell
wp isha-content-bundles migration preview
```

It prints the exact plan and its SHA-256 signature. Apply requires both the fresh signature and an explicit write flag:

```shell
wp isha-content-bundles migration apply --signature=<sha256> --yes
```

Rollback requires the persisted snapshot and the same explicit write flag:

```shell
wp isha-content-bundles migration rollback --yes
```

The command registration does not authorize execution. Production apply and rollback remain separate approval-gated operations. Bundle product IDs remain absent until draft products are created under that gate.
