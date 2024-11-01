<?php
namespace Woo_Earn_Sharing;

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://lucio.dev
 * @since      1.0.0
 *
 * @package    Woo_Earn_Sharing
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Adds the bulk action, users new columns and display of referral code on the WooCommerce order edit page.
 *
 * @package    Woo_Earn_Sharing
 * @author     Lucio Dev <contact@lucio.dev>
 */
class Admin {
	/**
	 * Initialize the class and set its properties.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		add_action( 'admin_init', array( $this, 'require_woocommerce' ) );

		add_filter( 'manage_users_custom_column', array( $this, 'add_user_column_data' ), 10, 3 );
		add_filter( 'manage_users_columns', array( $this, 'add_user_columns' ) );
		add_filter( 'bulk_actions-users', array( $this, 'bulk_generate_code' ) );
		add_filter( 'handle_bulk_actions-users', array( $this, 'handle_bulk_generate_code' ), 10, 3 );
		add_filter( 'admin_notices', array( $this, 'generated_codes_notice' ), 10, 3 );
		add_action( 'woocommerce_admin_order_data_after_billing_address', array( $this, 'add_referral_code_to_woocommerce_edit_order' ), 20, 3 );
	}

	/**
	 * Checks if WooCommerce is activated, deactivating the plugin otherwise.
	 *
	 * @return void
	 */
	public function require_woocommerce() {
		if ( is_admin() && current_user_can( 'activate_plugins' ) && ! is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
			add_action(
				'admin_notices',
				function() {
					?>
						<div class="error"><p><?php esc_html_e( 'WooCommerce is required in order to activate the Woo Earn Sharing plugin.', 'woo-earn-sharing' ); ?> </p></div>
					<?php
				}
			);
			deactivate_plugins( WOOES_ROOT_FILE );
			if ( isset( $_GET['activate'] ) ) {
				unset( $_GET['activate'] );
			}
		}
	}

	/**
	 * Adds the referral code to the WooCommerce edit order screen.
	 *
	 * @param \WC_Order $order The order ID.
	 * @return void
	 */
	public function add_referral_code_to_woocommerce_edit_order( $order ) {
		$code = $order->get_meta( 'wooes_referral_code' );
		if ( empty( $code ) ) {
			return;
		}

		$user = Util::get_user_by_code( $code );
		if ( empty( $user ) ) {
			$content = sprintf( '<br>%s: %s (%s)', __( 'Referral Code', 'woo-earn-sharing' ), Util::format_code( $code ), __( 'User deleted', 'woo-earn-sharing' ) );
		} else {
			$content = sprintf( '<br>%s: <a href="%s">%s</a>', __( 'Referral Code', 'woo-earn-sharing' ), get_edit_user_link( $user->ID ), Util::format_code( $code ) );
		}
		echo wp_kses_post( $content );
	}

	/**
	 * Adds the wooes-generate-code bulk action.
	 *
	 * @param array $bulk_actions The previous bulk actions array.
	 * @return array
	 */
	public function bulk_generate_code( $bulk_actions ) {
		$bulk_actions['wooes-generate-code'] = __( 'WOOES - Generate a new code', 'woo-earn-sharing' );
		return $bulk_actions;
	}

	/**
	 * Handle the wooes-generate-code bulk action.
	 *
	 * @param string $redirect_url The URL to be redirected to.
	 * @param string $action       The current bulk action.
	 * @param array  $user_ids     Array of selected users IDs.
	 * @return string
	 */
	public function handle_bulk_generate_code( $redirect_url, $action, $user_ids ) {
		if ( 'wooes-generate-code' === $action ) {
			foreach ( $user_ids as $user_id ) {
				update_user_meta( $user_id, 'wooes_code', Util::generate_new_referral_code() );
			}
			$redirect_url = add_query_arg( 'codes-generated', count( $user_ids ), $redirect_url );
		}
		return $redirect_url;
	}

	/**
	 * Shows a success message.
	 *
	 * @return void
	 */
	public function generated_codes_notice() {
		global $pagenow;
		if ( 'users.php' !== $pagenow || empty( $_GET['codes-generated'] ) ) {
			return;
		}
		echo sprintf( '<div id="message" class="updated notice is-dismissable"><p>%s</p></div>', esc_html__( 'New codes generated', 'woo-earn-sharing' ) );
	}

	/**
	 * Adds referral related columns to the users table.
	 *
	 * @param array $columns The previous columns array.
	 * @return array
	 */
	public function add_user_columns( $columns ) {
		$columns['wooes_code']    = __( 'Referral Code', 'woo-earn-sharing' );
		$columns['wooes_balance'] = __( 'Balance', 'woo-earn-sharing' );
		return $columns;
	}

	/**
	 * Populates the referral related columns with data.
	 *
	 * @param string  $val The value of the current cell.
	 * @param string  $column_name The column name.
	 * @param integer $user_id The user ID.
	 * @return string
	 */
	public function add_user_column_data( $val, $column_name, $user_id ) {
		if ( 'wooes_code' === $column_name ) {
			$code = get_user_meta( $user_id, 'wooes_code', true );
			return Util::format_code( $code );
		} elseif ( 'wooes_balance' === $column_name ) {
			$balance = get_user_meta( $user_id, 'wooes_balance', true );
			return wc_price( $balance );
		}
		return '';
	}
}
