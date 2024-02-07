<?php
/**
 * WP_Test_Sitemap_Index
 *
 * @package Metro_Sitemap/unit_tests
 */

namespace Automattic\MSM_Sitemap\Tests;

use Metro_Sitemap;

/**
 * Unit Tests to validate indexes are properly generated by Metro_Sitemap
 *
 * @author bcampeau
 */
class WP_Test_Sitemap_Index extends \WP_UnitTestCase {

	/**
	 * Create posts across a number of years
	 *
	 * @var int
	 */
	private $num_years_data = 3;

	/**
	 * Base Test Class Instance
	 *
	 * @var MSM_SIteMap_Test
	 */
	private $test_base;

	/**
	 * Generate posts and build initial sitemaps
	 */
	function setup(): void {
		_delete_all_posts();

		$this->test_base = new MSM_SiteMap_Test();

		// Add a post for each day in the last x years.
		$dates = array();
		$date = time();
		for ( $i = 0; $i < $this->num_years_data; $i++ ) {
			// Add a post for x years ago.
			$dates[] = date( 'Y', $date ) . '-' . date( 'm', $date ) . '-' . date( 'd', $date ) . ' 00:00:00';
			$date = strtotime( '-1 year', $date );
		}

		$this->test_base->create_dummy_posts( $dates );

		$this->assertCount( $this->num_years_data, $this->test_base->posts );
		$this->test_base->build_sitemaps();
	}

	/**
	 * Remove created posts, sitemaps and options
	 */
	function teardown(): void {
		$this->test_base->posts = array();
		$sitemaps = get_posts( array(
			'post_type' => Metro_Sitemap::SITEMAP_CPT,
			'fields' => 'ids',
			'posts_per_page' => -1,
		) );
		update_option( 'msm_sitemap_indexed_url_count' , 0 );
		array_map( 'wp_delete_post', array_merge( $this->test_base->posts_created, $sitemaps ) );
	}

	/**
	 * Test that robots.txt has a single sitemap index when sitemaps by year are disabled
	 */
	function test_single_sitemap_index() {
		// Turn on indexing by year
		Metro_Sitemap::$index_by_year = false;

		// Check that we have a single instance of sitemap.xml in robots.txt
		// We can't actually use the core function since it outputs headers,
		// but we only care about our stuff output to a public blog.
		preg_match_all( '|sitemap\.xml|', apply_filters( 'robots_txt', '', true ), $matches );

		// Check that we've indexed the proper total number of URLs.
		$this->assertEquals( 1, count( $matches[0] ) );
	}

	/**
	 * Test that robots.txt has sitemap indexes for all years when sitemaps by year are enabled
	 */
	function test_sitemap_index_by_year() {
		// Turn on indexing by year
		Metro_Sitemap::$index_by_year = true;

		// Check that we have a single instance of sitemap.xml in robots.txt
		// We can't actually use the core function since it outputs headers,
		// but we only care about our stuff output to a public blog.
		preg_match_all( '|sitemap-([0-9]{4})\.xml|', apply_filters( 'robots_txt', '', true ), $matches );

		// Check that we've indexed the proper total number of URLs.
		$this->assertEquals( 3, count( $matches[0] ) );
	}
}

