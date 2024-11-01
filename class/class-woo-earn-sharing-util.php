<?php
namespace Woo_Earn_Sharing;

class Util {
	/**
	 * Util functions.
	 *
	 * @param integer $user_id The user ID to get the balance from.
	 * @since    1.0.0
	 */
	public static function get_user_balance( $user_id = false ) {
		if ( empty( $user_id ) ) {
			$user_id = get_current_user_id();
		}

		if ( empty( $user_id ) ) {
			return;
		}

		$balance = (float) get_user_meta( $user_id, 'wooes_balance', true );

		/**
		 * Filters the user's balance
		 *
		 * @since 2.0.0
		 *
		 * @param float   $balance The user's balance.
		 * @param integer $user_id The user ID.
		 */
		$balance = apply_filters( 'wooes_user_balance', $balance, $user_id );

		return $balance;
	}

	/**
	 * Gets the referral code from the given user, or the current one if false.
	 *
	 * @param integer|boolean $user_id The user ID.
	 * @param boolean         $format  Whether or not the code should be formatted.
	 * @return void
	 */
	public static function get_user_code( $user_id = false, $format = false ) {

		if ( empty( $user_id ) ) {
			$user_id = get_current_user_id();
		}

		if ( empty( $user_id ) ) {
			return;
		}

		$code = get_user_meta( $user_id, 'wooes_code', true );

		if ( $format ) {
			return self::format_code( $code );
		}

		/**
		 * Filters the user's code
		 *
		 * @since 2.0.0
		 *
		 * @param string  $code    The code fetched from the user.
		 * @param integer $user_id The user ID.
		 * @param boolean $format  Whether or not the code should be formatted.
		 */
		$code = apply_filters( 'wooes_get_user_code', $code, $user_id, $format );

		return $code;
	}

	/**
	 * Returns a code given a referral code.
	 *
	 * @param string $code The code.
	 * @return \WP_User
	 */
	public static function get_user_by_code( $code ) {
		$code  = self::parse_code( $code );
		$users = get_users(
			array(
				'meta_key'     => 'wooes_code',
				'meta_value'   => $code,
				'meta_compare' => '=',
			)
		);

		$user = false;
		if ( ! empty( $users ) ) {
			$user = $users[0];
		}

		/**
		 * Filters the user by the code
		 *
		 * @since 2.0.0
		 *
		 * @param \WP_User|false $user The fetched user of false if none.
		 * @param string        $code The code used to search the user.
		 */
		$user = apply_filters( 'wooes_get_user_by_code', $user, $code );

		return $user;
	}

	/**
	 * Rewards the user after the order is completed, if it's an order with a referral code.
	 *
	 * @param string  $code     The referral code.
	 * @param integer $order_id The order ID.
	 * @return boolean
	 */
	public static function reward_user_by_code( $code, $order_id ) {
		$user = self::get_user_by_code( $code );

		if ( ! empty( $user ) ) {

			$order = new \WC_Order( $order_id );

			if ( ! empty( get_post_meta( $order_id, 'wooes_rewarded_user', true ) ) ) {
				return false;
			}

			if ( ! Race_Condition::start( $code, $order_id ) ) {
				return false; // Another request already started to handle this order with this code.
			}

			$options        = get_option( 'woo_earn_sharing_options' );
			$old_balance    = (float) get_user_meta( $user->ID, 'wooes_balance', true );
			$reward_percent = isset( $options['wooes_reward_percentage'] ) ? (float) $options['wooes_reward_percentage'] / 100 : 0.1;
			$give           = $order->get_total() * $reward_percent;

			if ( ! empty( $options['wooes_reward_max'] ) && $give > (float) $options['wooes_reward_max'] ) {
				$give = (float) $options['wooes_reward_max'];
			}

			$new_balance = $old_balance + $give;

			/**
			 * Filters the new user's balance
			 *
			 * @since 2.0.0
			 *
			 * @param float   $new_balance The new balance value.
			 * @param float   $money       The money being added.
			 * @param float   $old_balance The previous value.
			 * @param boolean $giving_back Whether it's giving the money back or not.
			 */
			$new_balance = apply_filters( 'wooes_new_balance', $new_balance, $give, $old_balance, false );

			if ( update_user_meta( $user->ID, 'wooes_balance', $new_balance ) ) {
				update_post_meta( $order_id, 'wooes_rewarded_user', $user->ID );
				update_post_meta( $order_id, 'wooes_rewarded_amount', $give );

				/* translators: %d: the user ID %f: the rewarded amount */
				$order->add_order_note( sprintf( __( 'The user %1$d was rewarded with %2$f.', 'woo-earn-sharing' ), $user->ID, $give ) );
			}

			return Race_Condition::finish( $code, $order_id );
		}

		return false;
	}

	/**
	 * Gives the money back to the user after the order is cancelled, if it's an order that had a balance used.
	 *
	 * @param integer $order_id The order ID.
	 * @return boolean
	 */
	public static function give_back_money( $order_id ) {

		if ( ! Race_Condition::start( 'money_back', $order_id ) ) {
			return false; // Another request already started to handle this order with this code.
		}

		$order   = new \WC_Order( $order_id );
		$user_id = $order->get_user_id();

		if ( empty( $user_id ) ) {
			return false;
		}

		$wooes_discount = (float) get_post_meta( $order_id, 'wooes_discount', true );
		$old_balance    = (float) get_user_meta( $user_id, 'wooes_balance', true );
		$new_balance    = $wooes_discount + $old_balance;

		/**
		 * Filters the new user's balance
		 *
		 * @since 2.0.0
		 *
		 * @param float $new_balance The new balance value.
		 * @param float $money       The money being added.
		 * @param float $old_balance The previous value.
		 * @param float $giving_back Whether it's giving the money back or not.
		 */
		$new_balance = apply_filters( 'wooes_new_balance', $new_balance, $wooes_discount, $old_balance, true );

		if ( update_user_meta( $user_id, 'wooes_balance', $new_balance ) ) {
			update_post_meta( $order_id, 'wooes_money_back', $user_id );

			/* translators: %d: the user ID %f: the rewarded amount */
			$order->add_order_note( sprintf( __( 'The user %1$d received their money back.', 'woo-earn-sharing' ), $user_id ) );
		}

		return Race_Condition::finish( 'money_back', $order_id );
	}

	/**
	 * Gets the discount considering the user's balance.
	 *
	 * @param float $total The cart totals.
	 * @return float
	 */
	public static function get_fee( $total ) {
		$balance  = self::get_user_balance();
		$discount = null;
		if ( (float) $total <= $balance ) {
			$discount = $total - 1;
		} else {
			$discount = $balance;
		}
		return $discount;
	}

	/**
	 * Generates a new referral code.
	 *
	 * @param boolean $format Whether or not the code should return formated.
	 * @return string
	 */
	public static function generate_new_referral_code( $format = false ) {
		$options      = get_option( 'woo_earn_sharing_options' );
		$length       = empty( $options['wooes_code_length'] ) ? 9 : absint( $options['wooes_code_length'] );
		$alphanumeric = ! empty( $options['wooes_code_alphanumeric'] );

		// Method from the wp_generate_password function. Adapted since the original function always returns a string with numbers.
		$chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		if ( $alphanumeric ) {
			$chars .= '0123456789';
		}

		do {
			$code = '';
			for ( $i = 0; $i < $length; $i++ ) {
				$code .= substr( $chars, wp_rand( 0, strlen( $chars ) - 1 ), 1 );
			}

			/**
			 * Filters the newly generated code
			 *
			 * @since 2.0.0
			 *
			 * @param string   $code The randomly generated code.
			 * @param integer  $length The length of the code, from settings.
			 * @param boolean  $alphanumeric Whether it's an alphanumeric code, from settings.
			 */
			$code = apply_filters( 'wooes_generate_new_referral_code', $code, $length, $alphanumeric );

			$users = get_users(
				array(
					'meta_key'     => 'wooes_code',
					'meta_value'   => $code,
					'meta_compare' => '=',
				)
			);
		} while ( ! empty( $users ) );

		if ( $format ) {
			return self::format_code( $code );
		}

		return $code;
	}

	/**
	 * Removes non-alphanumeric characteres from the code.
	 *
	 * @param string $code The raw code.
	 * @return string
	 */
	public static function parse_code( $code ) {
		$code = sanitize_key( $code );
		$code = str_replace( '-', '', $code );
		return $code;
	}

	/**
	 * Formats the code with hyphens.
	 *
	 * @param string $code The raw code.
	 * @return string
	 */
	public static function format_code( $code ) {
		$code = strtoupper( $code );
		if ( strlen( $code ) >= 6 ) {
			$code = implode( '-', str_split( $code, 3 ) );
		}

		/**
		 * Filters the formatted code
		 *
		 * @since 2.0.0
		 *
		 * @param string $code The code.
		 */
		$code = apply_filters( 'wooes_format_code', $code );

		$code = esc_html( $code );
		return $code;
	}
}
