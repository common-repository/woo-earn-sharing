<?php
namespace Woo_Earn_Sharing;

/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://lucio.dev
 * @since      1.0.0
 *
 * @package    Woo_Earn_Sharing
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Woo_Earn_Sharing
 * @author     Lucio Dev <contact@lucio.dev>
 */
class Common {

	/**
	 * Registers actions and filters.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'flush_rewrite_rules_maybe' ), 20 );
		add_action( 'init', array( $this, 'tab_support_endpoint' ) );
		add_action( 'query_vars', array( $this, 'tab_query_vars' ), 0 );
		add_filter( 'woocommerce_account_menu_items', array( $this, 'add_my_account_tab' ) );
		add_action( 'woocommerce_account_my-referrals_endpoint', array( $this, 'my_referrals_content' ) );
		add_action( 'user_register', array( $this, 'new_user' ), 10, 1 );
		add_action( 'woocommerce_after_order_notes', array( $this, 'checkout_field' ) );
		add_action( 'woocommerce_checkout_process', array( $this, 'checkout_field_process' ) );
		add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'checkout_field_update_order_meta' ) );
		add_action( 'woocommerce_order_status_completed', array( $this, 'woocommerce_order_status_completed' ) );
		add_action( 'woocommerce_cart_calculate_fees', array( $this, 'discount_balance' ), 25, 1 );
		add_action( 'woocommerce_thankyou', array( $this, 'checkout_add_meta' ) );
		add_shortcode( 'wooes_user_balance', array( $this, 'user_balance_shortcode' ) );
		add_shortcode( 'wooes_user_code', array( $this, 'user_code_shortcode' ) );

		add_action( 'woocommerce_order_status_cancelled', array( $this, 'woocommerce_order_status_cancelled' ) );
		add_action( 'woocommerce_order_status_failed', array( $this, 'woocommerce_order_status_cancelled' ) );
		add_action( 'woocommerce_order_status_refunded', array( $this, 'woocommerce_order_status_cancelled' ) );
	}

	/**
	 * Flushes the permalinks rules, if needed.
	 *
	 * @return void
	 */
	public function flush_rewrite_rules_maybe() {
		if ( get_option( 'woo_earn_sharing_rewrite' ) ) {
			flush_rewrite_rules();
			delete_option( 'woo_earn_sharing_rewrite' );
		}
	}

	/**
	 * Adds the new "my-referrals" tab
	 *
	 * @return void
	 */
	public function tab_support_endpoint() {
		add_rewrite_endpoint( 'my-referrals', EP_ROOT | EP_PAGES );
	}

	/**
	 * Adds the new "my-referrals" query
	 *
	 * @param array $vars Array of previous set query vars.
	 *
	 * @return array
	 */
	public function tab_query_vars( $vars ) {
		$vars[] = 'my-referrals';
		return $vars;
	}

	/**
	 * Adds the new my-referrals tab in the My Account page.
	 *
	 * @param array $items Array of previous set items.
	 * @return array
	 */
	public function add_my_account_tab( $items ) {
			$logout = $items['customer-logout'];
			unset( $items['customer-logout'] );
			$items['my-referrals']    = __( 'Referrals', 'woo-earn-sharing' );
			$items['customer-logout'] = $logout;
			return $items;
	}

	/**
	 * Echoes the content of the My Referrals tab.
	 *
	 * @return void
	 */
	public function my_referrals_content() {
		ob_start();
		require_once WOOES_ROOT_DIR . '/templates/my-account.php';
		$output = ob_get_clean();
		echo wp_kses_post( $output );
	}

	/**
	 * Adds a balance and referral code for the just created user.
	 *
	 * @param integer $user_id The new user ID.
	 * @return void
	 */
	public function new_user( $user_id ) {
		update_user_meta( $user_id, 'wooes_code', Util::generate_new_referral_code() );
		update_user_meta( $user_id, 'wooes_balance', 0 );
	}

	/**
	 * Called when the order has the status changed to completed.
	 *
	 * @param integer $order_id The order ID.
	 *
	 * @see Util::reward_user_by_code
	 *
	 * @return void
	 */
	public function woocommerce_order_status_completed( $order_id ) {
		$code = get_post_meta( $order_id, 'wooes_referral_code', true );
		if ( ! empty( $code ) ) {
			Util::reward_user_by_code( $code, $order_id );
		}
	}

	/**
	 * Called when the order has the status changed to cancelled.
	 *
	 * @param integer $order_id The order ID.
	 *
	 * @see Util::give_back_money
	 *
	 * @return void
	 */
	public function woocommerce_order_status_cancelled( $order_id ) {
		$wooes_discount = (float) get_post_meta( $order_id, 'wooes_discount', true );
		if ( ! empty( $wooes_discount ) ) {
			$options = get_option( 'woo_earn_sharing_options' );
			if ( empty( get_post_meta( $order_id, 'wooes_money_back', true ) ) && (bool) $options['wooes_money_back'] ) {
				Util::give_back_money( $order_id );
			}
		}
	}

	/**
	 * Creates the referral field in the checkout page.
	 *
	 * @return void
	 */
	public function checkout_field() {
		$options = get_option( 'woo_earn_sharing_options' );
		$length  = empty( $options['wooes_code_length'] ) ? 9 : absint( $options['wooes_code_length'] );

		echo '<div id="wooes_referral_code"><h2>' . esc_html__( 'Referral Code', 'woo-earn-sharing' ) . '</h2>';
		woocommerce_form_field(
			'wooes_referral_code',
			array(
				'type'        => 'text',
				'label'       => __( 'Add here a referral code, if you have one', 'woo-earn-sharing' ),
				'placeholder' => Util::format_code( str_repeat( 'X', $length ) ),
				'required'    => false,
				'class'       => array(
					'wooes-checkout-field form-row-wide',
				),
			)
		);
		echo '</div>';
	}

	/**
	 * Processes the checkout referral code field
	 *
	 * @return void
	 */
	public function checkout_field_process() {

		$nonce_value = wc_get_var( $_REQUEST['woocommerce-process-checkout-nonce'], wc_get_var( $_REQUEST['_wpnonce'], '' ) ); // phpcs:ignore
		if ( empty( $nonce_value ) || ! wp_verify_nonce( $nonce_value, 'woocommerce-process_checkout' ) ) {
			return;
		}

		if ( ! empty( $_POST['wooes_referral_code'] ) ) {
			$code = Util::parse_code( $_POST['wooes_referral_code'] ); // phpcs:ignore
			if ( empty( Util::get_user_by_code( $code ) ) ) {
				wc_add_notice( __( 'Invalid Referral Code.', 'woo-earn-sharing' ), 'error' );
			} elseif ( Util::get_user_code() === $code ) {
				wc_add_notice( __( 'You cannot use your own code as a Referral Code.', 'woo-earn-sharing' ), 'error' );
			}
		}
	}

	/**
	 * Adds the referral code to the order post after the checkout.
	 *
	 * @param integer $order_id The order ID.
	 * @return void
	 */
	public function checkout_field_update_order_meta( $order_id ) {
		$nonce_value = wc_get_var( $_REQUEST['woocommerce-process-checkout-nonce'], wc_get_var( $_REQUEST['_wpnonce'], '' ) ); // phpcs:ignore
		if ( empty( $nonce_value ) || ! wp_verify_nonce( $nonce_value, 'woocommerce-process_checkout' ) ) {
			return;
		}

		if ( ! empty( $_POST['wooes_referral_code'] ) ) {
			$code = Util::parse_code( $_POST['wooes_referral_code'] ); // phpcs:ignore
			update_post_meta( $order_id, 'wooes_referral_code', $code );
		}
	}

	/**
	 * Adds a discount if the user has money in their balance.
	 *
	 * @param WC_Cart $cart The cart object.
	 *
	 * @see Util::get_fee
	 *
	 * @return void
	 */
	public function discount_balance( $cart ) {
		$total    = $cart->cart_contents_total;
		$discount = Util::get_fee( $total );
		if ( ! empty( $discount ) ) {
			$cart->add_fee( __( 'Balance', 'woo-earn-sharing' ), - $discount );
		}
	}

	/**
	 * Adds the discount to the order.
	 *
	 * @param integer $order_id The order ID.
	 * @return void
	 */
	public function checkout_add_meta( $order_id ) {
		$order          = new \WC_Order( $order_id );
		$subtotal       = 0;
		$subtotal_taxes = 0;

		foreach ( $order->get_items() as $item ) {
				$subtotal       += (float) $item->get_subtotal();
				$subtotal_taxes += (float) $item->get_subtotal_tax();
		}

		$total    = $subtotal + $subtotal_taxes;
		$discount = Util::get_fee( $total );
		$balance  = Util::get_user_balance();

		if ( ! empty( $discount ) ) {
			update_user_meta( $order->get_user_id(), 'wooes_balance', $balance - $discount );
			update_post_meta( $order_id, 'wooes_discount', $discount );
			update_post_meta( $order_id, 'wooes_money_back', 0 );
		}
	}

	/**
	 * The [wooes_user_balance] shortcode.
	 *
	 * @return string
	 */
	public function user_balance_shortcode() {
		return wc_price( Util::get_user_balance() );
	}

	/**
	 * The [wooes_user_code] shortcode.
	 *
	 * @return string
	 */
	public function user_code_shortcode() {
		return Util::get_user_code( false, true );
	}
}
