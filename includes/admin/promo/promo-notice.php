<?php
defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

class TG_Demo_Importer_Promo_Notice {

	public function __construct() {
		add_action( 'admin_notices', array( $this, 'promo_notices' ), 15 );
		add_action( 'admin_init', array( $this, 'ignore_promo_notice' ), 0 );
	}

	public function promo_notices() {
		$ignored_notice = get_user_meta( get_current_user_id(), 'tg_demo_importer_ignore_promo', true );
		if ( $ignored_notice ) {
			error_log( print_r( 'zero', true ) );
			return;
		}

		$theme_lists   = TG_Demo_Importer_Utils::get_theme_supported_themes();
		$current_theme = wp_get_theme();
		$current_theme = strtolower( $current_theme->get( 'Name' ) );

//		error_log( print_r( in_array( array('a', 'b'), array( array('a', 'b'), 'c', 'd' ) ), true ) );
		error_log( print_r( in_array(array( 'zakra', 'masonic', 'explore' ), $theme_lists, false), true ) );
//		error_log( print_r( array( $current_theme, 'masonic', 'explore' ), true ) );

		if ( ! in_array( array( $current_theme, 'masonic', 'explore' ), $theme_lists ) || strpos( $current_theme, '-pro' ) ) {
//			error_log( print_r( ! in_array( array( $current_theme, 'masonic', 'explore' ), $theme_lists, true ), true ) );
			return;
		}

		if ( is_plugin_active( 'zakra-pro/zakra-pro.php' ) && 'zakra' === $current_theme ) {
			error_log( print_r( 'two', true ) );
			return;
		}
		?>
		<div class="notice updated promo-notice">

			<div class="promo-wrap">
				<p>
					<?php
					$string = '<strong>Zakra Black Friday:</strong> Get Zakra with the biggest discount (35%) offer on all plans! Limited Time Offer. <a target="_blank" href="https://zakratheme.com/pricing/">Grab your deals now!</a>';
					echo $string;
					?>
				</p>
			</div>

			<a class="notice-dismiss" href="?tg_demo_importer_ignore_promo=1"></a>
		</div>
		<?php
	}

	public function ignore_promo_notice() {
		$user_id = get_current_user_id();

		$current_date = date( 'Y-m-d' );

		if ( ( $current_date === '2019-12-02' ) || ( isset( $_GET['tg_demo_importer_ignore_promo'] ) && '1' == $_GET['tg_demo_importer_ignore_promo'] ) ) {
			update_user_meta( $user_id, 'tg_demo_importer_ignore_promo', '1' );
		}
	}
}

new TG_Demo_Importer_Promo_Notice();
