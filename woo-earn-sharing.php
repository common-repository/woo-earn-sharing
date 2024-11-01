<?php
/**
 * The plugin main file
 *
 * The plugin creates referral codes for users, that can be used when a new order is made, given the code's owner a defined comission.
 *
 * @link              https://lucio.dev
 * @since             1.0.0
 * @package           Woo_Earn_Sharing
 *
 * Plugin Name:       Woo Earn Sharing
 * Plugin URI:        https://lucio.dev/Woo-Earn-Sharing
 * Description:       Let your users share their own codes and earn discounts.
 * Version:           2.0.0
 * Author:            Lucio Dev
 * Author URI:        https://lucio.dev
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       woo-earn-sharing
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

define( 'WOOES_VERSION', '2.0.0' );
define( 'WOOES_ROOT_DIR', __DIR__ );
define( 'WOOES_ROOT_URL', plugin_dir_url( __FILE__ ) );
define( 'WOOES_ROOT_FILE', plugin_basename( __FILE__ ) );

/**
 * The activation hook function.
 *
 * @return void
 */
function woo_earn_sharing_activate() {
	require_once WOOES_ROOT_DIR . '/includes/class-woo-earn-sharing-activator.php';
	Woo_Earn_Sharing_Activator::activate();
}
register_activation_hook( __FILE__, 'woo_earn_sharing_activate' );

/**
 * Begins execution of the plugin.
 *
 * @since    1.0.0
 */
function run_woo_earn_sharing() {

	require_once WOOES_ROOT_DIR . '/includes/class-woo-earn-sharing-i18n.php';
	require_once WOOES_ROOT_DIR . '/class/class-woo-earn-sharing-util.php';
	require_once WOOES_ROOT_DIR . '/class/class-woo-earn-sharing-race-condition.php';
	require_once WOOES_ROOT_DIR . '/class/class-woo-earn-sharing-admin.php';
	require_once WOOES_ROOT_DIR . '/class/class-woo-earn-sharing-settings.php';
	require_once WOOES_ROOT_DIR . '/class/class-woo-earn-sharing-common.php';

	new Woo_Earn_Sharing_i18n();
	new Woo_Earn_Sharing\Admin();
	new Woo_Earn_Sharing\Settings();
	new Woo_Earn_Sharing\Common();
}
run_woo_earn_sharing();
