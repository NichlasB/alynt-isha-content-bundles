<?php
/**
 * Migration execution result value object.
 *
 * @package Alynt_ISHA_Content_Bundles
 */

namespace Alynt\ISHAContentBundles\Value;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Describes an apply or rollback outcome without performing output.
 */
final class MigrationExecutionResult {

	/**
	 * Whether execution succeeded.
	 *
	 * @var bool
	 */
	private $success;

	/**
	 * Stable result code.
	 *
	 * @var string
	 */
	private $code;

	/**
	 * Applied change count.
	 *
	 * @var int
	 */
	private $change_count;

	/**
	 * Pre-write rollback snapshot.
	 *
	 * @var MigrationSnapshot|null
	 */
	private $snapshot;

	/**
	 * Create an execution result.
	 *
	 * @param bool                   $success      Whether execution succeeded.
	 * @param string                 $code         Stable result code.
	 * @param int                    $change_count Applied change count.
	 * @param MigrationSnapshot|null $snapshot     Pre-write rollback snapshot.
	 */
	private function __construct(
		bool $success,
		string $code,
		int $change_count = 0,
		?MigrationSnapshot $snapshot = null
	) {
		$this->success      = $success;
		$this->code         = $code;
		$this->change_count = $change_count;
		$this->snapshot     = $snapshot;
	}

	/**
	 * Create a successful result.
	 *
	 * @param string                 $code         Stable result code.
	 * @param int                    $change_count Applied change count.
	 * @param MigrationSnapshot|null $snapshot     Pre-write rollback snapshot.
	 * @return self
	 */
	public static function success(
		string $code,
		int $change_count = 0,
		?MigrationSnapshot $snapshot = null
	): self {
		return new self( true, $code, $change_count, $snapshot );
	}

	/**
	 * Create a failed result.
	 *
	 * @param string $code Stable result code.
	 * @return self
	 */
	public static function failure( string $code ): self {
		return new self( false, $code );
	}

	/**
	 * Determine whether execution succeeded.
	 *
	 * @return bool
	 */
	public function is_success(): bool {
		return $this->success;
	}

	/**
	 * Get the stable result code.
	 *
	 * @return string
	 */
	public function get_code(): string {
		return $this->code;
	}

	/**
	 * Get the applied change count.
	 *
	 * @return int
	 */
	public function get_change_count(): int {
		return $this->change_count;
	}

	/**
	 * Get the pre-write rollback snapshot.
	 *
	 * @return MigrationSnapshot|null
	 */
	public function get_snapshot(): ?MigrationSnapshot {
		return $this->snapshot;
	}
}
