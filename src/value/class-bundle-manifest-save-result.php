<?php
/**
 * Bundle manifest save result.
 *
 * @package Alynt_ISHA_Content_Bundles
 */

namespace Alynt\ISHAContentBundles\Value;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Describes the result of an attempted manifest admin save.
 *
 * @since 0.2.0
 */
final class BundleManifestSaveResult {

	/**
	 * Whether the attempted save succeeded.
	 *
	 * @var bool
	 */
	private $success;

	/**
	 * Result code.
	 *
	 * @var string
	 */
	private $code;

	/**
	 * User-facing or loggable messages.
	 *
	 * @var string[]
	 */
	private $messages;

	/**
	 * Saved or validated manifest, when available.
	 *
	 * @var BundleManifest|null
	 */
	private $manifest;

	/**
	 * Create a result.
	 *
	 * @param bool                $success  Whether the operation succeeded.
	 * @param string              $code     Result code.
	 * @param string[]            $messages Messages.
	 * @param BundleManifest|null $manifest Manifest, when available.
	 */
	private function __construct(
		bool $success,
		string $code,
		array $messages = array(),
		?BundleManifest $manifest = null
	) {
		$this->success  = $success;
		$this->code     = $code;
		$this->messages = $messages;
		$this->manifest = $manifest;
	}

	/**
	 * Create a success result.
	 *
	 * @param string              $code     Result code.
	 * @param BundleManifest|null $manifest Manifest, when available.
	 * @return self
	 *
	 * @since 0.2.0
	 */
	public static function success( string $code, ?BundleManifest $manifest = null ): self {
		return new self( true, $code, array(), $manifest );
	}

	/**
	 * Create a failure result.
	 *
	 * @param string   $code     Result code.
	 * @param string[] $messages Messages.
	 * @return self
	 *
	 * @since 0.2.0
	 */
	public static function failure( string $code, array $messages ): self {
		return new self( false, $code, $messages );
	}

	/**
	 * Determine whether the operation succeeded.
	 *
	 * @return bool
	 *
	 * @since 0.2.0
	 */
	public function is_success(): bool {
		return $this->success;
	}

	/**
	 * Get the result code.
	 *
	 * @return string
	 *
	 * @since 0.2.0
	 */
	public function get_code(): string {
		return $this->code;
	}

	/**
	 * Get messages.
	 *
	 * @return string[]
	 *
	 * @since 0.2.0
	 */
	public function get_messages(): array {
		return $this->messages;
	}

	/**
	 * Get the manifest.
	 *
	 * @return BundleManifest|null
	 *
	 * @since 0.2.0
	 */
	public function get_manifest(): ?BundleManifest {
		return $this->manifest;
	}
}
