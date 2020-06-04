<?php
/**
 * Class to display the plugin review notice.
 *
 * @package ThemeGrill_Demo_Importer
 * @since   1.6.4
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class to display the plugin review notice.
 *
 * Class TG_Demo_Importer_Review_Notice
 */
class TG_Demo_Importer_Review_Notice {

	/**
	 * Constructor function to include the required functionality for the class.
	 *
	 * TG_Demo_Importer_Review_Notice constructor.
	 */
	public function __construct() {
		add_action( 'admin_notices', array( $this, 'review_notice_markup' ), 0 );
		add_action( 'admin_init', array( $this, 'ignore_plugin_review_notice' ), 0 );
		add_action( 'admin_init', array( $this, 'ignore_plugin_review_notice_partially' ), 0 );
	}

	/**
	 * Show HTML markup if conditions meet.
	 */
	public function review_notice_markup() {
		$user_id                  = get_current_user_id();
		$current_user             = wp_get_current_user();
		$ignored_notice           = get_user_meta( $user_id, 'tg_demo_importer_plugin_review_notice', true );
		$ignored_notice_partially = get_user_meta( $user_id, 'nag_tg_demo_importer_plugin_review_notice_partially', true );

		// Check for if demo is already imported.
		$demo_imported = get_option( 'themegrill_demo_importer_activated_id' );

		/**
		 * Return from notice display if:
		 *
		 * 1. The demo is not imported.
		 * 2. If the user has ignored the message partially for 15 days.
		 * 3. Dismiss always if clicked on 'I Already Did' and `Dismiss` button.
		 */
		if ( ! $demo_imported || ( $ignored_notice_partially > strtotime( '-15 day' ) ) || $ignored_notice ) {
			return;
		}
		?>
		<div class="notice notice-success tg-demo-importer-notice plugin-review-notice" style="position:relative;">
			<p>
				<?php
				printf(
					/* Translators: %1$s current user display name. */
					esc_html__(
						'Howdy, %1$s! It seems that you have imported the theme demo in your site. We hope that you are happy with it and if you can spare a minute, please help us by leaving a 5-star review on WordPress.org.',
						'themegrill-demo-importer'
					),
					'<strong>' . esc_html( $current_user->display_name ) . '</strong>'
				);
				?>
			</p>

			<div class="links">
				<a href="https://wordpress.org/support/plugin/themegrill-demo-importer/reviews/?filter=5#new-post"
				   class="btn button-primary" target="_blank">
					<span class="dashicons dashicons-thumbs-up"></span>
					<span><?php esc_html_e( 'Sure', 'themegrill-demo-importer' ); ?></span>
				</a>

				<a href="?nag_tg_demo_importer_plugin_review_notice_partially=0" class="btn button-secondary">
					<span class="dashicons dashicons-calendar"></span>
					<span><?php esc_html_e( 'Maybe later', 'themegrill-demo-importer' ); ?></span>
				</a>

				<a href="?nag_tg_demo_importer_plugin_review_notice=0" class="btn button-secondary">
					<span class="dashicons dashicons-smiley"></span>
					<span><?php esc_html_e( 'I already did', 'themegrill-demo-importer' ); ?></span>
				</a>

				<a href="<?php echo esc_url( 'https://wordpress.org/support/plugin/themegrill-demo-importer/' ); ?>"
				   class="btn button-secondary" target="_blank">
					<span class="dashicons dashicons-edit"></span>
					<span><?php esc_html_e( 'Got plugin support question?', 'themegrill-demo-importer' ); ?></span>
				</a>
			</div> <!-- /.links -->

			<a class="notice-dismiss" href="?nag_tg_demo_importer_plugin_review_notice=0"></a>
		</div> <!-- /.plugin-review-notice -->
		<?php
	}

	/**
	 * `I already did` button or `dismiss` button: remove the review notice permanently.
	 */
	public function ignore_plugin_review_notice() {
		/* If user clicks to ignore the notice, add that to their user meta */
		if ( isset( $_GET['nag_tg_demo_importer_plugin_review_notice'] ) && '0' == $_GET['nag_tg_demo_importer_plugin_review_notice'] ) {
			add_user_meta( get_current_user_id(), 'tg_demo_importer_plugin_review_notice', 'true', true );
		}
	}

	/**
	 * `Maybe later` button: remove the review notice partially.
	 */
	public function ignore_plugin_review_notice_partially() {
		/* If user clicks to ignore the notice, add that to their user meta */
		if ( isset( $_GET['nag_tg_demo_importer_plugin_review_notice_partially'] ) && '0' == $_GET['nag_tg_demo_importer_plugin_review_notice_partially'] ) {
			update_user_meta( get_current_user_id(), 'nag_tg_demo_importer_plugin_review_notice_partially', time() );
		}
	}

}

new TG_Demo_Importer_Review_Notice();
