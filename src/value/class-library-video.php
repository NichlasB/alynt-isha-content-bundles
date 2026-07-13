<?php
/**
 * Purchased-video library value object.
 *
 * @package Alynt_ISHA_Content_Bundles
 */

namespace Alynt\ISHAContentBundles\Value;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use InvalidArgumentException;

/**
 * Represents the presentation data for one entitled video.
 */
final class LibraryVideo {

	/**
	 * Video post ID.
	 *
	 * @var int
	 */
	private $id;

	/**
	 * Video title.
	 *
	 * @var string
	 */
	private $title;

	/**
	 * Video permalink.
	 *
	 * @var string
	 */
	private $watch_url;

	/**
	 * Featured image URL.
	 *
	 * @var string
	 */
	private $thumbnail_url;

	/**
	 * Author display name.
	 *
	 * @var string
	 */
	private $author_name;

	/**
	 * Author email used for avatar lookup.
	 *
	 * @var string
	 */
	private $author_email;

	/**
	 * Category names.
	 *
	 * @var string[]
	 */
	private $categories;

	/**
	 * Safe platform-generated avatar HTML.
	 *
	 * @var string
	 */
	private $author_avatar_html;

	/**
	 * Create a library video.
	 *
	 * Avatar HTML must already be safe platform-generated markup.
	 *
	 * @param int      $id                 Video post ID.
	 * @param string   $title              Video title.
	 * @param string   $watch_url          Video permalink.
	 * @param string   $thumbnail_url      Featured image URL.
	 * @param string   $author_name        Author display name.
	 * @param string   $author_email       Author email used for avatar lookup.
	 * @param string[] $categories         Category names.
	 * @param string   $author_avatar_html Safe avatar HTML.
	 * @throws InvalidArgumentException When the video ID is invalid.
	 */
	public function __construct(
		int $id,
		string $title,
		string $watch_url,
		string $thumbnail_url,
		string $author_name,
		string $author_email,
		array $categories,
		string $author_avatar_html = ''
	) {
		if ( $id <= 0 ) {
			throw new InvalidArgumentException( 'A library video requires a positive video ID.' );
		}

		$this->id                 = $id;
		$this->title              = $title;
		$this->watch_url          = $watch_url;
		$this->thumbnail_url      = $thumbnail_url;
		$this->author_name        = $author_name;
		$this->author_email       = $author_email;
		$this->categories         = $this->normalize_categories( $categories );
		$this->author_avatar_html = $author_avatar_html;
	}

	/**
	 * Get the video post ID.
	 *
	 * @return int
	 */
	public function get_id(): int {
		return $this->id;
	}

	/**
	 * Get the video title.
	 *
	 * @return string
	 */
	public function get_title(): string {
		return $this->title;
	}

	/**
	 * Get the video permalink.
	 *
	 * @return string
	 */
	public function get_watch_url(): string {
		return $this->watch_url;
	}

	/**
	 * Get the featured image URL.
	 *
	 * @return string
	 */
	public function get_thumbnail_url(): string {
		return $this->thumbnail_url;
	}

	/**
	 * Get the author display name.
	 *
	 * @return string
	 */
	public function get_author_name(): string {
		return $this->author_name;
	}

	/**
	 * Get the author email.
	 *
	 * @return string
	 */
	public function get_author_email(): string {
		return $this->author_email;
	}

	/**
	 * Get the category names.
	 *
	 * @return string[]
	 */
	public function get_categories(): array {
		return $this->categories;
	}

	/**
	 * Get safe platform-generated avatar HTML.
	 *
	 * @return string
	 */
	public function get_author_avatar_html(): string {
		return $this->author_avatar_html;
	}

	/**
	 * Normalize category labels while preserving source order.
	 *
	 * @param array $categories Candidate category labels.
	 * @return string[]
	 */
	private function normalize_categories( array $categories ): array {
		$normalized = array();

		foreach ( $categories as $category ) {
			if ( ! is_string( $category ) ) {
				continue;
			}

			$category = trim( $category );

			if ( '' === $category || in_array( $category, $normalized, true ) ) {
				continue;
			}

			$normalized[] = $category;
		}

		return $normalized;
	}
}
