<?php
/**
 * Class to display the `Upgrade To Pro` admin notice.
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

	/**
	 * Currently active theme in the site.
	 *
	 * @var \WP_Theme
	 */
	protected $active_theme;

	/**
	 * Current user id.
	 *
	 * @var int Current user id.
	 */
	protected $current_user_data;

	/**
	 * Constructor function for `Upgrade To Pro` admin notice.
	 *
	 * TG_Pro_Theme_Notice constructor.
	 */
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

		$theme_lists = array(
			'spacious'   => 'https://themegrill.com/pricing/?pid=958&vid=1221792',
			'colormag'   => 'https://themegrill.com/pricing/?pid=1183000&vid=1219398',
			'estore'     => 'https://themegrill.com/pricing/?pid=1242738&vid=1242847',
			'ample'      => 'https://themegrill.com/pricing/?pid=774550&vid=1219401',
			'accelerate' => 'https://themegrill.com/pricing/?pid=7394&vid=1221785',
			'colornews'  => 'https://themegrill.com/pricing/?pid=1198835&vid=1219395',
			'foodhunt'   => 'https://themegrill.com/pricing/?pid=1246665&vid=1246668',
			'fitclub'    => 'https://themegrill.com/pricing/?pid=1242755&vid=1242761',
			'radiate'    => 'https://themegrill.com/pricing/?pid=179&vid=1221773',
			'freedom'    => 'https://themegrill.com/pricing/?pid=12287&vid=1221795',
			'himalayas'  => 'https://themegrill.com/pricing/?pid=1199493&vid=1219392',
			'esteem'     => 'https://themegrill.com/pricing/?pid=14083&vid=1221789',
			'envince'    => 'https://themegrill.com/pricing/?pid=1256403&vid=1256406',
			'suffice'    => 'https://themegrill.com/pricing/?pid=1307844&vid=1307847',
			'cenote'     => 'https://themegrill.com/pricing/?pid=1383257&vid=1383267',
			'zakra'      => 'https://zakratheme.com/pricing/',
		);

		return $theme_lists;

	}

	/**
	 * Set upgrade time and display the admin notice as required.
	 */
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

	/**
	 * Enqueue the required scripts.
	 */
	public function enqueue_scripts() {

		$assets_path = tgdm()->plugin_url() . '/includes/admin/assets/';

		wp_register_style( 'tg-demo-importer-notice', $assets_path . 'css/notice.css', array(), TGDM_VERSION );
		wp_enqueue_style( 'tg-demo-importer-notice' );
	}

	/**
	 * Display the `Upgrade To Pro` admin notice.
	 */
	public function pro_theme_notice_markup() {

		$theme_lists             = self::get_theme_lists();
		$current_theme           = strtolower( $this->active_theme );
		$ignore_notice_partially = get_user_meta( $this->current_user_data->ID, 'tg_nag_pro_theme_notice_partial_ignore', true );

		// Return if the theme is not available in theme lists.
		if ( ! array_key_exists( $current_theme, $theme_lists ) ) {
			return;
		}

		if ( get_option( 'tg_pro_theme_notice_start_time' ) > strtotime( '-1 min' ) || $ignore_notice_partially > strtotime( '-1 min' ) ) {
			return;
		}
		?>

		<div class="notice updated pro-theme-notice">
			<p>
				<?php
				$pro_link = '<a target="_blank" href=" ' . esc_url( "https://zakratheme.com/pricing/" ) . ' ">' . esc_html( 'Go Pro' ) . ' </a>';

				printf(
					esc_html__(
						'Howdy %1$s!, You\'ve been using %2$s for a while now, and we hope you\'re happy with it. If you need more options and want to get access to the Premium features, you can %3$s ', 'themegrill-demo-importer'
					),
					'<strong>' . esc_html( $this->current_user_data->display_name ) . '</strong>',
					$this->active_theme,
					$pro_link
				);
				?>
			</p>

			<a class="notice-dismiss" href="?tg_nag_pro_theme_notice_partial_ignore=1"></a>
		</div>

		<?php
	}

	/**
	 * Set the nag for partially ignored users.
	 */
	public function pro_theme_notice_partial_ignore() {

		$user_id = $this->current_user_data->ID;

		if ( isset( $_GET['tg_nag_pro_theme_notice_partial_ignore'] ) && '1' == $_GET['tg_nag_pro_theme_notice_partial_ignore'] ) {
			update_user_meta( $user_id, 'tg_nag_pro_theme_notice_partial_ignore', time() );
		}

	}

}

new TG_Pro_Theme_Notice();
