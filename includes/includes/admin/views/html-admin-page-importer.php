<?php
/**
 * Admin View: Page - Importer
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$demo_imported_id  = get_option( 'themegrill_demo_imported_id' );
$demo_filter_links = apply_filters( 'themegrill_demo_importer_filter_links_array', array(
	'welcome'  => __( 'Welcome', 'themegrill-demo-importer' ),
	'uploaded' => __( 'Available Demos', 'themegrill-demo-importer' ),
	'previews' => __( 'Theme Demos', 'themegrill-demo-importer' ),
) );

?>
<div class="wrap demo-importer">
	<h1><?php
		esc_html_e( 'Demo Importer', 'themegrill-demo-importer' );
		if ( current_user_can( 'upload_files' ) ) {
			echo ' <button type="button" class="upload-view-toggle page-title-action hide-if-no-js tg-demo-upload" aria-expanded="false">' . __( 'Upload Demo', 'themegrill-demo-importer' ) . '</button>';
		}
	?></h1>
	<?php if ( ! get_option( 'themegrill_demo_imported_notice_dismiss' ) && in_array( $demo_imported_id, array_keys( $this->demo_config ) ) ) : ?>
		<div id="message" class="notice notice-info is-dismissible" data-notice_id="demo-importer">
			<p><?php printf( __( '<strong>Notice</strong> &#8211; If you want to completely remove a demo installation after importing it, you can use a plugin like %1$sWordPress Reset%2$s.', 'themegrill-demo-importer' ), '<a target="_blank" href="' . esc_url( 'https://wordpress.org/plugins/wordpress-reset/' ) . '">', '</a>' ); ?></p>
		</div>
	<?php endif; ?>
	<div class="error hide-if-js">
		<p><?php _e( 'The Demo Importer screen requires JavaScript.', 'themegrill-demo-importer' ); ?></p>
	</div>
	<div class="upload-theme">
		<p class="install-help"><?php _e( 'If you have a demo pack in a .zip format, you may install it by uploading it here.', 'themegrill-demo-importer' ); ?></p>
		<form method="post" enctype="multipart/form-data" class="wp-upload-form" action="<?php echo self_admin_url( 'themes.php?page=demo-importer&action=upload-demo' ); ?>">
			<?php wp_nonce_field( 'demo-upload' ); ?>
			<label class="screen-reader-text" for="demozip"><?php _e( 'Demo zip file', 'themegrill-demo-importer' ); ?></label>
			<input type="file" id="demozip" name="demozip" />
			<?php submit_button( __( 'Install Now', 'themegrill-demo-importer' ), 'button', 'install-demo-submit', false ); ?>
		</form>
	</div>

	<h2 class="screen-reader-text hide-if-no-js"><?php _e( 'Filter demos list', 'themegrill-demo-importer' ); ?></h2>

	<div class="wp-filter hide-if-no-js">
		<div class="filter-count">
			<span class="count demo-count"><?php echo 'previews' == $current_tab ? count( $this->demo_packages ) : count( $this->demo_config ); ?></span>
		</div>

		<ul class="filter-links">
			<?php
				foreach ( $demo_filter_links as $name => $label ) {
					if ( ( empty( $this->demo_config ) && 'uploaded' == $name ) || ( empty( $this->demo_packages ) && 'previews' == $name ) ) {
						continue;
					}
					echo '<li><a href="' . admin_url( 'themes.php?page=demo-importer&tab=' . $name ) . '" class="demo-tab ' . ( $current_tab == $name ? 'current' : '' ) . '">' . $label . '</a></li>';
				}
				do_action( 'themegrill_demo_importer_filter_links' );
			?>
		</ul>
	</div>
	<?php do_action( 'themegrill_demo_importer_' . $current_tab ); ?>
</div>
