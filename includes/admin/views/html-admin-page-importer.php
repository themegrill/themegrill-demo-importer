<?php
/**
 * Admin View: Page - Importer
 *
 * @package ThemeGrill_Demo_Importer
 */

defined( 'ABSPATH' ) || exit;

?>
<div class="wrap demo-importer">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Demo Importer', 'themegrill-demo-importer' ); ?></h1>

	<a href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'refresh-demo-packages', 'true' ), 'refresh_demo_packages', '_refresh_demo_packages_nonce' ) ); ?>"
	   class="page-title-action"
	   title="<?php esc_html_e( 'If you do not see the new demos on the list, please click this button to fetch all the available demos.', 'themegrill-demo-importer' ); ?>"
	>
		<?php esc_html_e( 'Refresh Demos', 'themegrill-demo-importer' ); ?>
	</a>

	<?php if ( apply_filters( 'themegrill_demo_importer_upcoming_demos', false ) ) : ?>
		<a href="<?php echo esc_url( 'https://themegrill.com/upcoming-demos' ); ?>" class="page-title-action" target="_blank"><?php esc_html_e( 'Upcoming Demos', 'themegrill-demo-importer' ); ?></a>
	<?php endif; ?>

	<hr class="wp-header-end">

	<div class="error hide-if-js">
		<p><?php esc_html_e( 'The Demo Importer screen requires JavaScript.', 'themegrill-demo-importer' ); ?></p>
	</div>

	<h2 class="screen-reader-text hide-if-no-js"><?php esc_html_e( 'Filter demos list', 'themegrill-demo-importer' ); ?></h2>

	<div class="wp-filter hide-if-no-js">
		<div class="filter-section">
			<div class="filter-count">
				<span class="count theme-count demo-count"></span>
			</div>

			<?php if ( ! empty( $this->demo_packages->categories ) ) : ?>
				<ul class="filter-links categories">
					<?php foreach ( $this->demo_packages->categories as $slug => $label ) : ?>
						<li><a href="#" data-sort="<?php echo esc_attr( $slug ); ?>" class="category-tab"><?php echo esc_html( $label ); ?></a></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
		<div class="filter-section right">
			<?php if ( ! empty( $this->demo_packages->pagebuilders ) ) : ?>
				<ul class="filter-links pagebuilders">
					<?php foreach ( $this->demo_packages->pagebuilders as $slug => $label ) : ?>
						<?php if ( 'default' !== $slug ) : ?>
							<li><a href="#" data-type="<?php echo esc_attr( $slug ); ?>" class="pagebuilder-tab"><?php echo esc_html( $label ); ?></a></li>
						<?php else : ?>
							<li><a href="#" data-type="<?php echo esc_attr( $slug ); ?>" class="pagebuilder-tab tips" data-tip="<?php esc_attr_e( 'Without Page Builder', 'themegrill-demo-importer' ); ?>"><?php echo esc_html( $label ); ?></a></li>
						<?php endif; ?>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>

			<form class="search-form"></form>
		</div>
	</div>
	<h2 class="screen-reader-text hide-if-no-js"><?php esc_html_e( 'Themes list', 'themegrill-demo-importer' ); ?></h2>
	<div class="theme-browser content-filterable"></div>
	<div class="theme-install-overlay wp-full-overlay expanded"></div>

	<p class="no-themes"><?php esc_html_e( 'No demos found. Try a different search.', 'themegrill-demo-importer' ); ?></p>
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

	<# if ( data.isPremium ) { #>
		<span class="premium-demo-banner"><?php esc_html_e( 'Premium', 'themegrill-demo-importer' ); ?></span>
	<# } #>

	<# if ( data.isPro && data.isAllThemePlan ) { #>
		<span class="premium-demo-banner"><?php esc_html_e( 'Pro Plus', 'themegrill-demo-importer' ); ?></span>
	<# } #>

	<# if ( data.isPro && ! data.isAllThemePlan ) { #>
		<span class="premium-demo-banner"><?php esc_html_e( 'Pro', 'themegrill-demo-importer' ); ?></span>
	<# } #>

	<div class="theme-author">
		<?php
		/* translators: %s: Demo author name */
		printf( esc_html__( 'By %s', 'themegrill-demo-importer' ), '{{{ data.author }}}' );
		?>
	</div>

	<div class="theme-id-container">
		<# if ( data.active ) { #>
			<h2 class="theme-name" id="{{ data.id }}-name">
				<?php
				/* translators: %s: Demo name */
				printf( __( '<span>Imported:</span> %s', 'themegrill-demo-importer' ), '{{{ data.name }}}' ); // @codingStandardsIgnoreLine
				?>
			</h2>
		<# } else { #>
			<h2 class="theme-name" id="{{ data.id }}-name">{{{ data.name }}}</h2>
		<# } #>

		<div class="theme-actions">
			<# if ( data.active ) { #>
				<a class="button button-primary live-preview" target="_blank" href="<?php echo esc_url( get_site_url( null, '/' ) ); ?>"><?php esc_html_e( 'Live Preview', 'themegrill-demo-importer' ); ?></a>
			<# } else { #>
				<# if ( data.isPremium ) { #>
					<a class="button button-primary purchase-now" href="{{ data.homepage }}" target="_blank"><?php esc_html_e( 'Buy Now', 'themegrill-demo-importer' ); ?></a>
				<# } else if ( data.isPro ) { #>
					<a class="button button-primary purchase-now" href="{{ data.homepage }}" target="_blank"><?php esc_html_e( 'Buy Now', 'themegrill-demo-importer' ); ?></a>
				<# } else if ( data.isAllThemePlan ) { #>
					<a class="button button-primary purchase-now" href="{{ data.homepage }}" target="_blank"><?php esc_html_e( 'Upgrade Theme Plan', 'themegrill-demo-importer' ); ?></a>
				<# } else if ( data.requiredVersion ) { #>
					<a class="button button-primary" href="<?php echo current_user_can( 'update_themes' ) ? esc_url( admin_url( '/update-core.php' ) ) : '#'; ?>" title="{{ data.updateThemeNotice }}" target="_blank"><?php esc_html_e( 'Update', 'themegrill-demo-importer' ); ?></a>
				<# } else { #>
					<?php
					/* translators: %s: Demo name */
					$aria_label = sprintf( esc_html_x( 'Import %s', 'demo', 'themegrill-demo-importer' ), '{{ data.name }}' );
					?>
					<a class="button button-primary hide-if-no-js demo-import" href="#" data-name="{{ data.name }}" data-slug="{{ data.id }}" aria-label="<?php echo esc_attr( $aria_label ); ?>" data-plugins="{{ JSON.stringify( data.plugins ) }}"><?php esc_html_e( 'Import', 'themegrill-demo-importer' ); ?></a>
				<# } #>
				<button class="button preview install-demo-preview"><?php esc_html_e( 'Preview', 'themegrill-demo-importer' ); ?></button>
			<# } #>
		</div>
	</div>

	<# if ( data.imported ) { #>
		<div class="notice notice-success notice-alt"><p><?php echo esc_html_x( 'Imported', 'demo', 'themegrill-demo-importer' ); ?></p></div>
	<# } #>
</script>

<script id="tmpl-demo-preview" type="text/template">
	<div class="wp-full-overlay-sidebar">
		<div class="wp-full-overlay-header">
			<button class="close-full-overlay"><span class="screen-reader-text"><?php esc_html_e( 'Close', 'themegrill-demo-importer' ); ?></span></button>
			<button class="previous-theme"><span class="screen-reader-text"><?php echo esc_html_x( 'Previous', 'Button label for a demo', 'themegrill-demo-importer' ); ?></span></button>
			<button class="next-theme"><span class="screen-reader-text"><?php echo esc_html_x( 'Next', 'Button label for a demo', 'themegrill-demo-importer' ); ?></span></button>
			<# if ( data.isPremium ) { #>
				<a class="button button-primary purchase-now" href="{{ data.homepage }}" target="_blank"><?php esc_html_e( 'Buy Now', 'themegrill-demo-importer' ); ?></a>
			<# } else if ( data.isPro ) { #>
				<a class="button button-primary purchase-now" href="{{ data.homepage }}" target="_blank"><?php esc_html_e( 'Buy Now', 'themegrill-demo-importer' ); ?></a>
			<# } else if ( data.isAllThemePlan ) { #>
				<a class="button button-primary purchase-now" href="{{ data.homepage }}" target="_blank"><?php esc_html_e( 'Upgrade Theme Plan', 'themegrill-demo-importer' ); ?></a>
			<# } else if ( data.requiredTheme ) { #>
				<button class="button button-primary hide-if-no-js disabled"><?php esc_html_e( 'Import Demo', 'themegrill-demo-importer' ); ?></button>
			<# } else if ( data.requiredVersion ) { #>
				<a class="button button-primary" href="<?php echo current_user_can( 'update_themes' ) ? esc_url( admin_url( '/update-core.php' ) ) : '#'; ?>" title="{{ data.updateThemeNotice }}" target="_blank"><?php esc_html_e( 'Update', 'themegrill-demo-importer' ); ?></a>
			<# } else { #>
				<# if ( data.active ) { #>
					<a class="button button-primary live-preview" target="_blank" href="<?php echo esc_url( get_site_url( null, '/' ) ); ?>"><?php esc_html_e( 'Live Preview', 'themegrill-demo-importer' ); ?></a>
				<# } else { #>
					<a class="button button-primary hide-if-no-js demo-import" href="#" data-name="{{ data.name }}" data-slug="{{ data.id }}"><?php esc_html_e( 'Import Demo', 'themegrill-demo-importer' ); ?></a>
				<# } #>
			<# } #>
		</div>
		<div class="wp-full-overlay-sidebar-content">
			<div class="install-theme-info">
				<h3 class="theme-name">
					{{ data.name }}
					<# if ( data.isPremium ) { #>
						<span class="premium-demo-tag"><?php esc_html_e( 'Premium', 'themegrill-demo-importer' ); ?></span>
					<# } #>

					<# if ( data.isPro && data.isAllThemePlan ) { #>
						<span class="premium-demo-tag"><?php esc_html_e( 'Pro Plus', 'themegrill-demo-importer' ); ?></span>
					<# } #>

					<# if ( data.isPro && ! data.isAllThemePlan ) { #>
						<span class="premium-demo-tag"><?php esc_html_e( 'Pro', 'themegrill-demo-importer' ); ?></span>
					<# } #>
				</h3>

				<span class="theme-by">
					<?php
					/* translators: %s: Demo author name */
					printf( esc_html__( 'By %s', 'themegrill-demo-importer' ), '{{ data.author }}' );
					?>
				</span>

				<img class="theme-screenshot" src="{{ data.screenshot_url }}" alt="" />

				<div class="theme-details">
					<# if ( ! data.isPremium && data.requiredTheme ) { #>
						<div class="demo-message notice notice-error notice-alt"><p>
							<?php
							/* translators: %s: Theme Name */
							printf( esc_html__( '%s theme is not active.', 'themegrill-demo-importer' ), '<strong>{{{ data.theme }}}</strong>' );
							?>
						</p></div>
					<# } #>

					<# if ( ! data.isPro && data.requiredTheme ) { #>
						<div class="demo-message notice notice-error notice-alt"><p>
						<?php
						/* translators: %s: Theme Name */
						printf( esc_html__( '%s theme is not active.', 'themegrill-demo-importer' ), '<strong>{{{ data.theme }}}</strong>' );
						?>
						</p></div>
					<# } #>
					<div class="theme-version">
						<?php
						/* translators: %s: Demo version */
						printf( esc_html__( 'Version: %s', 'themegrill-demo-importer' ), '{{ data.version }}', 'themegrill-demo-importer' );
						?>
					</div>
					<div class="theme-description">{{{ data.description }}}</div>
				</div>

				<div class="plugins-details">
					<h4 class="plugins-info"><?php esc_html_e( 'Plugins Information', 'themegrill-demo-importer' ); ?></h4>

					<table class="plugins-list-table widefat striped">
						<thead>
							<tr>
								<th scope="col" class="manage-column required-plugins" colspan="2"><?php esc_html_e( 'Required Plugins', 'themegrill-demo-importer' ); ?></th>
							</tr>
						</thead>
						<tbody id="the-list">
							<# if ( ! _.isEmpty( data.plugins ) ) { #>
								<# _.each( data.plugins, function( plugin, slug ) { #>
									<tr class="plugin<# if ( ! plugin.is_active ) { #> inactive<# } #>" data-slug="{{ slug }}" data-plugin="{{ plugin.slug }}" data-name="{{ plugin.name }}">
										<td class="plugin-name">
											<a href="<?php printf( esc_url( 'https://wordpress.org/plugins/%s' ), '{{ slug }}' ); ?>" target="_blank">{{ plugin.name }}</a>
										</td>
										<td class="plugin-status">
											<# if ( plugin.is_active && plugin.is_install ) { #>
												<span class="active"></span>
											<# } else if ( plugin.is_install ) { #>
												<span class="activate-now<# if ( ! data.requiredPlugins ) { #> active<# } #>"></span>
											<# } else { #>
												<span class="install-now<# if ( ! data.requiredPlugins ) { #> active<# } #>"></span>
											<# } #>
										</td>
									</tr>
								<# }); #>
							<# } else { #>
								<tr class="no-items">
									<td class="colspanchange" colspan="4"><?php esc_html_e( 'No plugins are required for this demo.', 'themegrill-demo-importer' ); ?></td>
								</tr>
							<# } #>
						</tbody>
					</table>
				</div>
			</div>
		</div>
		<div class="wp-full-overlay-footer">
			<div class="demo-import-actions">
				<# if ( data.isPremium ) { #>
					<a class="button button-hero button-primary purchase-now" href="{{ data.homepage }}" target="_blank"><?php esc_html_e( 'Buy Now', 'themegrill-demo-importer' ); ?></a>
				<# } else if ( data.isPro ) { #>
					<a class="button button-hero button-primary purchase-now" href="{{ data.homepage }}" target="_blank"><?php esc_html_e( 'Buy Now', 'themegrill-demo-importer' ); ?></a>
				<# } else if ( data.isAllThemePlan ) { #>
					<a class="button button-hero button-primary purchase-now" href="{{ data.homepage }}" target="_blank"><?php esc_html_e( 'Upgrade Theme Plan', 'themegrill-demo-importer' ); ?></a>
				<# } else if ( data.requiredTheme ) { #>
					<button class="button button-hero button-primary hide-if-no-js disabled"><?php esc_html_e( 'Import Demo', 'themegrill-demo-importer' ); ?></button>
				<# } else if ( data.requiredVersion ) { #>
					<a class="button button-hero button-primary" href="<?php echo current_user_can( 'update_themes' ) ? esc_url( admin_url( '/update-core.php' ) ) : '#'; ?>" title="{{ data.updateThemeNotice }}" target="_blank"><?php esc_html_e( 'Update', 'themegrill-demo-importer' ); ?></a>
				<# } else { #>
					<# if ( data.active ) { #>
						<a class="button button-primary live-preview button-hero hide-if-no-js" target="_blank" href="<?php echo esc_url( get_site_url( null, '/' ) ); ?>"><?php esc_html_e( 'Live Preview', 'themegrill-demo-importer' ); ?></a>
					<# } else { #>
						<a class="button button-hero button-primary hide-if-no-js demo-import" href="#" data-name="{{ data.name }}" data-slug="{{ data.id }}"><?php esc_html_e( 'Import Demo', 'themegrill-demo-importer' ); ?></a>
					<# } #>
				<# } #>
			</div>
			<button type="button" class="collapse-sidebar button" aria-expanded="true" aria-label="<?php esc_attr_e( 'Collapse Sidebar', 'themegrill-demo-importer' ); ?>">
				<span class="collapse-sidebar-arrow"></span>
				<span class="collapse-sidebar-label"><?php esc_html_e( 'Collapse', 'themegrill-demo-importer' ); ?></span>
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
