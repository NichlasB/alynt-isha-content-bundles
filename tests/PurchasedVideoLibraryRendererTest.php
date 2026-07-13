<?php
/**
 * Purchased-video library renderer tests.
 *
 * @package Alynt_ISHA_Content_Bundles
 */

namespace Alynt\ISHAContentBundles\Tests;

use Alynt\ISHAContentBundles\Services\PurchasedVideoLibraryRenderer;
use Alynt\ISHAContentBundles\Value\LibraryVideo;
use PHPUnit\Framework\TestCase;

/**
 * Verifies the legacy account-library presentation contract.
 */
final class PurchasedVideoLibraryRendererTest extends TestCase {

	/** @return void */
	public function test_legacy_card_structure_and_fields_are_rendered() {
		$video = new LibraryVideo(
			10,
			'Herbal Foundations',
			'https://example.test/video/herbal-foundations/',
			'https://example.test/image.jpg',
			'Raw Chef Gail',
			'gail@example.test',
			array( 'Nutrition', 'Raw Food' ),
			'<img class="avatar avatar-32" src="https://example.test/avatar.jpg" alt="">'
		);
		$html = ( new PurchasedVideoLibraryRenderer() )->render( array( $video ), true );

		$this->assertStringStartsWith( '<ul class="purchased-videos">', $html );
		$this->assertStringContainsString( '<li class="video-item">', $html );
		$this->assertStringContainsString( '<img class="video-thumbnail" src="https://example.test/image.jpg" alt="Herbal Foundations">', $html );
		$this->assertStringContainsString( '<div class="video-categories"><span>Nutrition | Raw Food</span></div>', $html );
		$this->assertStringContainsString( '<ul class="video-author-info">', $html );
		$this->assertStringContainsString( 'avatar avatar-32', $html );
		$this->assertStringContainsString( '<li>Raw Chef Gail</li>', $html );
		$this->assertStringContainsString( '<h3>Herbal Foundations</h3>', $html );
		$this->assertStringContainsString( '<a href="https://example.test/video/herbal-foundations/">Watch</a>', $html );
		$this->assertStringEndsWith( '</ul>', $html );
	}

	/** @return void */
	public function test_existing_state_messages_are_preserved() {
		$renderer = new PurchasedVideoLibraryRenderer();

		$this->assertSame( '<p>Please log in to view your purchased videos.</p>', $renderer->render( array(), false ) );
		$this->assertSame( '<p>You have not purchased any videos yet.</p>', $renderer->render( array(), true ) );
	}

	/** @return void */
	public function test_renderer_ignores_invalid_records_and_deduplicates() {
		$first  = $this->create_video( 10, 'First title' );
		$second = $this->create_video( 10, 'Replacement title' );
		$html   = ( new PurchasedVideoLibraryRenderer() )->render( array( $first, 'invalid', $second ), true );

		$this->assertSame( 1, substr_count( $html, '<li class="video-item">' ) );
		$this->assertStringNotContainsString( 'First title', $html );
		$this->assertStringContainsString( 'Replacement title', $html );
	}

	/** @return void */
	public function test_dynamic_fields_are_safely_rendered() {
		$video = new LibraryVideo(
			10,
			'<script>alert("title")</script>',
			'javascript:alert(1)',
			'https://example.test/image.jpg?size=1&crop=yes',
			'Teacher & Guide',
			'teacher@example.test',
			array( 'Health < Wellness', 'Health < Wellness', '', 20 )
		);
		$html = ( new PurchasedVideoLibraryRenderer() )->render( array( $video ), true );

		$this->assertStringNotContainsString( '<script>', $html );
		$this->assertStringContainsString( '&lt;script&gt;alert(&quot;title&quot;)&lt;/script&gt;', $html );
		$this->assertStringContainsString( 'src="https://example.test/image.jpg?size=1&amp;crop=yes"', $html );
		$this->assertStringContainsString( '<li>Teacher &amp; Guide</li>', $html );
		$this->assertSame( 1, substr_count( $html, 'Health &lt; Wellness' ) );
		$this->assertStringContainsString( '<a href="">Watch</a>', $html );
		$this->assertStringNotContainsString( 'javascript:', $html );
	}

	/**
	 * @param int    $video_id Video ID.
	 * @param string $title    Video title.
	 * @return LibraryVideo
	 */
	private function create_video( int $video_id, string $title ): LibraryVideo {
		return new LibraryVideo(
			$video_id,
			$title,
			'https://example.test/video/' . $video_id,
			'',
			'Teacher',
			'teacher@example.test',
			array()
		);
	}
}
