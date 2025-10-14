<?php
/**
 * Admin interface class for ThemeGrill Starter Templates.
 *
 * @package ThemeGrill\StarterTemplates
 * @since   2.0.0
 */

namespace ThemeGrill\StarterTemplates;

use ThemeGrill\StarterTemplates\Services\ThemeService;
use ThemeGrill\StarterTemplates\Traits\Hooks;

class Admin {

	use Hooks;

	/**
	 * Constructor.
	 *
	 * @since 2.0.0
	 */
	public function __construct() {
		$this->doAction( 'themegrill:starter-templates:admin-construct', $this );

		$this->addAction( 'admin_menu', [ $this, 'addMenu' ] );
		$this->addAction( 'admin_init', [ $this, 'onAdminInit' ] );

		$this->doAction( 'themegrill:starter-templates:admin-constructed', $this );
	}

	/**
	 * Add the admin menu page for Starter Templates.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function addMenu() {
		$menuConfig = $this->applyFilters(
			'themegrill:starter-templates:menu-config',
			[
				'page_title' => __( 'Starter Templates', 'themegrill-demo-importer' ),
				'menu_title' => __( 'Starter Templates', 'themegrill-demo-importer' ),
				'capability' => 'manage_options',
				'menu_slug'  => 'themegrill-starter-templates',
				'icon_url'   => '',
				'position'   => 59,
			]
		);

		$this->doAction( 'themegrill:starter-templates:add-menu', $menuConfig );

		add_menu_page(
			$menuConfig['page_title'],
			$menuConfig['menu_title'],
			$menuConfig['capability'],
			$menuConfig['menu_slug'],
			fn() => '',
			$menuConfig['icon_url'],
			$menuConfig['position']
		);

		$this->doAction( 'themegrill:starter-templates:menu-added', $menuConfig );
	}

	/**
	 * Handle the WordPress 'admin_init' action.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function onAdminInit() {
		if ( isset( $_GET['page'] ) && 'themegrill-starter-templates' === $_GET['page'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended

			$this->doAction( 'themegrill:starter-templates:admin-page-init' );

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( esc_html__( 'Sorry, you are not allowed to access this page.', 'themegrill-starter-templates' ) );
			}

			header( 'Content-Type: ' . get_option( 'html_type' ) . '; charset=' . get_option( 'blog_charset' ) );

			remove_all_actions( 'wp_head' );
			remove_all_actions( 'wp_print_styles' );
			remove_all_actions( 'wp_print_head_scripts' );
			remove_all_actions( 'wp_footer' );
			remove_all_actions( 'wp_enqueue_scripts' );
			remove_all_actions( 'after_wp_tiny_mce' );

			add_action( 'wp_head', 'wp_print_styles', 8 );
			add_action( 'wp_head', 'wp_site_icon' );

			add_action( 'wp_footer', 'wp_enqueue_scripts', 1 );
			add_action( 'wp_footer', 'wp_print_head_scripts', 9 );
			add_action( 'wp_footer', 'wp_print_footer_scripts', 20 );
			add_action( 'wp_footer', 'wp_auth_check_html', 30 );

			$this->addAction( 'wp_enqueue_scripts', [ $this, 'enqueueScripts' ], 999999 );
			$this->addAction( 'wp_enqueue_scripts', [ $this, 'enqueueStyles' ], 999999 );

			wp_enqueue_media();
			wp_enqueue_style( 'wp-auth-check' );

			$this->doAction( 'themegrill:starter-templates:admin-page-output' );

			global $wp_locale;
			?>
			<!DOCTYPE html>
			<html lang="en">
			<head>
				<meta charset="UTF-8">
				<meta name="viewport" content="width=device-width, initial-scale=1.0">
				<title><?php esc_html_e( 'Home - PDFPress', 'pdfpress' ); ?></title>
				<script type="text/javascript">
					addLoadEvent = function(func){if(typeof jQuery!=='undefined')jQuery(function(){func();});else if(typeof wpOnload!=='function'){wpOnload=func;}else{var oldonload=wpOnload;wpOnload=function(){oldonload();func();}}};
					var ajaxurl = '<?php echo esc_js( admin_url( 'admin-ajax.php', 'relative' ) ); ?>',
						pagenow = 'ThemeGrill-Starter-templates-Page',
						typenow = 'ThemeGrill-Starter-templates-Page',
						adminpage = 'admin.php',
						thousandsSeparator = '<?php echo esc_js( $wp_locale->number_format['thousands_sep'] ); ?>',
						decimalPoint = '<?php echo esc_js( $wp_locale->number_format['decimal_point'] ); ?>',
						isRtl = <?php echo (int) is_rtl(); ?>;
				</script>
				<?php wp_head(); ?>
			</head>
			<body class="ThemeGrill-Starter-Templates-Page">
				<div id="ThemeGrill-Starter-Templates-App">
					<div id="ThemeGrill-Starter-Templates-App-Loader" style="display:grid;min-height:100svh;width:100%;place-items:center;background:white;">
						<div style="color: hsl(221 83% 53%)">
							<svg style="animation: spin 1s linear infinite;" xmlns="http://www.w3.org/2000/svg" width="50" height="50" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12a9 9 0 1 1-6.219-8.56"></path></svg>
						</div>
						<style>
							@keyframes spin {
								100% { transform: rotate(360deg); }
							}
						</style>
					</div>
				</div>
				<?php wp_footer(); ?>
			</body>
			</html>
			<?php

			$this->doAction( 'themegrill:starter-templates:admin-page-rendered' );

			exit;
		}
	}

	/**
	 * Enqueue JavaScript files for the admin page.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function enqueueScripts() {
		$this->doAction( 'themegrill:starter-templates:enqueue-scripts' );

		wp_enqueue_script( 'themegrill-starter-templates' );

		$localizeData = $this->applyFilters(
			'themegrill:starter-templates:script-data',
			[
				'adminUrl'    => admin_url(),
				'homeUrl'     => home_url(),
				'isCoreTheme' => ThemeService::isCoreTheme() ? 'yes' : 'no',
			]
		);

		wp_localize_script(
			'themegrill-starter-templates',
			'__THEMEGRILL_STARTER_TEMPLATES__',
			$localizeData
		);
		wp_set_script_translations(
			'themegrill-starter-templates',
			'themegrill-starter-templates',
			THEMEGRILL_STARTER_TEMPLATES_PLUGIN_DIR . '/languages'
		);

		$this->doAction( 'themegrill:starter-templates:scripts-enqueued', $localizeData );
	}

	/**
	 * Enqueue CSS files for the admin page.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function enqueueStyles() {
		$this->doAction( 'themegrill:starter-templates:enqueue-styles' );

		wp_enqueue_style( 'themegrill-starter-templates' );

		$this->doAction( 'themegrill:starter-templates:styles-enqueued' );
	}
}
