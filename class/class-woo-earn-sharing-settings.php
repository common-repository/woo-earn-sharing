<?php
namespace Woo_Earn_Sharing;

/**
 * The settings-specific functionality of the plugin.
 *
 * @link       https://lucio.dev
 * @since      1.0.0
 *
 * @package    Woo_Earn_Sharing
 */

/**
 * The settings-specific functionality of the plugin.
 *
 * Registers the menu, settings, fields and sanitization callback.
 *
 * @package    Woo_Earn_Sharing
 * @author     Lucio Dev <contact@lucio.dev>
 */
class Settings {
	/**
	 * Initialize the class and set its properties.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		add_action( 'admin_init', array( $this, 'settings_init' ) );
		add_action( 'admin_menu', array( $this, 'wooes_create_menu' ) );
	}

	public function settings_init() {
		$this->options = get_option( 'woo_earn_sharing_options' );

		register_setting(
			'woo_earn_sharing',
			'woo_earn_sharing_options',
			array(
				'sanitize_callback' => array( $this, 'sanitize_settings' ),
			)
		);

		add_settings_section(
			'wooes_section_rewards',
			__( 'Rewards', 'woo-earn-sharing' ),
			array( $this, 'display_section' ),
			'woo_earn_sharing'
		);

		add_settings_field(
			'wooes_reward_percentage',
			__( 'Reward Percentage', 'woo-earn-sharing' ),
			array( $this, 'display_field' ),
			'woo_earn_sharing',
			'wooes_section_rewards',
			array(
				'label_for'   => 'wooes_reward_percentage',
				'type'        => 'number',
				'placeholder' => '%',
				'default'     => 10,
				'min'         => 0,
				'max'         => 100,
			),
		);

		add_settings_field(
			'wooes_reward_max',
			__( 'Reward Maximum', 'woo-earn-sharing' ),
			array( $this, 'display_field' ),
			'woo_earn_sharing',
			'wooes_section_rewards',
			array(
				'label_for'   => 'wooes_reward_max',
				'type'        => 'number',
				'placeholder' => '0',
				'default'     => 0,
				'min'         => 0,
			),
		);

		add_settings_field(
			'wooes_money_back',
			__( 'Give credit back to user, when order change to Refunded, Cancelled or Failed', 'woo-earn-sharing' ),
			array( $this, 'display_field' ),
			'woo_earn_sharing',
			'wooes_section_rewards',
			array(
				'label_for' => 'wooes_money_back',
				'type'      => 'checkbox',
				'default'   => true,
			),
		);

		add_settings_section(
			'wooes_section_user_code',
			__( 'Referral Code', 'woo-earn-sharing' ),
			array( $this, 'display_section' ),
			'woo_earn_sharing'
		);

		add_settings_field(
			'wooes_code_length',
			__( 'Code Length', 'woo-earn-sharing' ),
			array( $this, 'display_field' ),
			'woo_earn_sharing',
			'wooes_section_user_code',
			array(
				'label_for'   => 'wooes_code_length',
				'type'        => 'number',
				'placeholder' => '0',
				'default'     => 9,
			),
		);

		add_settings_field(
			'wooes_code_alphanumeric',
			__( 'Alphanumeric Code', 'woo-earn-sharing' ),
			array( $this, 'display_field' ),
			'woo_earn_sharing',
			'wooes_section_user_code',
			array(
				'label_for' => 'wooes_code_alphanumeric',
				'type'      => 'checkbox',
				'default'   => true,
			),
		);

		add_settings_section(
			'wooes_section_page',
			__( 'Referral Page', 'woo-earn-sharing' ),
			array( $this, 'display_section' ),
			'woo_earn_sharing'
		);

		add_settings_field(
			'wooes_page_html',
			__( 'Referrals page HTML', 'woo-earn-sharing' ),
			array( $this, 'display_page_html_field' ),
			'woo_earn_sharing',
			'wooes_section_page'
		);
	}

	/**
	 * Sanitizes the options to guarantee everything's fine.
	 *
	 * @param array $option The option array.
	 * @return array
	 */
	public function sanitize_settings( $option ) {
		$options = array(
			'wooes_reward_percentage' => empty( $option['wooes_reward_percentage'] ) ? 0 : (float) $option['wooes_reward_percentage'],
			'wooes_code_length'       => empty( $option['wooes_code_length'] ) ? 9 : (int) $option['wooes_code_length'],
			'wooes_money_back'        => ! empty( $option['wooes_money_back'] ),
			'wooes_code_alphanumeric' => ! empty( $option['wooes_code_alphanumeric'] ),
			'wooes_page_html'         => empty( $option['wooes_page_html'] ) ? '' : wp_kses_post( $option['wooes_page_html'] ),
		);
		return $options;
	}

	/**
	 * Creates a new Wooes menu under the WooCommerce menu.
	 *
	 * @return void
	 */
	public function wooes_create_menu() {
		add_submenu_page(
			'woocommerce',
			__( 'Wooes Settings', 'woo-earn-sharing' ),
			__( 'Wooes', 'woo-earn-sharing' ),
			'manage_woocommerce',
			'wooes-settings',
			array( $this, 'options_page_html' )
		);
	}


	public function options_page_html() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<form action="options.php" method="post">
				<?php
				settings_fields( 'woo_earn_sharing' );
				do_settings_sections( 'woo_earn_sharing' );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	public function display_section( $args ) {
		$description = '';
		switch ( $args['id'] ) {
			case 'wooes_section_rewards':
				$description = __( 'Manage rewards settings.', 'woo-earn-sharing' );
				break;
			case 'wooes_section_user_code':
				$description = __( 'Referral code example: ', 'woo-earn-sharing' ) . Util::generate_new_referral_code( true );
				break;

		}
		?>
			<p class="description"><?php echo esc_html( $description ); ?></p>
		<?php
	}

	public function display_field( $args ) {
		$attrs = '';
		foreach ( $args as $key => $value ) {
			$attrs .= esc_attr( $key ) . '="' . esc_attr( $value ) . '" ';
		}

		$value = isset( $this->options[ $args['label_for'] ] ) ? $this->options[ $args['label_for'] ] : '';

		if ( ! isset( $args['default'] ) && ! isset( $this->options[ $args['label_for'] ] ) ) {
			$value = $args['default'];
		}

		if ( 'checkbox' === $args['type'] ) {
			if ( false !== $value ) {
				$attrs .= 'checked="checked"';
			}
		} else {
			$attrs .= 'value="' . esc_attr( $value ) . '"';
		}

		?>
		<input id="<?php echo esc_attr( $args['label_for'] ); ?>" name="woo_earn_sharing_options[<?php echo esc_attr( $args['label_for'] ); ?>]" <?php echo $attrs; // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped ?>>
		<?php
	}

	public function display_page_html_field() {

		$value = isset( $this->options['wooes_page_html'] ) ? $this->options['wooes_page_html'] : '<p>[wooes_user_balance]<br>[wooes_user_code]</p>';

		$settings = array(
			'teeny'         => true,
			'textarea_rows' => 10,
			'tabindex'      => 1,
			'textarea_name' => 'woo_earn_sharing_options[wooes_page_html]',
		);

		wp_editor( wp_kses_post( $value ), 'wooes_page_html', $settings );

		echo '<br><p><b>Shortcodes:</b><br>[wooes_user_balance]<br>[wooes_user_code]</p>';
	}
}
