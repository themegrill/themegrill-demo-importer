<?php
/**
 * Admin View: Page - Demo Uploaded
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $current_tab;

$template         = get_option( 'template' );
$demo_imported_id = get_option( 'themegrill_demo_imported_id' );

?>
<h2 class="screen-reader-text hide-if-no-js"><?php _e( 'Available demos list', 'themegrill-demo-importer' ); ?></h2>

<div class="theme-browser content-filterable">
	<div class="themes wp-clearfix">
		<?php foreach ( $this->demo_config as $demo_id => $demo_data ) : ?>
			<div class="theme<?php if ( $demo_id == $demo_imported_id ) echo ' active'; ?>" tabindex="0">
				<?php if ( $screenshot = $this->import_file_url( $demo_id, 'screenshot.jpg' ) ) : ?>
					<div class="theme-screenshot">
						<?php if ( file_is_displayable_image( $screenshot ) ) : ?>
							<img src="<?php echo esc_url( $screenshot ); ?>" alt="" />
						<?php endif; ?>
					</div>
				<?php else : ?>
					<div class="theme-screenshot blank"></div>
				<?php endif; ?>

				<?php if ( ! empty( $demo_data['plugins_list'] ) ) : ?>
					<div class="notice inline notice-<?php echo isset( $demo_data['notice_type'] ) ? esc_attr( $demo_data['notice_type'] ) : 'info'; ?> notice-alt">
						<?php if ( ! empty( $demo_data['plugins_list']['required'] ) && $plugins_required = tg_get_plugins_links( $demo_data['plugins_list']['required'] ) ) : ?>
							<p><?php printf( __( '<strong>Required Plugins:</strong> %s', 'themegrill-demo-importer' ), $plugins_required ); ?></p>
						<?php endif; ?>
						<?php if ( ! empty( $demo_data['plugins_list']['recommended'] ) && $plugins_recommended = tg_get_plugins_links( $demo_data['plugins_list']['recommended'] ) ) : ?>
							<p><?php printf( __( '<strong>Recommended Plugins:</strong> %s', 'themegrill-demo-importer' ), $plugins_recommended ); ?></p>
						<?php endif; ?>
					</div>
				<?php endif; ?>

				<?php if ( $demo_id == $demo_imported_id ) { ?>
					<h2 class="theme-name" id="demo-name"><?php
						/* translators: %s: demo name */
						printf( __( '<span>Imported:</span> %s', 'themegrill-demo-importer' ), esc_html( $demo_data['name'] ) );
					?></h2>
				<?php } else { ?>
					<h2 class="theme-name" id="demo-name"><?php echo esc_html( $demo_data['name'] ); ?></h2>
				<?php } ?>
				<div class="theme-actions">
					<?php if ( $demo_id !== $demo_imported_id ) : ?>
						<?php if ( isset( $demo_data['template'] ) && $template !== $demo_data['template'] ) : ?>
							<a class="button button-secondary tips import disabled" href="#" data-demo_id="<?php echo $demo_id; ?>" data-tip="<?php printf( esc_attr( 'Required %s theme must be activated to import this demo.', 'themegrill-demo-importer' ), wp_get_theme()->get( 'Name' ) ); ?>"><?php _e( 'Import', 'themegrill-demo-importer' ); ?></a>
						<?php elseif ( ! empty( $demo_data['plugins_list'] ) ) : ?>
							<?php if ( ! empty( $demo_data['plugins_list']['required'] ) && tg_is_plugins_active( $demo_data['plugins_list']['required'] ) ) : ?>
								<a class="button button-secondary tips import disabled" href="#" data-demo_id="<?php echo $demo_id; ?>" data-tip="<?php esc_attr_e( 'Required Plugin must be activated to import this demo.', 'themegrill-demo-importer' ); ?>"><?php _e( 'Import', 'themegrill-demo-importer' ); ?></a>
							<?php else : ?>
								<a class="button button-secondary import plugins-ready" href="#" data-demo_id="<?php echo $demo_id; ?>"><?php _e( 'Import', 'themegrill-demo-importer' ); ?></a>
							<?php endif; ?>
						<?php else : ?>
							<a class="button button-secondary import no-plugins-needed" href="#" data-demo_id="<?php echo $demo_id; ?>"><?php _e( 'Import', 'themegrill-demo-importer' ); ?></a>
						<?php endif; ?>
						<a class="button button-primary live-preview" target="_blank" href="<?php echo esc_url( $demo_data['demo_url'] ); ?>"><?php _e( 'Live Preview', 'themegrill-demo-importer' ); ?></a>
					<?php endif; ?>
					<a class="button button-primary preview" target="_blank" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php _e( 'Preview', 'themegrill-demo-importer' ); ?></a>
					<span class="spinner"><?php _e( 'Please Wait&hellip;', 'themegrill-demo-importer' ); ?></span>
				</div>
			</div>
		<?php endforeach; ?>
	</div>
</div>
