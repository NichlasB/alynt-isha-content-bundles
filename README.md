# Alynt ISHA Content Bundles

A site-specific WordPress plugin for managing explicit teacher content bundles while preserving compatible access to previously purchased individual videos.

## Status

Version `0.1.0` is an inert scaffold. It registers the plugin lifecycle and a namespaced runtime shell, but it does not change products, orders, entitlements, post metadata, or customer data.

## Requirements

- WordPress 6.0 or newer
- PHP 7.4 or newer
- WooCommerce integration will be introduced in a later implementation phase

## Development

Install local development dependencies with Composer:

```shell
php composer.phar install
```

Run the quality checks:

```shell
php ./vendor/bin/phpcs --standard=.phpcs.xml .
php ./vendor/bin/phpunit -c phpunit.xml.dist
```

## Release packaging

Publishing a GitHub release runs the release workflow and attaches an updater-compatible ZIP containing the exact top-level folder `alynt-isha-content-bundles`.

The plugin header identifies the public source as `NichlasB/alynt-isha-content-bundles`. No production credentials, customer records, database exports, migration evidence, or environment-specific secrets belong in this repository.

## Data and uninstall policy

The initial scaffold owns no persistent data. Activation, deactivation, and uninstall therefore perform no data changes. Any future persistent-data cleanup policy must preserve purchased-content access and be documented before implementation.

## License

GPL-2.0-or-later.
