<?php
/**
 * Site definition tests.
 *
 * @package Alynt_ISHA_Content_Bundles
 */

namespace Alynt\ISHAContentBundles\Tests;

use Alynt\ISHAContentBundles\BundleMetadata;
use Alynt\ISHAContentBundles\SiteDefinition;
use PHPUnit\Framework\TestCase;

/**
 * Verifies the approved non-sensitive launch baseline embedded in the plugin.
 */
final class SiteDefinitionTest extends TestCase {

	/**
	 * Qualifying teacher totals preserve the verified T3 baseline.
	 *
	 * @return void
	 */
	public function test_qualifying_teacher_runtime_totals() {
		$runtimes = SiteDefinition::video_runtimes();
		$groups   = array(
			array( 705, 718, 722, 727, 733, 739 ),
			array( 522, 524, 531, 536, 1157 ),
			array( 1120, 1139 ),
		);

		$this->assertEqualsWithDelta( 5803.190699, $this->sum( $groups[0], $runtimes ), 0.000001 );
		$this->assertEqualsWithDelta( 5622.533334, $this->sum( $groups[1], $runtimes ), 0.000001 );
		$this->assertEqualsWithDelta( 3560.658570, $this->sum( $groups[2], $runtimes ), 0.000001 );
		$this->assertGreaterThanOrEqual( BundleMetadata::QUALIFYING_SECONDS, $this->sum( $groups[2], $runtimes ) );
	}

	/**
	 * Nonqualifying teacher totals remain below the approved grace cutoff.
	 *
	 * @return void
	 */
	public function test_nonqualifying_teacher_runtime_totals() {
		$runtimes = SiteDefinition::video_runtimes();

		$this->assertLessThan( BundleMetadata::QUALIFYING_SECONDS, $this->sum( array( 500, 507, 510 ), $runtimes ) );
		$this->assertLessThan( BundleMetadata::QUALIFYING_SECONDS, $this->sum( array( 514 ), $runtimes ) );
	}

	/**
	 * Sum selected runtime records.
	 *
	 * @param int[]            $video_ids Video IDs.
	 * @param array<int,float> $runtimes  Runtime map.
	 * @return float
	 */
	private function sum( array $video_ids, array $runtimes ): float {
		$total = 0.0;
		foreach ( $video_ids as $video_id ) {
			$total += $runtimes[ $video_id ];
		}

		return $total;
	}
}
