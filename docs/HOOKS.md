# Public Hooks

This document lists the plugin's public extension points. All hook names use the `alynt_isha_content_bundles_` prefix.

## `alynt_isha_content_bundles_loaded`

Action fired after the plugin has registered its runtime integrations.

```php
add_action(
	'alynt_isha_content_bundles_loaded',
	function () {
		// Register a compatible integration after the bundle runtime is ready.
	}
);
```

The action receives no arguments and has existed since version `0.1.0`.

## `alynt_isha_content_bundles_blocked_product_url`

Filters the safe redirect destination used when somebody visits a retired individual product or another managed product that is unavailable for purchase.

```php
add_filter(
	'alynt_isha_content_bundles_blocked_product_url',
	function ( $url, $product_id ) {
		return $url;
	},
	10,
	2
);
```

Parameters:

- `$url` (`string`): Default home-page URL.
- `$product_id` (`int`): Blocked WooCommerce product ID.

The returned value must be an absolute local URL accepted by `wp_safe_redirect()`. An unsafe or rejected destination produces a controlled error instead of continuing to protected content. The filter has existed since version `0.2.0`.

## `alynt_isha_content_bundles_unavailable_url`

Filters the destination used when a published video has no qualifying teacher bundle and the current user has no preserved legacy entitlement.

```php
add_filter(
	'alynt_isha_content_bundles_unavailable_url',
	function ( $url, $video_id ) {
		return $url;
	},
	10,
	2
);
```

Parameters:

- `$url` (`string`): Default unavailable-content destination.
- `$video_id` (`int`): Video post ID.

The returned URL must not resolve back to the protected video, and it must remain acceptable to WordPress safe-redirect validation. The filter has existed since version `0.2.0`.

## Compatibility policy

Hook names, argument order, and core semantics are public API. Additive hooks may be introduced in minor versions. Removing a hook or changing its established arguments requires a major-version compatibility review and a changelog notice.
