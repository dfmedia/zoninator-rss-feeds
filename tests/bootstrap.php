<?php
/**
 * PHPUnit bootstrap file
 *
 * @package Zoninator_Rss_Feeds
 */

$_tests_dir = getenv( 'WP_TESTS_DIR' );
if ( ! $_tests_dir ) {
	$_tests_dir = '/tmp/wordpress-tests-lib';
}

// Give access to tests_add_filter() function.
require_once $_tests_dir . '/includes/functions.php';

/**
 * Manually load the plugin being tested.
 */
function _manually_load_plugin() {
	/**
	 * Require the Zoninator plugin in addition to the RSS Feeds plugin. This requires Zoninator to live in the same
	 * directory as this plugin for testing.
	 *
	 * For example, both plugins should live at /plugins/zoninator/ and /plugins/zoninator-rss-feeds/
	 */
	require_once dirname( dirname( __FILE__, 2 ) ) . '/zoninator/zoninator.php';
	require dirname( dirname( __FILE__ ) ) . '/zoninator-rss-feeds.php';
}
tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

// Start up the WP testing environment.
require $_tests_dir . '/includes/bootstrap.php';
