<?php
/**
 * Admin View: Notice - Reset Wizard
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$plugin_slug    = 'wordpress-reset';
$install_plugin = wp_nonce_url( add_query_arg( array(
	'action' => 'install-plugin',
	'plugin' => $plugin_slug,
), self_admin_url( 'update.php' ) ), 'install-plugin_' . $plugin_slug );

?>
<div id="message" class="updated themegrill-demo-importer-message">
	<p><?php printf( __( '<strong>WordPress Reset</strong> &#8211; If you want to completely remove a demo installation after importing it, you can use a plugin like %1$sWordPress Reset%2$s.', 'themegrill-demo-importer' ), '<a target="_blank" href="' . esc_url( 'https://wordpress.org/plugins/wordpress-reset/' ) . '">', '</a>' ); ?></p>
	<p class="submit"><a href="<?php echo esc_url( $install_plugin ); ?>" class="button button-primary"><?php _e( 'Install WordPress Reset', 'themegrill-demo-importer' ); ?></a> <a class="button-secondary skip" href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'themegrill-demo-importer-hide-notice', 'reset_notice' ), 'themegrill_demo_importer_hide_notice_nonce', '_themegrill_demo_importer_notice_nonce' ) ); ?>"><?php _e( 'Hide this notice', 'themegrill-demo-importer' ); ?></a></p>
</div>
