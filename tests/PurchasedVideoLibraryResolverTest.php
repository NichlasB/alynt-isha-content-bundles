<?php
/**
 * Purchased-video library resolver tests.
 *
 * @package Alynt_ISHA_Content_Bundles
 */

namespace Alynt\ISHAContentBundles\Tests;

use Alynt\ISHAContentBundles\Services\EntitlementResolver;
use Alynt\ISHAContentBundles\Services\PurchasedVideoLibraryResolver;
use Alynt\ISHAContentBundles\Tests\Support\FakeContentMapProvider;
use Alynt\ISHAContentBundles\Tests\Support\FakePurchaseProvider;
use Alynt\ISHAContentBundles\Tests\Support\FakeUserAccessProvider;
use Alynt\ISHAContentBundles\Tests\Support\FakeVideoLibraryProvider;
use Alynt\ISHAContentBundles\Value\LibraryVideo;
use Alynt\ISHAContentBundles\Value\Purchase;
use PHPUnit\Framework\TestCase;

/**
 * Verifies entitlement-backed account-library data resolution.
 */
final class PurchasedVideoLibraryResolverTest extends TestCase {

	/**
	 * Bundle buyers receive every video in the explicit manifest.
	 *
	 * @return void
	 */
	public function test_bundle_buyer_receives_every_manifest_video() {
		$resolver = $this->create_resolver(
			array( new Purchase( 200, 'completed' ) ),
			array(),
			array( 200 => array( 30, 10, 20 ) ),
			$this->create_video_map( array( 10, 20, 30 ) )
		);

		$this->assertSame( array( 10, 20, 30 ), $this->get_video_ids( $resolver->resolve_for_user( 1 ) ) );
	}

	/**
	 * Legacy buyers retain the video mapped to their purchased product.
	 *
	 * @return void
	 */
	public function test_legacy_buyer_retains_individual_video() {
		$resolver = $this->create_resolver(
			array( new Purchase( 100, 'completed' ) ),
			array( 100 => 10 ),
			array(),
			$this->create_video_map( array( 10, 20 ) )
		);

		$this->assertSame( array( 10 ), $this->get_video_ids( $resolver->resolve_for_user( 1 ) ) );
	}

	/**
	 * Overlapping legacy and bundle access produces no duplicate cards.
	 *
	 * @return void
	 */
	public function test_overlapping_access_is_deduplicated() {
		$resolver = $this->create_resolver(
			array( new Purchase( 100, 'completed' ), new Purchase( 200, 'completed' ) ),
			array( 100 => 10 ),
			array( 200 => array( 10, 20, 20 ) ),
			$this->create_video_map( array( 10, 20 ) )
		);

		$this->assertSame( array( 10, 20 ), $this->get_video_ids( $resolver->resolve_for_user( 1 ) ) );
	}

	/**
	 * Presentation fields needed by the legacy cards remain intact.
	 *
	 * @return void
	 */
	public function test_presentation_fields_are_preserved() {
		$video = new LibraryVideo(
			10,
			'Herbal Foundations',
			'https://example.test/video/herbal-foundations/',
			'https://example.test/image.jpg',
			'Raw Chef Gail',
			'gail@example.test',
			array( 'Nutrition', 'Raw Food' ),
			'<img class="avatar" src="https://example.test/avatar.jpg" alt="">'
		);
		$resolver = $this->create_resolver(
			array( new Purchase( 100, 'completed' ) ),
			array( 100 => 10 ),
			array(),
			array( 10 => $video )
		);

		$resolved = $resolver->resolve_for_user( 1 );

		$this->assertCount( 1, $resolved );
		$this->assertSame( 'Herbal Foundations', $resolved[0]->get_title() );
		$this->assertSame( 'https://example.test/video/herbal-foundations/', $resolved[0]->get_watch_url() );
		$this->assertSame( 'https://example.test/image.jpg', $resolved[0]->get_thumbnail_url() );
		$this->assertSame( 'Raw Chef Gail', $resolved[0]->get_author_name() );
		$this->assertSame( 'gail@example.test', $resolved[0]->get_author_email() );
		$this->assertSame( array( 'Nutrition', 'Raw Food' ), $resolved[0]->get_categories() );
		$this->assertStringContainsString( 'avatar', $resolved[0]->get_author_avatar_html() );
	}

	/**
	 * Missing and mismatched provider records are not substituted into access.
	 *
	 * @return void
	 */
	public function test_missing_and_mismatched_records_are_ignored() {
		$resolver = $this->create_resolver(
			array( new Purchase( 200, 'completed' ) ),
			array(),
			array( 200 => array( 10, 20, 30 ) ),
			array(
				10 => $this->create_video( 10 ),
				20 => $this->create_video( 99 ),
			)
		);

		$this->assertSame( array( 10 ), $this->get_video_ids( $resolver->resolve_for_user( 1 ) ) );
	}

	/**
	 * Anonymous or invalid users receive an empty library.
	 *
	 * @return void
	 */
	public function test_invalid_user_receives_empty_library() {
		$resolver = $this->create_resolver(
			array( new Purchase( 100, 'completed' ) ),
			array( 100 => 10 ),
			array(),
			$this->create_video_map( array( 10 ) )
		);

		$this->assertSame( array(), $resolver->resolve_for_user( 0 ) );
	}

	/**
	 * Create the system under test.
	 *
	 * @param Purchase[]              $purchases  User purchases.
	 * @param array<int,int>          $legacy_map Legacy product mappings.
	 * @param array<int,array>        $bundle_map Bundle product mappings.
	 * @param array<int,LibraryVideo> $videos     Library records.
	 * @return PurchasedVideoLibraryResolver
	 */
	private function create_resolver(
		array $purchases,
		array $legacy_map,
		array $bundle_map,
		array $videos
	): PurchasedVideoLibraryResolver {
		$entitlements = new EntitlementResolver(
			new FakeUserAccessProvider(),
			new FakePurchaseProvider( array( 1 => $purchases ) ),
			new FakeContentMapProvider( $legacy_map, $bundle_map )
		);

		return new PurchasedVideoLibraryResolver( $entitlements, new FakeVideoLibraryProvider( $videos ) );
	}

	/**
	 * Create library records keyed by ID.
	 *
	 * @param int[] $video_ids Video IDs.
	 * @return array<int,LibraryVideo>
	 */
	private function create_video_map( array $video_ids ): array {
		$videos = array();

		foreach ( $video_ids as $video_id ) {
			$videos[ $video_id ] = $this->create_video( $video_id );
		}

		return $videos;
	}

	/**
	 * Create a minimal library record.
	 *
	 * @param int $video_id Video ID.
	 * @return LibraryVideo
	 */
	private function create_video( int $video_id ): LibraryVideo {
		return new LibraryVideo(
			$video_id,
			'Video ' . $video_id,
			'https://example.test/video/' . $video_id,
			'',
			'Teacher',
			'teacher@example.test',
			array()
		);
	}

	/**
	 * Extract IDs from resolved records.
	 *
	 * @param LibraryVideo[] $videos Library records.
	 * @return int[]
	 */
	private function get_video_ids( array $videos ): array {
		return array_map(
			static function ( LibraryVideo $video ): int {
				return $video->get_id();
			},
			$videos
		);
	}
}
