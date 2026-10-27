<?php
/**
 * PHPUnit bootstrap file for Hosted JFT plugin tests.
 *
 * Uses wp-phpunit/wp-phpunit (installed via Composer) as the test library.
 * WordPress core must be downloaded to WP_CORE_DIR (default: /tmp/wordpress).
 */

// Point wp-phpunit at our config file.
putenv( 'WP_PHPUNIT__TESTS_CONFIG=' . __DIR__ . '/wp-tests-config.php' );

$hosted_jft_tests_dir = dirname( __DIR__ ) . '/vendor/wp-phpunit/wp-phpunit';

if ( ! file_exists( "{$hosted_jft_tests_dir}/includes/functions.php" ) ) {
	echo "Could not find wp-phpunit. Run: composer install" . PHP_EOL;
	exit( 1 );
}

require_once "{$hosted_jft_tests_dir}/includes/functions.php";

/**
 * Load the plugin being tested.
 */
function hosted_jft_tests_manually_load_plugin() {
	require dirname( __DIR__ ) . '/hosted-jft-plugin.php';
}
tests_add_filter( 'muplugins_loaded', 'hosted_jft_tests_manually_load_plugin' );

require "{$hosted_jft_tests_dir}/includes/bootstrap.php";
