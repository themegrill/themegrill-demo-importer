<?php
/**
 * Admin View: Page - Welcome
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<div class="themegrill-demo-BlankState">
	<div id="welcome-panel" class="welcome-panel">
		<div class="welcome-panel-content">
			<h2><?php _e( 'Welcome to ThemeGrill Demo Importer!', 'themegrill-demo-importer' ); ?></h2>
			<h3><?php _e( 'Get Started','themegrill-demo-importer' ); ?></h3>
			<div class="welcome-panel-column-container">
				<div class="welcome-panel-column">
					<ul>
						<li><?php printf( __( '1. Visit <a href="%s" target="_blank"><strong>this page</strong></a> and download demo zip file.','themegrill-demo-importer' ),esc_url( 'http://themegrill.com/theme-demo-file-downloads/' ) ); ?></li>
						<li><?php _e( '2. Click <strong>Upload Demo</strong> button on the top of this Page.','themegrill-demo-importer' ); ?></li>
						<li><?php _e( '3. Browse the demo zip file and click <strong>Install Now</strong>.','themegrill-demo-importer' ); ?></li>
						<li><?php _e( '4. Go to <strong>Available Demos</strong> tab.','themegrill-demo-importer' ); ?></li>
						<li><?php _e( '5. Click <strong>Import</strong> button and wait for few minutes. Done!','themegrill-demo-importer' ); ?></li>
					</ul>
				</div>
			</div>
		</div>
	</div>
</div>
