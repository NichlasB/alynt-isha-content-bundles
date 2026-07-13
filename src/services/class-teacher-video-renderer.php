<?php
/**
 * Teacher-video shortcode renderer.
 *
 * @package Alynt_ISHA_Content_Bundles
 */

namespace Alynt\ISHAContentBundles\Services;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Alynt\ISHAContentBundles\Value\LibraryVideo;

/**
 * Preserves the existing teacher-video list structure with safe output.
 */
final class TeacherVideoRenderer {

	/**
	 * Render teacher videos.
	 *
	 * @param LibraryVideo[] $videos Video records.
	 * @return string
	 */
	public function render( array $videos ): string {
		if ( empty( $videos ) ) {
			return '<p style="text-align: center;">Videos are coming soon!</p>';
		}

		$html = '<ul class="teacher-videos">';
		foreach ( $videos as $video ) {
			if ( ! $video instanceof LibraryVideo ) {
				continue;
			}

			$url   = $this->escape( $video->get_watch_url() );
			$title = $this->escape( $video->get_title() );
			$html .= '<li class="teacher-video"><a href="' . $url . '">';
			$html .= '<div class="teacher-video-thumbnail">';
			if ( '' !== $video->get_thumbnail_url() ) {
				$html .= '<img src="' . $this->escape( $video->get_thumbnail_url() ) . '" alt="' . $title . '">';
			}
			$html .= '</div>';

			if ( ! empty( $video->get_categories() ) ) {
				$categories = array_map( array( $this, 'escape' ), $video->get_categories() );
				$html      .= '<div class="teacher-video-categories">' . implode( ', ', $categories ) . '</div>';
			}

			$html .= '<div class="teacher-video-details"><h3 class="teacher-video-title">';
			$html .= $title . '</h3></div></a></li>';
		}

		return $html . '</ul>';
	}

	/**
	 * Escape text and attribute content.
	 *
	 * @param string $value Raw value.
	 * @return string
	 */
	private function escape( string $value ): string {
		return htmlspecialchars( $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8' );
	}
}
