<?php

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Provide a public-facing view for the plugin
 *
 * This file is used to markup the public-facing aspects of the plugin.
 *
 * @link       https://lucio.dev
 * @since      1.0.0
 *
 * @package    Woo_Earn_Sharing
 */

$options = get_option( 'woo_earn_sharing_options' );
$balance = Woo_Earn_Sharing\Util::get_user_balance();
$code    = Woo_Earn_Sharing\Util::get_user_code( false, true );
?>
<div class="wooes-my-account">

	<h3 class="wooes-referrals-title"><?php esc_html_e( 'Referrals', 'woo-earn-sharing' ); ?></h3>

	<div class="wooes-my-account-page">
	<?php
	if ( ! isset( $options['wooes_page_html'] ) ) {
		?>
		<div class="wooes-my-account-info">
			<div class="wooes-current-balance">
				<?php echo esc_html__( 'Balance:' ) . wp_kses_post( wc_price( $balance ) ); ?>
			</div>
			<div class="wooes-current-code">
				<?php echo esc_html__( 'Your Code:' ) . wp_kses_post( $code ); ?>
			</div>
			</div>
		<?php
	} else {
		echo do_shortcode( wp_kses_post( $options['wooes_page_html'] ) );
	}
	?>
	</div>
</div>
