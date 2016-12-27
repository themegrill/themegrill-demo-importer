<?php
/**
 * Admin View: Page - Demo Preview
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$previews = $this->prepare_previews_for_js( $this->demo_packages );

?>
<div class="theme-browser rendered">
	<div class="themes wp-clearfix">
		<?php foreach ( $previews as $demo ) : ?>
			<div class="theme" tabindex="0">
				<a target="_blank" href="<?php echo esc_url( $demo['actions']['preview_url'] ); ?>">
					<?php if ( $demo['screenshot'] ) : ?>
						<div class="theme-screenshot">
							<img src="<?php echo esc_url( $demo['screenshot'] ); ?>" alt="" />
						</div>
					<?php else : ?>
						<div class="theme-screenshot blank"></div>
					<?php endif; ?>
					<span class="more-details"><?php _e( 'Demo Preview', 'themegrill-demo-importer' ); ?></span>
				</a>
				<div class="theme-author"><?php
					/* translators: %s: Demo author name */
					printf( __( 'By %s', 'themegrill-demo-importer' ), $demo['author'] );
				?></div>
				<h3 class="theme-name"><?php echo esc_html( $demo['name'] ); ?></h3>

				<div class="theme-actions">
					<?php if ( ! $demo['installed'] ) : ?>
						<?php
						/* translators: %s: Demo name */
						$aria_label = sprintf( _x( 'Download %s', 'demo', 'themegrill-demo-importer' ), esc_attr( $demo['name'] ) );
						?>
						<a class="button button-primary demo-download" data-name="<?php echo esc_attr( $demo['name'] ); ?>" href="<?php echo esc_url( $demo['actions']['download_url'] ); ?>" aria-label="<?php echo esc_attr( $aria_label ); ?>"><?php _e( 'Download', 'themegrill-demo-importer' ); ?></a>
					<?php endif; ?>
					<a class="button button-secondary demo-preview" target="_blank" href="<?php echo esc_url( $demo['actions']['preview_url'] ); ?>"><?php _e( 'Preview', 'themegrill-demo-importer' ); ?></a>
				</div>

				<?php if ( $demo['installed'] ) : ?>
					<div class="notice notice-success notice-alt inline"><p><?php _ex( 'Installed', 'theme', 'themegrill-demo-importer' ); ?></p></div>
				<?php endif; ?>
			</div>
		<?php endforeach; ?>
	</div>
</div>
