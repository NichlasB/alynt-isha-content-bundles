<?php
/**
 * Teacher directory content filter tests.
 *
 * @package Alynt_ISHA_Content_Bundles
 */

namespace Alynt\ISHAContentBundles\Tests;

use Alynt\ISHAContentBundles\Services\TeacherDirectoryContentFilter;
use PHPUnit\Framework\TestCase;

/**
 * Verifies compiled Brizy cards follow the central eligibility outcome.
 */
final class TeacherDirectoryContentFilterTest extends TestCase {

	/**
	 * Blocked cards are removed while qualifying and unrelated markup remains.
	 *
	 * @return void
	 */
	public function test_filter_removes_only_cards_with_blocked_teacher_links() {
		$content = '<div class="brz-posts__wrapper">'
			. '<div class="brz-posts__item"><p>Marie</p><a href="https://example.test/teacher/marie/">Get to Know</a></div>'
			. '<div class="brz-posts__item extra"><p>Matt</p><a href="https://example.test/teacher/matt/?ref=grid">Get to Know</a></div>'
			. '<div class="other">Footer</div>'
			. '</div>';

		$filtered = ( new TeacherDirectoryContentFilter() )->filter(
			$content,
			array( 'https://example.test/teacher/matt/' )
		);

		$this->assertStringContainsString( 'Marie', $filtered );
		$this->assertStringNotContainsString( 'Matt', $filtered );
		$this->assertStringContainsString( 'Footer', $filtered );
	}

	/**
	 * Unrelated content is returned byte-for-byte unchanged.
	 *
	 * @return void
	 */
	public function test_filter_leaves_unrelated_content_unchanged() {
		$content = '<p>Unrelated content</p>';

		$this->assertSame(
			$content,
			( new TeacherDirectoryContentFilter() )->filter( $content, array( 'https://example.test/teacher/matt/' ) )
		);
	}
}
