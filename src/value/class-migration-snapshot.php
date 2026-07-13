<?php
/**
 * Migration rollback snapshot value object.
 *
 * @package Alynt_ISHA_Content_Bundles
 */

namespace Alynt\ISHAContentBundles\Value;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use InvalidArgumentException;

/**
 * Stores the complete logical state required by an approved rollback.
 */
final class MigrationSnapshot {

	const VERSION = 1;

	/**
	 * Relationship row values keyed by video ID.
	 *
	 * @var array<int,string[]>
	 */
	private $relationships;

	/**
	 * Legacy and managed product states keyed by product ID.
	 *
	 * @var array<int,array<string,mixed>>
	 */
	private $products;

	/**
	 * Teacher states keyed by teacher post ID.
	 *
	 * @var array<int,array<string,mixed>>
	 */
	private $teachers;

	/**
	 * Advanced Scripts states keyed by term ID.
	 *
	 * @var array<int,array<string,mixed>>
	 */
	private $scripts;

	/**
	 * Create a rollback snapshot.
	 *
	 * @param array<int,string[]>            $relationships Relationship row values.
	 * @param array<int,array<string,mixed>> $products      Product states.
	 * @param array<int,array<string,mixed>> $teachers      Teacher states.
	 * @param array<int,array<string,mixed>> $scripts       Advanced Scripts states.
	 */
	public function __construct(
		array $relationships,
		array $products,
		array $teachers,
		array $scripts
	) {
		$this->relationships = $relationships;
		$this->products      = $products;
		$this->teachers      = $teachers;
		$this->scripts       = $scripts;
	}

	/**
	 * Get relationship row values.
	 *
	 * @return array<int,string[]>
	 */
	public function get_relationships(): array {
		return $this->relationships;
	}

	/**
	 * Get protected product states.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	public function get_products(): array {
		return $this->products;
	}

	/**
	 * Get protected teacher states.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	public function get_teachers(): array {
		return $this->teachers;
	}

	/**
	 * Get protected Advanced Scripts states.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	public function get_scripts(): array {
		return $this->scripts;
	}

	/**
	 * Export the snapshot for durable adapter storage.
	 *
	 * @return array<string,mixed>
	 */
	public function to_array(): array {
		return array(
			'version'       => self::VERSION,
			'relationships' => $this->relationships,
			'products'      => $this->products,
			'teachers'      => $this->teachers,
			'scripts'       => $this->scripts,
		);
	}

	/**
	 * Rehydrate a snapshot loaded by a runtime adapter.
	 *
	 * @param array<string,mixed> $data Stored snapshot data.
	 * @return self
	 * @throws InvalidArgumentException When the stored snapshot is incomplete or incompatible.
	 */
	public static function from_array( array $data ): self {
		$required_arrays = array( 'relationships', 'products', 'teachers', 'scripts' );

		if ( ! isset( $data['version'] ) || self::VERSION !== (int) $data['version'] ) {
			throw new InvalidArgumentException( 'The migration snapshot version is missing or incompatible.' );
		}

		foreach ( $required_arrays as $required_key ) {
			if ( ! isset( $data[ $required_key ] ) || ! is_array( $data[ $required_key ] ) ) {
				throw new InvalidArgumentException( 'The migration snapshot is incomplete.' );
			}
		}

		return new self(
			$data['relationships'],
			$data['products'],
			$data['teachers'],
			$data['scripts']
		);
	}
}
