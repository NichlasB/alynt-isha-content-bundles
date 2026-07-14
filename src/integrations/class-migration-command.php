<?php
/**
 * WP-CLI migration command.
 *
 * @package Alynt_ISHA_Content_Bundles
 */

namespace Alynt\ISHAContentBundles\Integrations;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Alynt\ISHAContentBundles\Services\RelationshipMigrationService;
use Throwable;

/**
 * Exposes preview, signed apply, and rollback operations through WP-CLI.
 *
 * @since 0.2.0
 */
final class MigrationCommand {

	/**
	 * Migration service.
	 *
	 * @var RelationshipMigrationService
	 */
	private $service;

	/**
	 * Persisted WordPress migration store.
	 *
	 * @var WordPressMigrationStore
	 */
	private $store;

	/**
	 * Create the command.
	 *
	 * @param RelationshipMigrationService $service Migration service.
	 * @param WordPressMigrationStore      $store   WordPress store.
	 *
	 * @since 0.2.0
	 */
	public function __construct( RelationshipMigrationService $service, WordPressMigrationStore $store ) {
		$this->service = $service;
		$this->store   = $store;
	}

	/**
	 * Preview exact relationship changes without writing.
	 *
	 * ## EXAMPLES
	 *
	 *     wp isha-content-bundles migration preview
	 *
	 * @return void
	 *
	 * @since 0.2.0
	 */
	public function preview(): void {
		$plan = $this->service->preview();

		\WP_CLI::line(
			(string) wp_json_encode(
				array(
					'signature'  => $plan->get_signature(),
					'applicable' => $plan->is_applicable(),
					'plan'       => $plan->to_array(),
				),
				JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
			)
		);

		if ( ! $plan->is_applicable() ) {
			\WP_CLI::warning( __( 'Drift conflicts block apply mode.', 'alynt-isha-content-bundles' ) );
		}
	}

	/**
	 * Apply the exact reviewed preview.
	 *
	 * ## OPTIONS
	 *
	 * --signature=<sha256>
	 * : Signature printed by the immediately reviewed preview.
	 *
	 * [--yes]
	 * : Required explicit write confirmation.
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Named arguments.
	 * @return void
	 *
	 * @since 0.2.0
	 */
	public function apply( array $args, array $assoc_args ): void {
		unset( $args );
		$this->require_confirmation( $assoc_args );

		$plan      = $this->service->preview();
		$signature = isset( $assoc_args['signature'] ) ? strtolower( trim( (string) $assoc_args['signature'] ) ) : '';

		if ( '' === $signature || ! hash_equals( $plan->get_signature(), $signature ) ) {
			\WP_CLI::error( __( 'The supplied signature does not match the fresh preview.', 'alynt-isha-content-bundles' ) );
		}

		$result = $this->service->apply( $plan );
		if ( ! $result->is_success() ) {
			/* translators: %s: Stable migration result code. */
			\WP_CLI::error( sprintf( __( 'Migration failed: %s', 'alynt-isha-content-bundles' ), $result->get_code() ) );
		}

		\WP_CLI::success(
			sprintf(
				/* translators: 1: Stable migration result code. 2: Number of changed videos. */
				__( 'Migration result: %1$s; changed videos: %2$d.', 'alynt-isha-content-bundles' ),
				$result->get_code(),
				$result->get_change_count()
			)
		);
	}

	/**
	 * Restore the persisted pre-write snapshot.
	 *
	 * ## OPTIONS
	 *
	 * [--yes]
	 * : Required explicit write confirmation.
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Named arguments.
	 * @return void
	 *
	 * @since 0.2.0
	 */
	public function rollback( array $args, array $assoc_args ): void {
		unset( $args );
		$this->require_confirmation( $assoc_args );
		try {
			$snapshot = $this->store->load_persisted_snapshot();
		} catch ( Throwable $exception ) {
			unset( $exception );
			\WP_CLI::error( __( 'The persisted migration snapshot is invalid or incompatible.', 'alynt-isha-content-bundles' ) );
			return;
		}

		if ( null === $snapshot ) {
			\WP_CLI::error( __( 'No persisted migration snapshot is available.', 'alynt-isha-content-bundles' ) );
		}

		$result = $this->service->rollback( $snapshot );
		if ( ! $result->is_success() ) {
			/* translators: %s: Stable rollback result code. */
			\WP_CLI::error( sprintf( __( 'Rollback failed: %s', 'alynt-isha-content-bundles' ), $result->get_code() ) );
		}

		\WP_CLI::success( __( 'Rollback restored and verified the persisted snapshot.', 'alynt-isha-content-bundles' ) );
	}

	/**
	 * Require an explicit --yes flag before a write operation.
	 *
	 * @param array $assoc_args Named arguments.
	 * @return void
	 */
	private function require_confirmation( array $assoc_args ): void {
		if ( ! isset( $assoc_args['yes'] ) || true !== filter_var( $assoc_args['yes'], FILTER_VALIDATE_BOOLEAN ) ) {
			\WP_CLI::error( __( 'Write mode requires the explicit --yes flag.', 'alynt-isha-content-bundles' ) );
		}
	}
}
