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
		add_action( 'switch_theme', array( $this, 'delete_pro_notice_datas' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

	}

	/**
	 * Function to hold the available themes, which have pro version available.
	 *
	 * @return array Theme lists.
	 */
	public static function get_theme_lists() {

		$theme_lists = array(
			'spacious'   => 'https://themegrill.com/pricing/?pid=958&vid=1221792&utm_source=spacious-dashboard-message&utm_medium=view-pricing-link&utm_campaign=upgrade',
			'colormag'   => 'https://themegrill.com/pricing/?pid=1183000&vid=1219398&utm_source=colormag-dashboard-message&utm_medium=view-pricing-link&utm_campaign=upgrade',
			'estore'     => 'https://themegrill.com/pricing/?pid=1242738&vid=1242847&utm_source=estore-dashboard-message&utm_medium=view-pricing-link&utm_campaign=upgrade',
			'ample'      => 'https://themegrill.com/pricing/?pid=774550&vid=1219401&utm_source=ample-dashboard-message&utm_medium=view-pricing-link&utm_campaign=upgrade',
			'accelerate' => 'https://themegrill.com/pricing/?pid=7394&vid=1221785&utm_source=accelerate-dashboard-message&utm_medium=view-pricing-link&utm_campaign=upgrade',
			'colornews'  => 'https://themegrill.com/pricing/?pid=1198835&vid=1219395&utm_source=colornews-dashboard-message&utm_medium=view-pricing-link&utm_campaign=upgrade',
			'foodhunt'   => 'https://themegrill.com/pricing/?pid=1246665&vid=1246668&utm_source=foodhunt-dashboard-message&utm_medium=view-pricing-link&utm_campaign=upgrade',
			'fitclub'    => 'https://themegrill.com/pricing/?pid=1242755&vid=1242761&utm_source=fitclub-dashboard-message&utm_medium=view-pricing-link&utm_campaign=upgrade',
			'radiate'    => 'https://themegrill.com/pricing/?pid=179&vid=1221773&utm_source=radiate-dashboard-message&utm_medium=view-pricing-link&utm_campaign=upgrade',
			'freedom'    => 'https://themegrill.com/pricing/?pid=12287&vid=1221795&utm_source=freedom-dashboard-message&utm_medium=view-pricing-link&utm_campaign=upgrade',
			'himalayas'  => 'https://themegrill.com/pricing/?pid=1199493&vid=1219392&utm_source=himalayas-dashboard-message&utm_medium=view-pricing-link&utm_campaign=upgrade',
			'esteem'     => 'https://themegrill.com/pricing/?pid=14083&vid=1221789&utm_source=esteem-dashboard-message&utm_medium=view-pricing-link&utm_campaign=upgrade',
			'envince'    => 'https://themegrill.com/pricing/?pid=1256403&vid=1256406&utm_source=envince-dashboard-message&utm_medium=view-pricing-link&utm_campaign=upgrade',
			'suffice'    => 'https://themegrill.com/pricing/?pid=1307844&vid=1307847&utm_source=suffice-dashboard-message&utm_medium=view-pricing-link&utm_campaign=upgrade',
			'cenote'     => 'https://themegrill.com/pricing/?pid=1383257&vid=1383267&utm_source=cenote-dashboard-message&utm_medium=view-pricing-link&utm_campaign=upgrade',
			'zakra'      => 'https://zakratheme.com/pricing/?utm_source=zakra-dashboard-message&utm_medium=view-pricing-link&utm_campaign=upgrade',
		);

		return $theme_lists;

	}

	/**
	 * Set upgrade time and display the admin notice as required.
	 */
	public function pro_theme_notice() {

		global $current_user;
		$this->current_user_data = $current_user;
		$this->active_theme      = wp_get_theme();

		// In case user is using child theme, we need to show `Upgrade To Pro` notice too.
		if ( is_child_theme() ) {
			$this->active_theme = wp_get_theme()->parent()->get( 'Name' );
		}

		$option = get_option( 'tg_pro_theme_notice_start_time' );

		if ( ! $option ) {
			update_option( 'tg_pro_theme_notice_start_time', time() );
		}

		add_action( 'admin_notices', array( $this, 'pro_theme_notice_markup' ), 0 );
		add_action( 'admin_init', array( $this, 'pro_theme_notice_partial_ignore' ), 0 );
		add_action( 'admin_init', array( $this, 'pro_theme_notice_ignore' ), 0 );

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
		$theme_notice_start_time = get_option( 'tg_pro_theme_notice_start_time' );
		$pre_sales_query_link    = ( 'zakra' !== $current_theme ) ? "https://themegrill.com/contact/?utm_source={$current_theme}-dashboard-message&utm_medium=button-link&utm_campaign=pre-sales" : "https://zakratheme.com/support/?utm_source={$current_theme}-dashboard-message&utm_medium=button-link&utm_campaign=pre-sales";
		$ignore_notice_permanent = get_user_meta( $this->current_user_data->ID, 'tg_nag_pro_theme_notice_ignore', true );
		$ignore_notice_partially = get_user_meta( $this->current_user_data->ID, 'tg_nag_pro_theme_notice_partial_ignore', true );

		// Return if the theme is not available in theme lists.
		if ( ! array_key_exists( $current_theme, $theme_lists ) ) {
			return;
		}

		// Return if `Zakra Pro` plugin is installed.
		if ( is_plugin_active( 'zakra-pro/zakra-pro.php' ) && 'zakra' === $current_theme ) {
			return;
		}

		/**
		 * Return from notice display if:
		 *
		 * 1. The theme installed is less than 10 days ago.
		 * 2. If the user has ignored the message partially for 2 days.
		 * 3. Dismiss always if clicked on 'Dismiss' button.
		 */
		if ( ( $theme_notice_start_time > strtotime( '-10 day' ) ) || ( $ignore_notice_partially > strtotime( '-2 day' ) ) || ( $ignore_notice_permanent ) ) {
			return;
		}
		?>

		<div class="notice updated pro-theme-notice">
			<p>
				<?php
				$pro_link = '<a target="_blank" href=" ' . esc_url( $theme_lists[ $current_theme ] ) . ' ">' . esc_html__( 'upgrade to pro', 'themegrill-demo-importer' ) . '</a>';

				printf(
					esc_html__(
						/* Translators: %1$s current user display name., %2$s Currently activated theme., %3$s Pro theme link., %4$s Coupon code. */
						'Howdy, %1$s! You\'ve been using %2$s theme for a while now, and we hope you\'re happy with it. If you need more options and access to the premium features, you can %3$s. Also, you can use the coupon code %4$s to get 15 percent discount while making the purchase. Enjoy!', 'themegrill-demo-importer'
					),
					'<strong>' . esc_html( $this->current_user_data->display_name ) . '</strong>',
					$this->active_theme,
					$pro_link,
					'<code>upgrade15</code>'
				);
				?>
			</p>

			<div class="links">
				<a href="<?php echo esc_url( $theme_lists[ $current_theme ] ); ?>" class="btn button-primary"
				   target="_blank">
					<span class="dashicons dashicons-thumbs-up"></span>
					<span><?php esc_html_e( 'Upgrade To Pro', 'themegrill-demo-importer' ); ?></span>
				</a>

				<a href="?tg_nag_pro_theme_notice_partial_ignore=1" class="btn button-secondary">
					<span class="dashicons dashicons-calendar"></span>
					<span><?php esc_html_e( 'Maybe later', 'themegrill-demo-importer' ); ?></span>
				</a>

				<a href="<?php echo esc_url( $pre_sales_query_link ); ?>"
				   class="btn button-secondary" target="_blank">
					<span class="dashicons dashicons-edit"></span>
					<span><?php esc_html_e( 'Got pre sales queries?', 'themegrill-demo-importer' ); ?></span>
				</a>
			</div>

			<a class="notice-dismiss" href="?tg_nag_pro_theme_notice_ignore=1"></a>
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

	/**
	 * Set the nag for permanently ignored users.
	 */
	public function pro_theme_notice_ignore() {

		$user_id = $this->current_user_data->ID;

		if ( isset( $_GET['tg_nag_pro_theme_notice_ignore'] ) && '1' == $_GET['tg_nag_pro_theme_notice_ignore'] ) {
			update_user_meta( $user_id, 'tg_nag_pro_theme_notice_ignore', time() );
		}

	}

	/**
	 * Delete the pro notice datas if theme is switched.
	 */
	public function delete_pro_notice_datas() {

		include_once TGDM_ABSPATH . 'includes/class-demo-importer-deactivator.php';

		TG_Demo_Importer_Deactivator::pro_upgrade_notice();

	}

}

new TG_Pro_Theme_Notice();
