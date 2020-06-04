<?php
/**
 * Class to display the plugin deactivation notice.
 *
 * @package ThemeGrill_Demo_Importer
 * @since   1.6.4
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class to display the plugin deactivation notice.
 *
 * Class TG_Demo_Importer_Plugin_Deactivate_Notice
 */
class TG_Demo_Importer_Plugin_Deactivate_Notice {

	/**
	 * Constructor function to include the required functionality for the class.
	 *
	 * TG_Demo_Importer_Plugin_Deactivate_Notice constructor.
	 */
	public function __construct() {
		add_action( 'admin_notices', array( $this, 'deactivate_notice_markup' ), 0 );
		add_action( 'admin_init', array( $this, 'deactivate_plugin' ), 0 );
		add_action( 'admin_init', array( $this, 'ignore_plugin_deactivate_notice' ), 0 );
	}

	/**
	 * Show HTML markup if conditions meet.
	 */
	public function deactivate_notice_markup() {
		$demo_imported            = get_option( 'themegrill_demo_importer_activated_id' );
		$ignore_deactivate_notice = get_option( 'tg_demo_importer_plugin_deactivate_notice' );

		/**
		 * Return from notice display if:
		 *
		 * 1. Demo is not installed.
		 * 2. User does not have the access to deactivate the plugin.
		 * 3. User does have no intention to deactivate the plugin.
		 */
		if ( ! $demo_imported || ! current_user_can( 'deactivate_plugin' ) || ( $ignore_deactivate_notice && current_user_can( 'deactivate_plugin' ) ) ) {
			return;
		}
		?>
		<div class="notice notice-success tg-demo-importer-notice plugin-deactivate-notice" style="position:relative;">
			<p>
				<?php
				esc_html_e(
					'It seems you\'ve imported the theme demo successfully. Now, the purpose of this plugin is fulfilled and it has no more use. So, if you\'re satisfied with this import, you can safely deactivate it by clicking the button.',
					'themegrill-demo-importer'
				);
				?>
			</p>

			<div class="links">
				<a href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'deactivate-themegrill-demo-importer-plugin', 'true' ), 'deactivate_themegrill_demo_importer_plugin', '_deactivate_themegrill_demo_importer_plugin_nonce' ) ); ?>"
				   class="btn button-primary"
				>
					<span class="dashicons dashicons-thumbs-up"></span>
					<span><?php esc_html_e( 'Deactivate', 'themegrill-demo-importer' ); ?></span>
				</a>
			</div> <!-- /.links -->

			<a class="notice-dismiss" href="?nag_tg_demo_importer_plugin_deactivate_notice=0"></a>
		</div> <!-- /.plugin-deactivate-notice -->
		<?php
	}

	/**
	 * Deactivates the ThemeGrill Demo Importer plugin.
	 */
	public function deactivate_plugin() {
		// Deactivate the plugin.
		if ( isset( $_GET['deactivate-themegrill-demo-importer-plugin'] ) && isset( $_GET['_deactivate_themegrill_demo_importer_plugin_nonce'] ) ) {
			if ( ! wp_verify_nonce( $_GET['_deactivate_themegrill_demo_importer_plugin_nonce'], 'deactivate_themegrill_demo_importer_plugin' ) ) {
				wp_die( __( 'Action failed. Please refresh the page and retry.', 'themegrill-demo-importer' ) );
			}

			// Get the plugin.
			$plugin = 'themegrill-demo-importer/themegrill-demo-importer.php';
			if ( is_plugin_active( $plugin ) ) {
				deactivate_plugins( $plugin );
			}

			// Redirect to main dashboard page.
			wp_safe_redirect( admin_url( 'plugins.php' ) );
		}
	}

	/**
	 * Remove the plugin deactivate notice permanently.
	 */
	public function ignore_plugin_deactivate_notice() {
		/* If user clicks to ignore the notice, add that to the options table. */
		if ( isset( $_GET['nag_tg_demo_importer_plugin_deactivate_notice'] ) && '0' == $_GET['nag_tg_demo_importer_plugin_deactivate_notice'] ) {
			update_option( 'tg_demo_importer_plugin_deactivate_notice', 'true' );
		}
	}

}

new TG_Demo_Importer_Plugin_Deactivate_Notice();
