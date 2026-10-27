<?php
/**
 * Uninstall routine: remove the plugin's options.
 *
 * @package Hosted_JFT
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

$hosted_jft_options = [
	'jft_timezone',
	'jft_custom_field',
	'jft_page_url',
	'jft_more_text',
];

foreach ( $hosted_jft_options as $hosted_jft_option ) {
	delete_option( $hosted_jft_option );
}
