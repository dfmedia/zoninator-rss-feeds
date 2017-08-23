<?php
/**
 * Plugin Name:     Zoninator RSS Feeds
 * Plugin URI:      https://github.com/dfmedia/zoninator-rss-feeds
 * Description:     This plugin adds RSS Feeds for each Zoninator Zone. The feed can be accessed at "site.com/feed/zoninator_zones?zone={slug}"
 * Author:          Digital First Media, Jason Bahl
 * Author URI:      https://github.com/dfmedia
 * Text Domain:     zoninator-rss-feeds
 * Domain Path:     /languages
 * Version:         0.1.0
 *
 * @package         Zoninator_RSS_Feeds
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Allow the class to be registered by another plugin loaded earlier
 */
if ( ! class_exists( 'Zoninator_RSS_Feeds' ) ) :

	/**
	 * Zoninator RSS Feeds
	 */
	class Zoninator_RSS_Feeds {

		/**
		 * stores the taxonomy that Zoninator uses
		 * @var string $zone_taxonomy
		 */
		public $zone_taxonomy;

		/**
		 * Initialize the plugin
		 */
		public function init() {

			/**
			 * Only proceed with the plugin if Zoninator is active and available
			 */
			global $zoninator, $zoninator_rss_active;
			if ( ! $zoninator instanceof Zoninator ) {
				$zoninator_rss_active = false;
				return;
			}

			// Set the global to true
			$zoninator_rss_active = true;

			/**
			 * Set the taxonomy for later use
			 */
			$this->zone_taxonomy = $zoninator->zone_taxonomy;

			/**
			 * Add a feed for Zoninator
			 */
			add_feed( $this->zone_taxonomy, [ $this, 'do_feed' ] );
			add_filter( 'feed_content_type', [ $this, 'set_zoninator_feed_to_rss2' ], 10, 2 );

			/**
			 * Add a "zone" query var
			 */
			add_filter( 'query_vars', [ $this, 'add_query_vars' ], 10 );

			/**
			 * Filter before posts are retrieved to populate the RSS feed
			 */
			add_action( 'pre_get_posts', [ $this, 'prepare_feed' ] );

		}

		public function set_zoninator_feed_to_rss2( $content_type, $type ) {
			if ( $this->zone_taxonomy === $type ) {
				return feed_content_type( 'rss2' );
			}
			return $content_type;
		}

		/**
		 * Adds a query var to determine which Zone the feed should be for
		 * @param $query_vars
		 *
		 * @return array
		 */
		public function add_query_vars( $query_vars ) {
			$query_vars[] = 'zone';
			return $query_vars;
		}

		/**
		 * Conditional that determines if the current page is a Zoninator RSS Feed and that the query
		 * being affected is the main query.
		 *
		 * @param $wp_query
		 *
		 * @return mixed|int|bool
		 */
		public function is_zoninator_rss( $wp_query ) {
			$zone_slug = ! empty( $wp_query->get( 'zone' ) ) ? $wp_query->get( 'zone' ) : null;
			if ( $wp_query->is_main_query() && $this->zone_taxonomy === $wp_query->get( 'feed' ) && ! empty( $zone_slug ) ) {
				global $zoninator;
				$zone = $zoninator->get_zone( $zone_slug );
				if ( ! empty( $zone ) && ! empty( $zone->term_id ) ) {
					return absint( $zone->term_id );
				}
			}
			return false;
		}

		/**
		 * Alter the main WP_Query for the Zoninator RSS Feed
		 *
		 * @param WP_Query $query
		 */
		public function prepare_feed( \WP_Query $query ) {

			/**
			 * Make sure we only affect the Zoninator RSS Query
			 */
			if ( $this->is_zoninator_rss( $query ) ) {

				/**
				 * Get the zone for the zone being viewed
				 */
				$zone_slug = $query->get( 'zone' );
				$zone = ! empty( $zone_slug ) ? z_get_zone( $zone_slug ) : null;

				/**
				 * If the zone is a valid Zoninator zone
				 */
				if ( ! empty( $zone ) && $zone instanceof \WP_Term && ! empty( $zone->term_id ) && $this->zone_taxonomy === $zone->taxonomy ) {

					/**
					 * Access the zoninator class
					 */
					global $zoninator;

					/**
					 * Get the Zone Meta Key to query for zone posts
					 */
					$meta_key = $zoninator->get_zone_meta_key( $zone );

					/**
					 * If the meta key exists, use it to set the query
					 */
					if ( ! empty( $meta_key ) ) {
						$query->set( 'meta_key', $meta_key );
						$query->set( 'orderby', 'meta_value_num' );
						$query->set( 'order', 'ASC' );
						$query->set( 'ignore_sticky_posts', true );
						$query->set( 'ignore_sticky_posts', [ 'post' ] );
						$query->set( 'posts_per_page', 25 );
					}
				}
			}
		}

		/**
		 * Output the RSS feed using the Core RSS 2.0 Feed template
		 *
		 */
		public function do_feed() {
			global $wp_query;
			if ( $this->is_zoninator_rss( $wp_query ) ) {
				require( ABSPATH . 'wp-includes/feed-rss2.php');
			}
		}

		/**
		 * This ensures that we don't output the JSON feed for zoninator for the RSS feed. Since
		 * the JSON feed outputs always if `is_feed()` is true, and if it's a zoninator feed, the
		 * JSON was being appended to the RSS feed causing issues.
		 *
		 * This ensures the JSON is prevented from being output if we're on the RSS Feed
		 */
		public function maybe_stop_zoninator_json_feed() {

			/**
			 * Access the $wp_query and $zoninator
			 */
			global $wp_query, $zoninator;

			/**
			 * If the feed is Zoninator RSS, remove the Zoninator JSON feed so it doesn't
			 * get appended to the RSS and cause problems
			 */
			if ( $this->is_zoninator_rss( $wp_query ) ) {
				remove_action( 'template_redirect', [ $zoninator, 'do_zoninator_feeds' ] );
			}

		}
	}

endif;

/**
 * Initialize the plugin
 */
add_action( 'init', function() {
	$zoninator_rss_feed = new Zoninator_RSS_Feeds();
	$zoninator_rss_feed->init();
} );
