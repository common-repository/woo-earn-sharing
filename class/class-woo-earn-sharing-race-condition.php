<?php
namespace Woo_Earn_Sharing;

/**
 * Race-condition prevention class.
 *
 * @link       https://lucio.dev
 * @since      2.0.0
 *
 * @package    Woo_Earn_Sharing
 */

/**
 * Race-condition prevention class.
 *
 * Adds a temporary file to be used as a check before functions that could be exploited with race-condition.
 *
 * @since      1.0.0
 * @package    Woo_Earn_Sharing
 * @author     Lucio Dev <contact@lucio.dev>
 */
class Race_Condition {

	/**
	 * Creates the temporary file.
	 *
	 * @param string  $code     A code for the action, or the user's referral code.
	 * @param integer $order_id The order ID or 0 for none.
	 * @return integer
	 */
	public static function start( $code, $order_id = 0 ) {
		global $wp_filesystem;

		$code = Util::parse_code( $code ) . (int) $order_id;

		$file = WOOES_ROOT_DIR . '/tmp/.' . hash( 'sha256', $code );

		if ( $wp_filesystem->exists( $file ) && ( time() - filectime( $file ) ) < 15 ) {
			return false;
		}

		return $wp_filesystem->put_contents( $file, '1' );
	}

	/**
	 * Finishes the prevention, deleting the temporary file.
	 *
	 * @param string  $code     A code for the action, or the user's referral code.
	 * @param integer $order_id The order ID or 0 for none.
	 * @return boolean
	 */
	public static function finish( $code, $order_id ) {
		global $wp_filesystem;

		$code = Util::parse_code( $code ) . (int) $order_id;

		$file = WOOES_ROOT_DIR . '/tmp/.' . hash( 'sha256', $code );

		if ( ! $wp_filesystem->exists( $file ) ) {
			return false;
		}

		return $wp_filesystem->delete( $file );
	}

}
