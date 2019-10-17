<?php
/**
 * Class to display the `Upgrade to Pro` admin notice.
 *
 * @package ThemeGrill_Demo_Importer
 * @since   1.6.1
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class to display the `Upgrade to Pro` admin notice.
 *
 * Class TG_Pro_Theme_Notice
 */
class TG_Pro_Theme_Notice {

	protected $active_theme;

	protected $current_user_data;

	public function __construct() {

		add_action( 'after_setup_theme', array( $this, 'pro_theme_notice' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		$this->active_theme = wp_get_theme();

	}

	/**
	 * Function to hold the available themes, which have pro version available.
	 *
	 * @return array Theme lists.
	 */
	public static function get_theme_lists() {

		$theme_lists = array( 'spacious', 'colormag', 'flash', 'estore', 'ample', 'accelerate', 'colornews', 'foodhunt', 'fitclub', 'radiate', 'freedom', 'himalayas', 'esteem', 'envince', 'suffice', 'cenote', 'zakra' );

		return $theme_lists;

	}

	public function pro_theme_notice() {

		global $current_user;
		$this->current_user_data = $current_user;

		$option = get_option( 'tg_pro_theme_notice_start_time' );

		if ( ! $option ) {
			update_option( 'tg_pro_theme_notice_start_time', time() );
		}

		add_action( 'admin_notices', array( $this, 'pro_theme_notice_markup' ), 0 );
		add_action( 'admin_init', array( $this, 'pro_theme_notice_partial_ignore' ), 0 );

	}

	public function enqueue_scripts() {

		$assets_path = tgdm()->plugin_url() . '/includes/admin/assets/';

		wp_register_style( 'tg-demo-importer-notice', $assets_path . 'css/notice.css', array(), TGDM_VERSION );
		wp_enqueue_style( 'tg-demo-importer-notice' );
	}

	public function pro_theme_notice_markup() {

		if ( get_option( 'tg_pro_theme_notice_start_time' ) > strtotime( '-1 min' ) || get_user_meta( $this->current_user_data->ID, 'tg_nag_pro_theme_notice_partial_ignore', true ) > strtotime( '-1 min' ) ) {
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
			<a class="notice-dismiss" href="?tg_nag_pro_theme_notice_partial_ignore=1"></a>
		</div>

		<?php
	}

	public function pro_theme_notice_partial_ignore() {

		$user_id = $this->current_user_data->ID;

		if ( isset( $_GET['tg_nag_pro_theme_notice_partial_ignore'] ) && '1' == $_GET['tg_nag_pro_theme_notice_partial_ignore'] ) {
			update_user_meta( $user_id, 'tg_nag_pro_theme_notice_partial_ignore', time() );
		}

	}

}

new TG_Pro_Theme_Notice();
