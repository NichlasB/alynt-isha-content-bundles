<?php
/**
 * Compiled teacher-directory content filtering.
 *
 * @package Alynt_ISHA_Content_Bundles
 */

namespace Alynt\ISHAContentBundles\Services;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use DOMDocument;
use DOMElement;
use DOMXPath;

/**
 * Removes blocked teacher cards from a compiled Brizy Posts snapshot.
 *
 * @since 0.2.0
 */
final class TeacherDirectoryContentFilter {

	/**
	 * Filter teacher cards by their destination URLs.
	 *
	 * @param string   $content      Compiled page content.
	 * @param string[] $blocked_urls Teacher permalinks to remove.
	 * @return string
	 *
	 * @since 0.2.0
	 */
	public function filter( string $content, array $blocked_urls ): string {
		$blocked = array();
		foreach ( $blocked_urls as $url ) {
			$normalized = $this->normalize_url( (string) $url );
			if ( '' !== $normalized ) {
				$blocked[ $normalized ] = true;
			}
		}

		if ( '' === $content || empty( $blocked ) || false === strpos( $content, 'brz-posts__item' ) || ! class_exists( DOMDocument::class ) ) {
			return $content;
		}

		$previous_errors = libxml_use_internal_errors( true );
		$document        = new DOMDocument( '1.0', 'UTF-8' );
		$wrapper_id      = 'alynt-isha-teacher-directory-root';
		$loaded          = $document->loadHTML(
			'<?xml encoding="utf-8" ?><div id="' . $wrapper_id . '">' . $content . '</div>',
			LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
		);
		libxml_clear_errors();
		libxml_use_internal_errors( $previous_errors );

		if ( ! $loaded ) {
			return $content;
		}

		$xpath   = new DOMXPath( $document );
		$wrapper = $document->getElementById( $wrapper_id );
		if ( ! $wrapper instanceof DOMElement ) {
			return $content;
		}

		$cards = $xpath->query( ".//*[contains(concat(' ', normalize-space(@class), ' '), ' brz-posts__item ')]", $wrapper );
		if ( false === $cards ) {
			return $content;
		}

		$remove = array();
		foreach ( $cards as $card ) {
			foreach ( $card->getElementsByTagName( 'a' ) as $link ) {
				$href = $this->normalize_url( $link->getAttribute( 'href' ) );
				if ( '' !== $href && isset( $blocked[ $href ] ) ) {
					$remove[] = $card;
					break;
				}
			}
		}

		foreach ( $remove as $card ) {
			$parent_node = $card->parentNode; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- Native DOM API.
			if ( null !== $parent_node ) {
				$parent_node->removeChild( $card );
			}
		}

		$output      = '';
		$child_nodes = $wrapper->childNodes; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- Native DOM API.
		foreach ( $child_nodes as $child ) {
			$output .= $document->saveHTML( $child );
		}

		return '' !== $output ? $output : $content;
	}

	/**
	 * Normalize a teacher permalink for exact matching.
	 *
	 * @param string $url URL to normalize.
	 * @return string
	 */
	private function normalize_url( string $url ): string {
		$url   = html_entity_decode( trim( $url ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		$parts = wp_parse_url( $url );
		if ( false === $parts || empty( $parts['path'] ) ) {
			return '';
		}

		$path = '/' . ltrim( (string) $parts['path'], '/' );
		return '/' === $path ? $path : rtrim( $path, '/' );
	}
}
