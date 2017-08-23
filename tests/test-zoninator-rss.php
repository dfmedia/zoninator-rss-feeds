<?php
/**
 * Class Test_Zoninator_RSS
 *
 * @package Zoninator_Rss_Feeds
 */
class Test_Zoninator_RSS extends WP_UnitTestCase {

	public $posts = [];
	public $zoninator_rss_feeds;
	public $zone_name = 'test_zone';

	/**
	 * Setup
	 */
	public function setUp() {
		parent::setUp();

		/**
		 * We need access to Zoninator and WP_Rewrite
		 */
		global $wp_rewrite, $zoninator;

		/**
		 * Insert a "test_zone" zone
		 */
		$test_zone = $zoninator->insert_zone( $this->zone_name );
		$test_zone = $test_zone['term_id'];

		/**
		 * Create some posts
		 */
		$this->posts = $this->factory->post->create_many( 5, [
			'post_type' => 'post',
		]);

		/**
		 * Add the posts to the $test_zone
		 */
		$zoninator->add_zone_posts( $test_zone, $this->posts );

		/**
		 * Instantiate the Zoninator_RSS_Feeds class
		 */
		$zoninator_rss_feeds = new Zoninator_RSS_Feeds();
		$zoninator_rss_feeds->init();
		$this->zoninator_rss_feeds = $zoninator_rss_feeds;

		/**
		 * Set permalink structure
		 */
		$wp_rewrite->set_permalink_structure( '/%year%/%monthnum%/%day%/%postname%/' );

		/**
		 * Flush the permalinks
		 */
		flush_rewrite_rules();

	}

	/**
	 * Tear Down
	 */
	public function tearDown() {
		parent::tearDown();
	}

	/**
	 * This is a hackable way of testing the feed to avoid "headers already sent" issues.
	 *
	 * @see: https://core.trac.wordpress.org/browser/trunk/tests/phpunit/tests/feed/rss2.php#L68
	 *
	 * @return string
	 * @throws Exception
	 */
	function _do_feed() {
		ob_start();
		global $post;
		try {
			@require(ABSPATH . 'wp-includes/feed-rss2.php');
			$out = ob_get_clean();
		} catch ( Exception $e ) {
			$out = ob_get_clean();
			throw($e);
		}
		return $out;
	}

	/**
	 * This makes sure our feed tweaks don't have any affect on the standard RSS Feeds
	 */
	public function test_standard_rss_feed_is_not_a_zoninator_feed() {
		$this->go_to( '/?feed=rss2' );
		global $wp_query;
		$is_zoninator_rss = $this->zoninator_rss_feeds->is_zoninator_rss( $wp_query );
		$this->assertFalse( $is_zoninator_rss );
	}

	/**
	 * This makes sure that the URL for a Zoninator Feed does properly validate as being a Zoninator RSS Feed
	 */
	public function test_is_zoninator_rss() {
		$this->go_to( '/feed/zoninator_zones?zone=' . $this->zone_name );
		global $wp_query;
		$is_zoninator_rss = $this->zoninator_rss_feeds->is_zoninator_rss( $wp_query );
		$this->assertNotFalse( $is_zoninator_rss );
	}

	/**
	 * This tests attributes of the RSS object returned by the feed
 	 */
	public function test_zoninator_rss_attributes() {

		$this->go_to( '/feed/zoninator_zones?zone=' . $this->zone_name );
		$feed = $this->_do_feed();
		$xml = xml_to_array( $feed );
		$rss = xml_find( $xml, 'rss' );

		$this->assertEquals( 1, count( $rss ) );
		$this->assertEquals( '2.0', $rss[0]['attributes']['version'] );
		$this->assertEquals( 'http://purl.org/rss/1.0/modules/content/', $rss[0]['attributes']['xmlns:content'] );
		$this->assertEquals( 'http://wellformedweb.org/CommentAPI/', $rss[0]['attributes']['xmlns:wfw'] );
		$this->assertEquals( 'http://purl.org/dc/elements/1.1/', $rss[0]['attributes']['xmlns:dc'] );
		$this->assertEquals( 1, count( $rss[0]['child'] ) );

	}

	/**
	 * This tests to make sure the items in the Zoninator RSS Feed are what we expect (the posts added to the zone)
	 */
	public function test_zoninator_rss_items() {

		$this->go_to( '/feed/zoninator_zones?zone=' . $this->zone_name );
		$feed = $this->_do_feed();
		$xml = xml_to_array( $feed );
		$items = xml_find( $xml, 'rss', 'channel', 'item' );

		// Make sure there are items returned
		$this->assertNotEmpty( $items );

		// Make sure the number of posts returned is only the number of posts in the zone
		$this->assertCount( count( $this->posts ), $items );

		// Loop through the RSS Items so we can make assertions about each one
		foreach ( $items as $key => $item ) {

			// ID
			$guid = xml_find( $items[$key]['child'], 'guid' );
			preg_match( '/\?p=(\d+)/', $guid[0]['content'], $matches );
			$rss_post_id = $matches[1];
			$this->assertNotEmpty( $rss_post_id );

			// Make sure the RSS ID matches the ID of the post in our array of generated posts
			$this->assertEquals( $rss_post_id, $this->posts[ $key ] );

			// Get a post object to compare against
			$post = get_post( $rss_post_id );

			// Make sure we're getting a valid post back when we fetch for one using the ID from the RSS Item
			$this->assertTrue( is_a( $post, 'WP_Post' ) );

			// Title
			$rss_title = xml_find( $items[$key]['child'], 'title' );
			$this->assertEquals( $post->post_title, $rss_title[0]['content'] );

			// Link
			$link = xml_find( $items[$key]['child'], 'link' );
			$this->assertEquals( get_permalink( $post ), $link[0]['content'] );

			// Comments Link
			$comments_link = xml_find( $items[$key]['child'], 'comments' );
			$this->assertEquals( get_permalink( $post ) . '#respond', $comments_link[0]['content'] );

			// Pubdate
			$pubdate = xml_find( $items[$key]['child'], 'pubDate' );
			$this->assertEquals( strtotime( $post->post_date_gmt ), strtotime( $pubdate[0]['content'] ) );

			// Creator
			$creator = xml_find( $items[$key]['child'], 'dc:creator' );
			$user = new WP_User( $post->post_author );
			$this->assertEquals( $user->display_name, $creator[0]['content'] );

			// Guid
			$guid = xml_find( $items[$key]['child'], 'guid' );
			$this->assertEquals( 'false', $guid[0]['attributes']['isPermaLink'] );
			$this->assertEquals( $post->guid, $guid[0]['content'] );

		}

	}

}
