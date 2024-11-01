<?php

/**
 * Fired during plugin activation
 *
 * @link       https://lucio.dev
 * @since      1.0.0
 *
 * @package    Woo_Earn_Sharing
 */

/**
 * Fired during plugin activation.
 *
 * Resaves Permalinks and adds default options.
 *
 * @since      1.0.0
 * @package    Woo_Earn_Sharing
 * @author     Lucio Dev <contact@lucio.dev>
 */
class Woo_Earn_Sharing_Activator {

	/**
	 * Plugin Activation
	 * Checks if WooCommerce is activated, resaves permalinks and adds default options.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
		if ( ! get_option( 'woo_earn_sharing_rewrite' ) ) {
			add_option( 'woo_earn_sharing_rewrite', true );
		}

		if ( ! get_option( 'woo_earn_sharing_options' ) ) {
			$options = array(
				'wooes_reward_percentage' => 10,
				'wooes_code_length'       => 9,
				'wooes_money_back'        => true,
				'wooes_code_alphanumeric' => true,
				'wooes_page_html'         => '<p>[wooes_user_balance]<br>[wooes_user_code]</p>',
			);
			add_option( 'woo_earn_sharing_options', $options );
		}
	}
}
