<?php

defined( 'ABSPATH' ) || exit;

class TG_Pro_Theme_Notice {

	protected $active_theme;

	public function __construct() {

		add_action( 'after_setup_theme', array( $this, 'pro_theme_notice' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		$this->active_theme = wp_get_theme();

	}

	public function pro_theme_notice() {

		$option = get_option( 'tg_pro_theme_notice_start_time' );

		if ( ! $option ) {
			update_option( 'tg_pro_theme_notice_start_time', time() );
		}

		add_action( 'admin_notices', array( $this, 'pro_theme_notice_markup' ), 0 );

	}

	public function enqueue_scripts() {

		$assets_path = tgdm()->plugin_url() . '/includes/admin/assets/';

		wp_register_style( 'tg-demo-importer-notice', $assets_path . 'css/notice.css', array(), TGDM_VERSION );
		wp_enqueue_style( 'tg-demo-importer-notice' );
	}

	public function pro_theme_notice_markup() {

		if ( get_option( 'tg_pro_theme_notice_start_time' ) > strtotime( '-1 min' ) ) {
			return;
		}
		?>

		<div class="updated pro-theme-notice">
			<p>
				<?php

				$pro_link = '<a target="_blank" href=" ' . esc_url( "https://zakratheme.com/pricing/" ) . ' ">' . esc_html( 'Go Pro' ) . ' </a>';

				printf(
					esc_html__(
						'Howdy, You\'ve been using %1$s for a while now, and we hope you\'re happy with it. If you need more options and want to get access to the Premium features, you can %2$s ', 'themegrill-demo-importer'
					),
					$this->active_theme,
					$pro_link
				);
				?>
			</p>
		</div>

		<?php
	}

}

new TG_Pro_Theme_Notice();
