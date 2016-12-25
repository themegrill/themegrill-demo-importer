<?php
/**
 * Admin View: Page - Demo Uploads
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$demos = $this->prepare_demos_for_js( $this->demo_config );

?>
<div class="theme-browser">
	<div class="themes wp-clearfix">
		<?php foreach ( $demos as $demo ) : ?>
			<div class="theme<?php if ( $demo['active'] ) echo ' active'; ?>" tabindex="0" aria-describedby="<?php echo esc_attr( $demo['id'] . '-action ' . $demo['id'] . '-name' ); ?>">
				<?php if ( $demo['screenshot'] ) : ?>
					<div class="theme-screenshot">
						<img src="<?php echo esc_url( $demo['screenshot'] ); ?>" alt="" />
					</div>
				<?php else : ?>
					<div class="theme-screenshot blank"></div>
				<?php endif; ?>

				<span class="more-details" id="<?php echo esc_attr( $demo['id'] . '-action' ); ?>"><?php esc_html_e( 'Demo Details', 'themegrill-demo-importer' ); ?></span>
				<div class="theme-author"><?php
					/* translators: %s: Demo author name */
					printf( __( 'By %s', 'themegrill-demo-importer' ), $demo['author'] );
				?></div>

				<?php if ( $demo['active'] ) { ?>
					<h2 class="theme-name" id="demo-name"><?php
						/* translators: %s: Demo name */
						printf( __( '<span>Imported:</span> %s', 'themegrill-demo-importer' ), esc_html( $demo['name'] ) );
					?></h2>
				<?php } else { ?>
					<h2 class="theme-name" id="<?php echo esc_attr( $demo['id'] . '-name' ); ?>"><?php echo esc_html( $demo['name'] ); ?></h2>
				<?php } ?>

				<div class="theme-actions">
					<?php if ( ! $demo['active'] ) : ?>
						<?php if ( ! empty( $demo['hasNotice'] ) ) : ?>
							<?php if ( isset( $demo['hasNotice']['required_theme'] ) ) : ?>
								<a class="button button-primary tips import disabled" href="#" data-demo_id="<?php echo esc_attr( $demo['id'] ); ?>" data-tip="<?php echo esc_attr( sprintf( __( 'Required %s theme must be activated to import this demo.', 'themegrill-demo-importer' ), $demo['theme'] ) ); ?>"><?php _e( 'Import', 'themegrill-demo-importer' ); ?></a>
							<?php elseif ( isset( $demo['hasNotice']['required_plugins'] ) ) : ?>
								<a class="button button-primary tips import disabled" href="#" data-demo_id="<?php echo esc_attr( $demo['id'] ); ?>" data-tip="<?php echo esc_attr( 'Required Plugin must be activated to import this demo.', 'themegrill-demo-importer' ); ?>"><?php _e( 'Import', 'themegrill-demo-importer' ); ?></a>
							<?php endif; ?>
						<?php else : ?>
							<?php
							/* translators: %s: Demo name */
							$aria_label = sprintf( _x( 'Import %s', 'demo', 'themegrill-demo-importer' ), esc_attr( $demo['name'] ) );
							?>
							<a class="button button-primary import" href="#" data-demo_id="<?php echo esc_attr( $demo['id'] ); ?>" aria-label="<?php echo $aria_label; ?>"><?php _e( 'Import', 'themegrill-demo-importer' ); ?></a>
						<?php endif; ?>
						<a class="button button-secondary live-preview" target="_blank" href="<?php echo esc_url( $demo['actions']['demo_url'] ); ?>"><?php _e( 'Live Preview', 'themegrill-demo-importer' ); ?></a>
					<?php endif; ?>
					<a class="button button-primary site-preview" target="_blank" href="<?php echo esc_url( $demo['actions']['preview'] ); ?>"><?php _e( 'Preview', 'themegrill-demo-importer' ); ?></a>
					<span class="spinner"><?php _e( 'Please Wait&hellip;', 'themegrill-demo-importer' ); ?></span>
				</div>
			</div>
		<?php endforeach; ?>
	</div>
</div>
<div class="theme-overlay"></div>
<p class="no-themes"><?php _e( 'No demos found. Try a different search.', 'themegrill-demo-importer' ); ?></p>
