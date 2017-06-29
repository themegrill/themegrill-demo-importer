<?php
/**
 * Enables ThemeGrill Demo Importer, via the command line.
 *
 * @class    TG_Demo_Importer_CLI
 * @version  1.0.0
 * @package  Importer/Classes
 * @category CLI
 * @author   ThemeGrill
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * TG_Demo_Importer_CLI Class.
 */
class TG_Demo_Importer_CLI {

	/**
	 * Load required hooks to make the CLI work.
	 */
	public function __construct() {
		$this->hooks();
	}

	/**
	 * Sets up and hooks WP CLI to our CLI code.
	 */
	private function hooks() {
		WP_CLI::add_hook( 'after_wp_load', array( __CLASS__, 'register_commands' ) );
	}

	/**
	 * Registers the reset command.
	 */
	public static function register_commands() {
		WP_CLI::add_command( 'reset', array( __CLASS__, 'reset' ) );
	}

	/**
	 * Reset WordPress back to default.
	 *
	 * [--yes]
	 * : Do not prompt for confirmation.
	 *
	 * ## EXAMPLES
	 *
	 *    # Reset WordPress.
	 *    $ wp reset
	 *
	 * @param array $args
	 * @param array $assoc_args
	 */
	public static function reset( $args, $assoc_args ) {
		global $wpdb, $current_user;

		if ( empty( $assoc_args['yes'] ) ) {
			WP_CLI::confirm( 'Are you sure you want to reset the WordPress back to default?' );
		}

		require_once( ABSPATH . '/wp-admin/includes/upgrade.php' );

		$blogname  = get_option( 'blogname' );
		$admin_email = get_option( 'admin_email' );
		$blog_public = get_option( 'blog_public' );

		if ( $current_user->user_login != 'admin' ) {
			$user = get_user_by( 'login', 'admin' );
		}

		if ( empty( $user->user_level ) || $user->user_level < 10 ) {
			$user = $current_user;
		}

		$prefix = str_replace( '_', '\_', $wpdb->prefix );
		$tables = $wpdb->get_col( "SHOW TABLES LIKE '{$prefix}%'" );
		foreach ( $tables as $table ) {
			$wpdb->query( "DROP TABLE $table" );
		}

		$result = wp_install( $blogname, $user->user_login, $user->user_email, $blog_public );
		extract( $result, EXTR_SKIP );

		$query = $wpdb->prepare( "UPDATE $wpdb->users SET user_pass = %s, user_activation_key = '' WHERE ID = %d", $user->user_pass, $user_id );
		$wpdb->query( $query );

		if ( get_user_meta( $user_id, 'default_password_nag' ) ) {
			update_user_meta( $user_id, 'default_password_nag', false );
		}

		if ( get_user_meta( $user_id, $wpdb->prefix . 'default_password_nag' ) ) {
			update_user_meta( $user_id, $wpdb->prefix . 'default_password_nag', false );
		}

		activate_plugin( plugin_basename( TGDM_PLUGIN_FILE ) );

		wp_clear_auth_cookie();
		wp_set_auth_cookie( $user_id );

		WP_CLI::success( sprintf( __( 'WordPress has been reset and user "%1$s" was recreated with its previous password.' ), $user->user_login ) );
	}
}

new TG_Demo_Importer_CLI();
