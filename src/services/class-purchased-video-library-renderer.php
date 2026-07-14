<?php
/**
 * Purchased-video library renderer.
 *
 * @package Alynt_ISHA_Content_Bundles
 */

namespace Alynt\ISHAContentBundles\Services;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Alynt\ISHAContentBundles\Value\LibraryVideo;

/**
 * Renders the legacy purchased-video card structure from normalized data.
 *
 * @since 0.2.0
 */
final class PurchasedVideoLibraryRenderer {

	/**
	 * Render the purchased-video library.
	 *
	 * Avatar markup is emitted as provided because the WordPress adapter will
	 * supply safe platform-generated get_avatar() HTML.
	 *
	 * @param array $videos       Candidate library video records.
	 * @param bool  $is_logged_in Whether the current visitor is logged in.
	 * @return string
	 *
	 * @since 0.2.0
	 */
	public function render( array $videos, bool $is_logged_in ): string {
		if ( ! $is_logged_in ) {
			return '<p class="alynt-isha-content-bundles__notice">' . $this->escape_html( __( 'Please log in to view your purchased videos.', 'alynt-isha-content-bundles' ) ) . '</p>';
		}

		$videos = $this->normalize_videos( $videos );

		if ( empty( $videos ) ) {
			return '<p class="alynt-isha-content-bundles__notice">' . $this->escape_html( __( 'You have not purchased any videos yet.', 'alynt-isha-content-bundles' ) ) . '</p>';
		}

		$html = '<ul class="purchased-videos alynt-isha-content-bundles__purchased-videos">';

		foreach ( $videos as $video ) {
			$html .= $this->render_video( $video );
		}

		return $html . '</ul>';
	}

	/**
	 * Normalize and deduplicate renderer input.
	 *
	 * @param array $videos Candidate library video records.
	 * @return LibraryVideo[]
	 */
	private function normalize_videos( array $videos ): array {
		$normalized = array();

		foreach ( $videos as $video ) {
			if ( ! $video instanceof LibraryVideo ) {
				continue;
			}

			$normalized[ $video->get_id() ] = $video;
		}

		return array_values( $normalized );
	}

	/**
	 * Render one video card.
	 *
	 * @param LibraryVideo $video Library video.
	 * @return string
	 */
	private function render_video( LibraryVideo $video ): string {
		$title         = $this->escape_html( $video->get_title() );
		$thumbnail_url = $this->escape_url( $video->get_thumbnail_url() );
		$watch_url     = $this->escape_url( $video->get_watch_url() );
		$html          = '<li class="video-item alynt-isha-content-bundles__video-item">';

		if ( '' !== $thumbnail_url ) {
			$html .= '<img class="video-thumbnail" src="' . $thumbnail_url . '" alt="' . $title . '">';
		}

		if ( ! empty( $video->get_categories() ) ) {
			$categories = array_map( array( $this, 'escape_html' ), $video->get_categories() );
			$html      .= '<div class="video-categories"><span>' . implode( ' | ', $categories ) . '</span></div>';
		}

		$html .= '<div class="video-item-details">';
		$html .= '<ul class="video-author-info">';
		$html .= '<li>' . $video->get_author_avatar_html() . '</li>';
		$html .= '<li>' . $this->escape_html( $video->get_author_name() ) . '</li>';
		$html .= '</ul>';
		$html .= '<h3>' . $title . '</h3>';
		/* translators: %s: Video title. */
		$watch_label = sprintf( __( 'Watch %s', 'alynt-isha-content-bundles' ), $video->get_title() );
		$html       .= '<a href="' . $watch_url . '" aria-label="' . $this->escape_html( $watch_label ) . '">';
		$html       .= $this->escape_html( __( 'Watch', 'alynt-isha-content-bundles' ) ) . '</a>';
		$html       .= '</div>';

		return $html . '</li>';
	}

	/**
	 * Escape HTML text or attribute content.
	 *
	 * @param string $value Raw value.
	 * @return string
	 */
	private function escape_html( string $value ): string {
		return htmlspecialchars( $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8' );
	}

	/**
	 * Reject unsafe schemes and escape a URL for an HTML attribute.
	 *
	 * @param string $url Raw URL.
	 * @return string
	 */
	private function escape_url( string $url ): string {
		$url = trim( $url );

		if ( '' === $url ) {
			return '';
		}

		if ( 1 === preg_match( '/^[a-z][a-z0-9+.-]*:/i', $url ) && 1 !== preg_match( '#^https?://#i', $url ) ) {
			return '';
		}

		return $this->escape_html( $url );
	}
}
