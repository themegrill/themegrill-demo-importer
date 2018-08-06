<?php
/**
 * Admin View: Page - Importer
 *
 * @package Importer
 */

defined( 'ABSPATH' ) || exit;

?>
<div class="wrap demo-importer">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Demo Importer', 'themegrill-demo-importer' ); ?></h1>

	<?php if ( apply_filters( 'themegrill_demo_importer_upcoming_demos', false ) ) : ?>
		<a href="<?php echo esc_url( 'https://themegrill.com/upcoming-demos' ); ?>" class="page-title-action" target="_blank"><?php esc_html_e( 'Upcoming Demos', 'themegrill-demo-importer' ); ?></a>
	<?php endif; ?>

	<hr class="wp-header-end">

	<div class="error hide-if-js">
		<p><?php _e( 'The Demo Importer screen requires JavaScript.', 'themegrill-demo-importer' ); ?></p>
	</div>

	<h2 class="screen-reader-text hide-if-no-js"><?php _e( 'Filter demos list', 'themegrill-demo-importer' ); ?></h2>

	<div class="wp-filter hide-if-no-js">
		<div class="filter-section">
			<div class="filter-count">
				<span class="count theme-count demo-count"></span>
			</div>

			<?php if ( ! empty( $this->demo_packages->category ) ) : ?>
				<ul class="filter-links category">
					<?php foreach ( $this->demo_packages->category as $slug => $label ) : ?>
						<li><a href="#" data-sort="<?php echo esc_attr( $slug ); ?>" class="category-tab"><?php echo esc_html( $label ); ?></a></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
		<div class="filter-section right">
			<?php if ( ! empty( $this->demo_packages->pagebuilder ) ) : ?>
				<ul class="filter-links pagebuilder">
					<?php foreach ( $this->demo_packages->pagebuilder as $slug => $label ) : ?>
						<li><a href="#" data-sort="<?php echo esc_attr( $slug ); ?>" class="pagebuilder-tab"><?php echo esc_html( $label ); ?></a></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>

			<form class="search-form"></form>
		</div>
	</div>
	<h2 class="screen-reader-text hide-if-no-js"><?php _e( 'Themes list', 'themegrill-demo-importer' ); ?></h2>
	<div class="theme-browser content-filterable"></div>
	<div class="theme-install-overlay wp-full-overlay expanded"></div>

	<p class="no-themes"><?php _e( 'No demos found. Try a different search.', 'themegrill-demo-importer' ); ?></p>
	<span class="spinner"></span>
</div>

<script id="tmpl-demo" type="text/template">
	<# if ( data.screenshot_url ) { #>
		<div class="theme-screenshot">
			<img src="{{ data.screenshot_url }}" alt="" />
		</div>
	<# } else { #>
		<div class="theme-screenshot blank"></div>
	<# } #>

	<# if ( data.is_pro ) { #>
		<span class="premium-demo-banner"><?php _e( 'Pro', 'themegrill-demo-importer' ); ?></span>
	<# } #>

	<span class="more-details"><?php _ex( 'Details &amp; Preview', 'demo', 'themegrill-demo-importer' ); ?></span>
	<div class="theme-author">
		<?php
		/* translators: %s: Demo author name */
		printf( __( 'By %s', 'themegrill-demo-importer' ), '{{{ data.author }}}' );
		?>
	</div>

	<div class="theme-id-container">
		<# if ( data.active ) { #>
			<h2 class="theme-name" id="{{ data.id }}-name">
				<?php
				/* translators: %s: Demo name */
				printf( __( '<span>Imported:</span> %s', 'themegrill-demo-importer' ), '{{{ data.name }}}' );
				?>
			</h2>
		<# } else { #>
			<h2 class="theme-name" id="{{ data.id }}-name">{{{ data.name }}}</h2>
		<# } #>

		<div class="theme-actions">
			<# if ( data.active ) { #>
				<a class="button button-primary live-preview" target="_blank" href="<?php echo home_url( '/' ); ?>"><?php _e( 'Live Preview', 'themegrill-demo-importer' ); ?></a>
			<# } else { #>
				<# if ( data.is_pro ) { #>
					<a class="button button-primary purchase-now" href="{{ data.homepage }}" target="_blank"><?php _e( 'Buy Now', 'themegrill-demo-importer' ); ?></a>
				<# } else { #>
					<# if ( ! _.isEmpty( data.hasNotice ) ) { #>
						<# if ( data.hasNotice['required_theme'] ) { #>
							<a class="button button-primary hide-if-no-js tips demo-import disabled" href="#" data-name="{{ data.name }}" data-slug="{{ data.id }}" data-tip="<?php echo esc_attr( sprintf( __( 'Required %s theme must be activated to import this demo.', 'themegrill-demo-importer' ), '{{{ data.theme }}}' ) ); ?>"><?php _e( 'Import', 'themegrill-demo-importer' ); ?></a>
						<# } else if ( data.hasNotice['required_plugins'] ) { #>
							<a class="button button-primary hide-if-no-js tips demo-import disabled" href="#" data-name="{{ data.name }}" data-slug="{{ data.id }}" data-tip="<?php echo esc_attr( 'Required Plugins must be activated to import this demo.', 'themegrill-demo-importer' ); ?>"><?php _e( 'Import', 'themegrill-demo-importer' ); ?></a>
						<# } #>
					<# } else { #>
						<?php
						/* translators: %s: Demo name */
						$aria_label = sprintf( _x( 'Import %s', 'demo', 'themegrill-demo-importer' ), '{{ data.name }}' );
						?>
						<a class="button button-primary hide-if-no-js demo-import" href="#" data-name="{{ data.name }}" data-slug="{{ data.id }}" aria-label="<?php echo $aria_label; ?>"><?php _e( 'Import', 'themegrill-demo-importer' ); ?></a>
					<# } #>
				<# } #>
				<button class="button preview install-demo-preview"><?php _e( 'Preview', 'themegrill-demo-importer' ); ?></button>
			<# } #>
		</div>
	</div>

	<# if ( data.imported ) { #>
		<div class="notice notice-success notice-alt"><p><?php _ex( 'Imported', 'demo', 'themegrill-demo-importer' ); ?></p></div>
	<# } #>
</script>

<script id="tmpl-demo-preview" type="text/template">
	<div class="wp-full-overlay-sidebar">
		<div class="wp-full-overlay-header">
			<button class="close-full-overlay"><span class="screen-reader-text"><?php _e( 'Close', 'themegrill-demo-importer' ); ?></span></button>
			<button class="previous-theme"><span class="screen-reader-text"><?php _ex( 'Previous', 'Button label for a demo', 'themegrill-demo-importer' ); ?></span></button>
			<button class="next-theme"><span class="screen-reader-text"><?php _ex( 'Next', 'Button label for a demo', 'themegrill-demo-importer' ); ?></span></button>
			<# if ( data.is_pro ) { #>
				<a class="button button-primary purchase-now" href="{{ data.homepage }}" target="_blank"><?php _e( 'Buy Now', 'themegrill-demo-importer' ); ?></a>
			<# } else { #>
				<# if ( ! _.isEmpty( data.hasNotice ) ) { #>
					<# if ( data.hasNotice['required_theme'] ) { #>
						<a class="button button-primary hide-if-no-js tips demo-import disabled" href="#" data-name="{{ data.name }}" data-slug="{{ data.id }}" data-tip="<?php echo esc_attr( sprintf( __( 'Required %s theme must be activated to import this demo.', 'themegrill-demo-importer' ), '{{{ data.theme }}}' ) ); ?>"><?php _e( 'Import', 'themegrill-demo-importer' ); ?></a>
					<# } else if ( data.hasNotice['required_plugins'] ) { #>
						<a class="button button-secondary hide-if-no-js install-plugins" href="#"><?php _e( 'Install Plugins', 'themegrill-demo-importer' ); ?></a>
					<# } #>
				<# } else { #>
					<a class="button button-primary hide-if-no-js demo-import" href="#" data-name="{{ data.name }}" data-slug="{{ data.id }}"><?php _e( 'Import Demo', 'themegrill-demo-importer' ); ?></a>
				<# } #>
			<# } #>
		</div>
		<div class="wp-full-overlay-sidebar-content">
			<div class="install-theme-info">
				<h3 class="theme-name">
					{{ data.name }}
					<# if ( data.is_pro ) { #>
						<span class="premium-demo-tag"><?php _e( 'Pro', 'themegrill-demo-importer' ); ?></span>
					<# } #>
				</h3>

				<span class="theme-by">
					<?php
					/* translators: %s: Demo author name */
					printf( __( 'By %s', 'themegrill-demo-importer' ), '{{ data.author }}' );
					?>
				</span>

				<img class="theme-screenshot" src="{{ data.screenshot_url }}" alt="" />

				<div class="theme-details">
					<# if ( ! data.is_pro && ! _.isEmpty( data.hasNotice ) ) { #>
						<div class="notice demo-message notice-warning notice-alt">
							<# if ( data.hasNotice['required_theme'] ) { #>
								<p class="demo-notice"><?php printf( esc_html__( 'Required %s theme must be activated to import this demo.', 'themegrill-demo-importer' ), '<strong>{{{ data.theme }}}</strong>' ); ?></p>
							<# } else if ( data.hasNotice['required_plugins'] ) { #>
								<p class="demo-notice"><?php _e( 'Required Plugins must be activated.', 'themegrill-demo-importer' ); ?></p>
							<# } #>
						</div>
					<# } #>
					<div class="theme-version">
						<?php
						/* translators: %s: Demo version */
						printf( __( 'Version: %s', 'themegrill-demo-importer' ), '{{ data.version }}', 'themegrill-demo-importer' );
						?>
					</div>
					<div class="theme-description">{{{ data.description }}}</div>
				</div>

				<table class="plugins-list-table widefat striped">
					<thead>
						<tr>
							<th scope="col" class="manage-column required-plugins" colspan="2"><?php esc_html_e( 'Required Plugins', 'themegrill-demo-importer' ); ?></th>
						</tr>
					</thead>
					<tbody id="the-list">
						<# if ( ! _.isEmpty( data.plugins ) ) { #>
							<# _.each( data.plugins, function( plugin, slug ) { #>
								<# var checkboxIdPrefix = _.uniqueId( 'checkbox_' ) #>
								<tr class="plugin<# if ( ! plugin.is_install ) { #> install<# } #>" data-slug="{{ slug }}" data-plugin="{{ plugin.slug }}" data-name="{{ plugin.name }}">
									<td class="plugin-name">
										<# if ( plugin.link ) { #>
											<a href="{{{ plugin.link }}}" target="_blank">{{{ plugin.name }}}</a>
										<# } else { #>
											<a href="<?php printf( esc_url( 'https://wordpress.org/plugins/%s' ), '{{ slug }}' ); ?>" target="_blank">{{ plugin.name }}</a>
										<# } #>
									</td>
									<td class="plugin-status">
										<# if ( plugin.is_active ) { #>
											<span class="circle-loader circle-colored checked"><span class="checked"></span></span>
										<# } else { #>
											<span class="circle-loader"></span>
										<# } #>
									</td>
								</tr>
							<# }); #>
						<# } else { #>
							<tr class="no-items">
								<td class="colspanchange" colspan="4"><?php _e( 'No plugins are needed to import this demo.', 'themegrill-demo-importer' ); ?></td>
							</tr>
						<# } #>
					</tbody>
				</table>
			</div>
		</div>
		<div class="wp-full-overlay-footer">
			<div class="demo-import-actions">
				<# if ( data.is_pro ) { #>
					<a class="button button-hero button-primary purchase-now" href="{{ data.homepage }}" target="_blank"><?php _e( 'Buy Now', 'themegrill-demo-importer' ); ?></a>
				<# } else { #>
					<# if ( ! _.isEmpty( data.hasNotice ) ) { #>
						<# if ( data.hasNotice['required_theme'] ) { #>
							<a class="button button-hero button-primary hide-if-no-js tips demo-import disabled" href="#" data-name="{{ data.name }}" data-slug="{{ data.id }}" data-tip="<?php echo esc_attr( sprintf( __( 'Required %s theme must be activated to import this demo.', 'themegrill-demo-importer' ), '{{{ data.theme }}}' ) ); ?>"><?php _e( 'Import Demo', 'themegrill-demo-importer' ); ?></a>
						<# } else if ( data.hasNotice['required_plugins'] ) { #>
							<a class="button button-hero button-secondary hide-if-no-js install-plugins" href="#"><?php _e( 'Install Plugins', 'themegrill-demo-importer' ); ?></a>
						<# } #>
					<# } else { #>
						<a class="button button-hero button-primary hide-if-no-js demo-import" href="#" data-name="{{ data.name }}" data-slug="{{ data.id }}"><?php _e( 'Import Demo', 'themegrill-demo-importer' ); ?></a>
					<# } #>
				<# } #>
			</div>
			<button type="button" class="collapse-sidebar button" aria-expanded="true" aria-label="<?php esc_attr_e( 'Collapse Sidebar', 'themegrill-demo-importer' ); ?>">
				<span class="collapse-sidebar-arrow"></span>
				<span class="collapse-sidebar-label"><?php _e( 'Collapse', 'themegrill-demo-importer' ); ?></span>
			</button>
			<div class="devices-wrapper">
				<div class="devices">
					<button type="button" class="preview-desktop active" aria-pressed="true" data-device="desktop">
						<span class="screen-reader-text"><?php esc_html_e( 'Enter desktop preview mode', 'themegrill-demo-importer' ); ?></span>
					</button>
					<button type="button" class="preview-tablet" aria-pressed="false" data-device="tablet">
						<span class="screen-reader-text"><?php esc_html_e( 'Enter tablet preview mode', 'themegrill-demo-importer' ); ?></span>
					</button>
					<button type="button" class="preview-mobile" aria-pressed="false" data-device="mobile">
						<span class="screen-reader-text"><?php esc_html_e( 'Enter mobile preview mode', 'themegrill-demo-importer' ); ?></span>
					</button>
				</div>
			</div>
		</div>
	</div>
	<div class="wp-full-overlay-main">
		<iframe src="{{ data.preview_url }}" title="<?php esc_attr_e( 'Preview', 'themegrill-demo-importer' ); ?>"></iframe>
	</div>
</script>

<?php
wp_print_request_filesystem_credentials_modal();
wp_print_admin_notice_templates();
tg_print_admin_notice_templates();
