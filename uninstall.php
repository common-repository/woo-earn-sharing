<?php
/**
 * Removes the plugin's settings.
 *
 * @link       https://lucio.dev
 * @since      1.0.0
 *
 * @package    Woo_Earn_Sharing
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'woo_earn_sharing_rewrite' );
delete_option( 'woo_earn_sharing_options' );
