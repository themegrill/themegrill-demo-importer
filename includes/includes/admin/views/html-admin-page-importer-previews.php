<?php
/**
 * Admin View: Page - Demo Previews
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $current_tab;

$template    = get_option( 'template' );
$assets_path = tg_get_demo_importer_assets_path();

?>
<h2 class="screen-reader-text hide-if-no-js"><?php _e( 'Theme demos list', 'themegrill-demo-importer' ); ?></h2>

<div class="theme-browser content-filterable">
	<div class="themes wp-clearfix">
		<?php foreach ( $this->demo_packages as $pack_id => $pack_data ) : ?>
			<div class="theme active" tabindex="0">
				<?php if ( $screenshot = "{$assets_path}images/{$template}/{$pack_id}.jpg" ) : ?>
					<div class="theme-screenshot">
						<?php if ( file_is_displayable_image( $screenshot ) ) : ?>
							<img src="<?php echo esc_url( $screenshot ); ?>" alt="" />
						<?php endif; ?>
					</div>
				<?php else : ?>
					<div class="theme-screenshot blank"></div>
				<?php endif; ?>

				<h2 class="theme-name" id="demo-name"><?php echo esc_html( $pack_data['name'] ); ?></h2>

				<div class="theme-actions">
					<a class="button button-primary live-preview" target="_blank" href="<?php echo esc_url( $pack_data['preview'] ); ?>"><?php _e( 'Live Preview', 'themegrill-demo-importer' ); ?></a>
				</div>
			</div>
		<?php endforeach; ?>
	</div>
</div>
