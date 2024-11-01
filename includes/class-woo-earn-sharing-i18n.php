<?php

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

class Woo_Earn_Sharing_i18n {
	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public static function load_plugin_textdomain() {
		load_plugin_textdomain(
			'woo-earn-sharing',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);

	}
}
add_action( 'plugins_loaded', array( 'Woo_Earn_Sharing_i18n', 'load_plugin_textdomain' ) );
