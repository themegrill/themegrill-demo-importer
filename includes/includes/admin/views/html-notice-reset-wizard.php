<?php
/**
 * Admin View: Notice - Reset Wizard
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<div id="message" class="notice notice-info is-dismissible" data-notice_id="demo-importer">
	<p><?php printf( __( '<strong>Notice</strong> &#8211; If you want to completely remove a demo installation after importing it, you can use a plugin like %1$sWordPress Reset%2$s.', 'themegrill-demo-importer' ), '<a target="_blank" href="' . esc_url( 'https://wordpress.org/plugins/wordpress-reset/' ) . '">', '</a>' ); ?></p>
</div>
