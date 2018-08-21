<?php
/**
 * ThemeGrill Demo Importer.
 *
 * @package ThemeGrill_Demo_Importer/Classes
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * TG_Demo_Importer Class.
 */
class TG_Demo_Importer {

	/**
	 * Demo packages.
	 *
	 * @var array
	 */
	public $demo_packages;

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'setup' ), 5 );
		add_action( 'init', array( $this, 'includes' ) );

		// Add Demo Importer menu.
		if ( apply_filters( 'themegrill_show_demo_importer_page', true ) ) {
			add_action( 'admin_menu', array( $this, 'admin_menu' ), 9 );
			add_action( 'admin_head', array( $this, 'add_menu_classes' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		}

		// Help Tabs.
		if ( apply_filters( 'themegrill_demo_importer_enable_admin_help_tab', true ) ) {
			add_action( 'current_screen', array( $this, 'add_help_tabs' ), 50 );
		}

		// Reset Wizard.
		add_action( 'wp_loaded', array( $this, 'hide_reset_notice' ) );
		add_action( 'admin_init', array( $this, 'reset_wizard_actions' ) );
		add_action( 'admin_notices', array( $this, 'reset_wizard_notice' ) );

		// Footer rating text.
		add_filter( 'admin_footer_text', array( $this, 'admin_footer_text' ), 1 );

		// Disable WooCommerce setup wizard.
		add_filter( 'woocommerce_enable_setup_wizard', '__return_false', 1 );

		// AJAX Events to query demo, import demo and update rating footer.
		add_action( 'wp_ajax_query-demos', array( $this, 'ajax_query_demos' ) );
		add_action( 'wp_ajax_import-demo', array( $this, 'ajax_import_demo' ) );
		add_action( 'wp_ajax_footer-text-rated', array( $this, 'ajax_footer_text_rated' ) );

		// Update custom nav menu items, elementor and siteorigin panel data.
		add_action( 'themegrill_ajax_demo_imported', array( $this, 'update_nav_menu_items' ) );
		add_action( 'themegrill_ajax_demo_imported', array( $this, 'update_elementor_data' ), 10, 2 );
		add_action( 'themegrill_ajax_demo_imported', array( $this, 'update_siteorigin_data' ), 10, 2 );

		// Update widget and customizer demo import settings data.
		add_filter( 'themegrill_widget_demo_import_settings', array( $this, 'update_widget_data' ), 10, 4 );
		add_filter( 'themegrill_customizer_demo_import_settings', array( $this, 'update_customizer_data' ), 10, 2 );
	}

	/**
	 * Demo importer setup.
	 */
	public function setup() {
		$this->demo_packages = $this->get_demo_packages();
	}

	/**
	 * Include required core files.
	 */
	public function includes() {
		include_once dirname( __FILE__ ) . '/importers/class-widget-importer.php';
		include_once dirname( __FILE__ ) . '/importers/class-customizer-importer.php';
	}

	/**
	 * Get demo packages.
	 *
	 * @return array of objects
	 */
	private function get_demo_packages() {
		$packages = get_transient( 'themegrill_demo_importer_packages' );
		$template = strtolower( str_replace( '-pro', '', get_option( 'template' ) ) );

		if ( false === $packages || ( isset( $packages->slug ) && $template !== $packages->slug ) ) {
			$raw_packages = wp_safe_remote_get( "https://raw.githubusercontent.com/themegrill/themegrill-demo-pack/master/configs/{$template}.json" );

			if ( ! is_wp_error( $raw_packages ) ) {
				$packages = json_decode( wp_remote_retrieve_body( $raw_packages ) );

				if ( $packages ) {
					set_transient( 'themegrill_demo_importer_packages', $packages, WEEK_IN_SECONDS );
				}
			}
		}

		return apply_filters( 'themegrill_demo_importer_packages_' . $template, $packages );
	}

	/**
	 * Get the import file path.
	 *
	 * @param  string $filename File name.
	 * @return string The import file path.
	 */
	private function get_import_file_path( $filename ) {
		return trailingslashit( TGDM_DEMO_DIR . '/dummy-data' ) . sanitize_file_name( $filename );
	}

	/**
	 * Add menu item.
	 */
	public function admin_menu() {
		add_theme_page( __( 'Demo Importer', 'themegrill-demo-importer' ), __( 'Demo Importer', 'themegrill-demo-importer' ), 'switch_themes', 'demo-importer', array( $this, 'demo_importer' ) );
	}

	/**
	 * Adds the class to the menu.
	 */
	public function add_menu_classes() {
		global $submenu;

		if ( isset( $submenu['themes.php'] ) ) {
			$submenu_class = 'demo-importer hide-if-no-js';

			// Add menu classes if user has access.
			if ( apply_filters( 'themegrill_demo_importer_include_class_in_menu', true ) ) {
				foreach ( $submenu['themes.php'] as $order => $menu_item ) {
					if ( 0 === strpos( $menu_item[0], _x( 'Demo Importer', 'Admin menu name', 'themegrill-demo-importer' ) ) ) {
						$submenu['themes.php'][ $order ][4] = empty( $menu_item[4] ) ? $submenu_class : $menu_item[4] . ' ' . $submenu_class;
						break;
					}
				}
			}
		}
	}

	/**
	 * Enqueue scripts.
	 */
	public function enqueue_scripts() {
		$screen      = get_current_screen();
		$screen_id   = $screen ? $screen->id : '';
		$suffix      = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		$assets_path = tgdm()->plugin_url() . '/assets/';

		// Register admin styles.
		wp_register_style( 'tg-demo-importer', $assets_path . 'css/demo-importer.css', array(), TGDM_VERSION );

		// Add RTL support for admin styles.
		wp_style_add_data( 'tg-demo-importer', 'rtl', 'replace' );

		// Register admin scripts.
		wp_register_script( 'jquery-tiptip', $assets_path . 'js/jquery-tiptip/jquery.tipTip' . $suffix . '.js', array( 'jquery' ), '1.3', true );
		wp_register_script( 'tg-demo-updates', $assets_path . 'js/admin/demo-updates' . $suffix . '.js', array( 'jquery', 'updates' ), TGDM_VERSION, true );
		wp_register_script( 'tg-demo-importer', $assets_path . 'js/admin/demo-importer' . $suffix . '.js', array( 'jquery', 'jquery-tiptip', 'wp-backbone', 'wp-a11y', 'tg-demo-updates' ), TGDM_VERSION, true );

		// Demo Importer appearance page.
		if ( 'appearance_page_demo-importer' === $screen_id ) {
			wp_enqueue_style( 'tg-demo-importer' );
			wp_enqueue_script( 'tg-demo-importer' );
			wp_localize_script( 'tg-demo-updates', '_demoUpdatesSettings', array(
				'l10n' => array(
					'importing'             => __( 'Importing...', 'themegrill-demo-importer' ),
					'demoImportingLabel'    => _x( 'Importing %s...', 'demo', 'themegrill-demo-importer' ), // no ellipsis
					'importingMsg'          => __( 'Importing... please wait.', 'themegrill-demo-importer' ),
					'importedMsg'           => __( 'Import completed successfully.', 'themegrill-demo-importer' ),
					'importFailedShort'     => __( 'Import Failed!', 'themegrill-demo-importer' ),
					'importFailed'          => __( 'Import failed: %s', 'themegrill-demo-importer' ),
					'demoImportedLabel'     => _x( '%s imported!', 'demo', 'themegrill-demo-importer' ),
					'demoImportFailedLabel' => _x( '%s import failed', 'demo', 'themegrill-demo-importer' ),
					'livePreview'           => __( 'Live Preview', 'themegrill-demo-importer' ),
					'livePreviewLabel'      => _x( 'Live Preview %s', 'demo', 'themegrill-demo-importer' ),
					'imported'              => __( 'Imported!', 'themegrill-demo-importer' ),
					'statusTextLink'        => '<a href="https://docs.themegrill.com/knowledgebase/demo-import-process-failed/" target="_blank">' . __( 'Try this solution!', 'themegrill-demo-importer' ) . '</a>',
				),
			) );
			wp_localize_script( 'tg-demo-importer', '_demoImporterSettings', array(
				'demos'    => $this->ajax_query_demos( true ),
				'settings' => array(
					'isNew'          => false,
					'ajaxUrl'        => admin_url( 'admin-ajax.php' ),
					'adminUrl'       => parse_url( self_admin_url(), PHP_URL_PATH ),
					'suggestURI'     => apply_filters( 'themegrill_demo_importer_suggest_new', 'https://themegrill.com/contact/' ),
					'confirmReset'   => __( 'It is strongly recommended that you backup your database before proceeding. Are you sure you wish to run the reset wizard now?', 'themegrill-demo-importer' ),
					'confirmImport'  => __( "Importing demo data will ensure that your site will look similar as theme demo. It makes you easy to modify the content instead of creating them from scratch. Also consider before importing theme demo: \n\n1. You need to import demo on fresh WordPress install to exactly replicate the theme demo. \n\n2. None of the posts, pages, attachments or any other data already existing in your site will be deleted or modified. \n\n3. Copyright images will get replaced with other placeholder images. \n\n4. It will take some time to import the theme demo.", 'themegrill-demo-importer' ),
				),
				'l10n' => array(
					'search'              => __( 'Search Demos', 'themegrill-demo-importer' ),
					'searchPlaceholder'   => __( 'Search demos...', 'themegrill-demo-importer' ), // placeholder (no ellipsis)
					/* translators: %s: support forums URL */
					'error'               => sprintf( __( 'An unexpected error occurred. Something may be wrong with ThemeGrill demo server&#8217;s configuration. If you continue to have problems, please try the <a href="%s">support forums</a>.', 'themegrill-demo-importer' ), 'https://wordpress.org/support/plugin/themegrill-demo-importer' ),
					'tryAgain'            => __( 'Try Again', 'themegrill-demo-importer' ),
					'suggestNew'          => __( 'Please suggest us!', 'themegrill-demo-importer' ),
					'demosFound'          => __( 'Number of Demos found: %d', 'themegrill-demo-importer' ),
					'noDemosFound'        => __( 'No demos found. Try a different search.', 'themegrill-demo-importer' ),
					'collapseSidebar'     => __( 'Collapse Sidebar', 'themegrill-demo-importer' ),
					'expandSidebar'       => __( 'Expand Sidebar', 'themegrill-demo-importer' ),
					/* translators: accessibility text */
					'selectFeatureFilter' => __( 'Select one or more Demo features to filter by', 'themegrill-demo-importer' ),
				),
			) );
		}
	}

	/**
	 * Change the admin footer text.
	 *
	 * @param  string $footer_text
	 * @return string
	 */
	public function admin_footer_text( $footer_text ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return $footer_text;
		}

		$current_screen = get_current_screen();

		// Check to make sure we're on a ThemeGrill Demo Importer admin page.
		if ( isset( $current_screen->id ) && apply_filters( 'themegrill_demo_importer_display_admin_footer_text', in_array( $current_screen->id, array( 'appearance_page_demo-importer' ) ) ) ) {
			// Change the footer text.
			if ( ! get_option( 'themegrill_demo_importer_admin_footer_text_rated' ) ) {
				$footer_text = sprintf(
					/* translators: 1: ThemeGrill Demo Importer 2: five stars */
					__( 'If you like %1$s please leave us a %2$s rating. A huge thanks in advance!', 'themegrill-demo-importer' ),
					sprintf( '<strong>%s</strong>', esc_html__( 'ThemeGrill Demo Importer', 'themegrill-demo-importer' ) ),
					'<a href="https://wordpress.org/support/plugin/themegrill-demo-importer/reviews?rate=5#new-post" target="_blank" class="themegrill-demo-importer-rating-link" data-rated="' . esc_attr__( 'Thanks :)', 'themegrill-demo-importer' ) . '">&#9733;&#9733;&#9733;&#9733;&#9733;</a>'
				);
			} else {
				$footer_text = __( 'Thank you for importing with ThemeGrill Demo Importer.', 'themegrill-demo-importer' );
			}
		}

		return $footer_text;
	}

	/**
	 * Add Contextual help tabs.
	 */
	public function add_help_tabs() {
		$screen = get_current_screen();

		if ( ! $screen || ! in_array( $screen->id, array( 'appearance_page_demo-importer' ) ) ) {
			return;
		}

		$screen->add_help_tab( array(
			'id'        => 'themegrill_demo_importer_support_tab',
			'title'     => __( 'Help &amp; Support', 'themegrill-demo-importer' ),
			'content'   =>
				'<h2>' . __( 'Help &amp; Support', 'themegrill-demo-importer' ) . '</h2>' .
				'<p>' . sprintf(
					__( 'Should you need help understanding, using, or extending ThemeGrill Demo Importer, <a href="%s">please read our documentation</a>. You will find all kinds of resources including snippets, tutorials and much more.' , 'themegrill-demo-importer' ),
					'https://themegrill.com/docs/themegrill-demo-importer/'
				) . '</p>' .
				'<p>' . sprintf(
					__( 'For further assistance with ThemeGrill Demo Importer core you can use the <a href="%1$s">community forum</a>. If you need help with premium themes sold by ThemeGrill, please <a href="%2$s">use our free support forum</a>.', 'themegrill-demo-importer' ),
					'https://wordpress.org/support/plugin/themegrill-demo-importer',
					'https://themegrill.com/support-forum/'
				) . '</p>' .
				'<p><a href="' . 'https://wordpress.org/support/plugin/themegrill-demo-importer' . '" class="button button-primary">' . __( 'Community forum', 'themegrill-demo-importer' ) . '</a> <a href="' . 'https://themegrill.com/support-forum/' . '" class="button">' . __( 'ThemeGrill Support', 'themegrill-demo-importer' ) . '</a></p>',
		) );

		$screen->add_help_tab( array(
			'id'        => 'themegrill_demo_importer_bugs_tab',
			'title'     => __( 'Found a bug?', 'themegrill-demo-importer' ),
			'content'   =>
				'<h2>' . __( 'Found a bug?', 'themegrill-demo-importer' ) . '</h2>' .
				'<p>' . sprintf( __( 'If you find a bug within ThemeGrill Demo Importer you can create a ticket via <a href="%1$s">Github issues</a>. Ensure you read the <a href="%2$s">contribution guide</a> prior to submitting your report. To help us solve your issue, please be as descriptive as possible.', 'themegrill-demo-importer' ), 'https://github.com/themegrill/themegrill-demo-importer/issues?state=open', 'https://github.com/themegrill/themegrill-demo-importer/blob/master/.github/CONTRIBUTING.md' ) . '</p>' .
				'<p><a href="' . 'https://github.com/themegrill/themegrill-demo-importer/issues?state=open' . '" class="button button-primary">' . __( 'Report a bug', 'themegrill-demo-importer' ) . '</a></p>',

		) );

		$screen->add_help_tab( array(
			'id'        => 'themegrill_demo_importer_reset_tab',
			'title'     => __( 'Reset wizard', 'themegrill-demo-importer' ),
			'content'   =>
				'<h2>' . __( 'Reset wizard', 'themegrill-demo-importer' ) . '</h2>' .
				'<p>' . __( 'If you need to reset the WordPress back to default again, please click on the button below.', 'themegrill-demo-importer' ) . '</p>' .
				'<p><a href="' . esc_url( add_query_arg( 'do_reset_wordpress', 'true', admin_url( 'themes.php?page=demo-importer' ) ) ) . '" class="button button-primary themegrill-reset-wordpress">' . __( 'Reset wizard', 'themegrill-demo-importer' ) . '</a></p>',
		) );

		$screen->set_help_sidebar(
			'<p><strong>' . __( 'For more information:', 'themegrill-demo-importer' ) . '</strong></p>' .
			'<p><a href="' . 'https://themegrill.com/demo-importer/' . '" target="_blank">' . __( 'About Demo Importer', 'themegrill-demo-importer' ) . '</a></p>' .
			'<p><a href="' . 'https://wordpress.org/plugins/themegrill-demo-importer/' . '" target="_blank">' . __( 'WordPress.org project', 'themegrill-demo-importer' ) . '</a></p>' .
			'<p><a href="' . 'https://github.com/themegrill/themegrill-demo-importer' . '" target="_blank">' . __( 'Github project', 'themegrill-demo-importer' ) . '</a></p>' .
			'<p><a href="' . 'https://themegrill.com/wordpress-themes/' . '" target="_blank">' . __( 'Official themes', 'themegrill-demo-importer' ) . '</a></p>' .
			'<p><a href="' . 'https://themegrill.com/plugins/' . '" target="_blank">' . __( 'Official plugins', 'themegrill-demo-importer' ) . '</a></p>'
		);
	}

	/**
	 * Reset wizard notice.
	 */
	public function reset_wizard_notice() {
		$screen              = get_current_screen();
		$demo_activated_id   = get_option( 'themegrill_demo_importer_activated_id' );
		$demo_notice_dismiss = get_option( 'themegrill_demo_importer_reset_notice' );

		if ( ! $screen || ! in_array( $screen->id, array( 'appearance_page_demo-importer' ) ) ) {
			return;
		}

		// Output reset wizard notice.
		if ( ! $demo_notice_dismiss && $demo_activated_id ) {
			include_once dirname( __FILE__ ) . '/admin/views/html-notice-reset-wizard.php';
		} elseif ( isset( $_GET['reset'] ) && 'true' === $_GET['reset'] ) {
			include_once dirname( __FILE__ ) . '/admin/views/html-notice-reset-wizard-success.php';
		}
	}

	/**
	 * Hide a notice if the GET variable is set.
	 */
	public function hide_reset_notice() {
		if ( isset( $_GET['themegrill-demo-importer-hide-notice'] ) && isset( $_GET['_themegrill_demo_importer_notice_nonce'] ) ) {
			if ( ! wp_verify_nonce( $_GET['_themegrill_demo_importer_notice_nonce'], 'themegrill_demo_importer_hide_notice_nonce' ) ) {
				wp_die( __( 'Action failed. Please refresh the page and retry.', 'themegrill-demo-importer' ) );
			}

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( __( 'Cheatin&#8217; huh?', 'themegrill-demo-importer' ) );
			}

			$hide_notice = sanitize_text_field( $_GET['themegrill-demo-importer-hide-notice'] );

			if ( ! empty( $hide_notice ) && 'reset_notice' == $hide_notice ) {
				update_option( 'themegrill_demo_importer_reset_notice', 1 );
			}
		}
	}

	/**
	 * Reset actions when a reset button is clicked.
	 */
	public function reset_wizard_actions() {
		global $wpdb, $current_user;

		if ( ! empty( $_GET['do_reset_wordpress'] ) ) {
			require_once ABSPATH . '/wp-admin/includes/upgrade.php';

			$template     = get_option( 'template' );
			$blogname     = get_option( 'blogname' );
			$admin_email  = get_option( 'admin_email' );
			$blog_public  = get_option( 'blog_public' );
			$footer_rated = get_option( 'themegrill_demo_importer_admin_footer_text_rated' );

			if ( 'admin' != $current_user->user_login ) {
				$user = get_user_by( 'login', 'admin' );
			}

			if ( empty( $user->user_level ) || $user->user_level < 10 ) {
				$user = $current_user;
			}

			// Drop tables.
			$drop_tables = $wpdb->get_col( sprintf( "SHOW TABLES LIKE '%s%%'", str_replace( '_', '\_', $wpdb->prefix ) ) );
			foreach ( $drop_tables as $table ) {
				$wpdb->query( "DROP TABLE IF EXISTS $table" );
			}

			// Installs the site.
			$result = wp_install( $blogname, $user->user_login, $user->user_email, $blog_public );

			// Updates the user password with a old one.
			$wpdb->update( $wpdb->users, array( 'user_pass' => $user->user_pass, 'user_activation_key' => '' ), array( 'ID' => $result['user_id'] ) );

			// Set up the Password change nag.
			$default_password_nag = get_user_option( 'default_password_nag', $result['user_id'] );
			if ( $default_password_nag ) {
				update_user_option( $result['user_id'], 'default_password_nag', false, true );
			}

			// Update footer text.
			if ( $footer_rated ) {
				update_option( 'themegrill_demo_importer_admin_footer_text_rated', $footer_rated );
			}

			// Switch current theme.
			$current_theme = wp_get_theme( $template );
			if ( $current_theme->exists() ) {
				switch_theme( $template );
			}

			// Activate required plugins.
			$required_plugins = (array) apply_filters( 'themegrill_demo_importer_' . $template . '_required_plugins', array() );
			if ( is_array( $required_plugins ) ) {
				if ( ! in_array( TGDM_PLUGIN_BASENAME, $required_plugins ) ) {
					$required_plugins = array_merge( $required_plugins, array( TGDM_PLUGIN_BASENAME ) );
				}
				activate_plugins( $required_plugins, '', is_network_admin(), true );
			}

			// Update the cookies.
			wp_clear_auth_cookie();
			wp_set_auth_cookie( $result['user_id'] );

			// Redirect to demo importer page to display reset success notice.
			wp_safe_redirect( admin_url( 'themes.php?page=demo-importer&browse=all&reset=true' ) );
			exit();
		}
	}

	/**
	 * Demo Importer page output.
	 */
	public function demo_importer() {
		include_once dirname( __FILE__ ) . '/admin/views/html-admin-page-importer.php';
	}

	/**
	 * Ajax handler for getting demos from github.
	 */
	public function ajax_query_demos( $return = true ) {
		$prepared_demos     = array();
		$current_template   = get_option( 'template' );
		$is_pro_theme_demo  = strpos( $current_template, '-pro' ) !== false;
		$demo_activated_id  = get_option( 'themegrill_demo_importer_activated_id' );
		$available_packages = $this->demo_packages;

		/**
		 * Filters demo data before it is prepared for JavaScript.
		 *
		 * @param array      $prepared_demos     An associative array of demo data. Default empty array.
		 * @param null|array $available_packages An array of demo package config to prepare, if any.
		 * @param string     $demo_activated_id  The current demo activated id.
		 */
		$prepared_demos = (array) apply_filters( 'themegrill_demo_importer_pre_prepare_demos_for_js', array(), $available_packages, $demo_activated_id );

		if ( ! empty( $prepared_demos ) ) {
			return $prepared_demos;
		}

		if ( ! $return ) {
			$request = wp_parse_args( wp_unslash( $_REQUEST['request'] ), array(
				'browse' => 'all',
			) );
		} else {
			$request = array(
				'browse' => 'all',
			);
		}

		if ( isset( $available_packages->demos ) ) {
			foreach ( $available_packages->demos as $package_slug => $package_data ) {
				$plugins_list   = isset( $package_data->plugins_list ) ? $package_data->plugins_list : array();
				$screenshot_url = "https://raw.githubusercontent.com/themegrill/themegrill-demo-pack/master/resources/{$available_packages->slug}/{$package_slug}/screenshot.jpg";

				if ( isset( $request['browse'], $package_data->category ) && ! in_array( $request['browse'], $package_data->category, true ) ) {
					continue;
				}

				if ( isset( $request['builder'], $package_data->pagebuilder ) && ! in_array( $request['builder'], $package_data->pagebuilder, true ) ) {
					continue;
				}

				// Plugins status.
				foreach ( $plugins_list as $plugin => $plugin_data ) {
					$plugin_data->is_active = is_plugin_active( $plugin_data->slug );

					// Looks like a plugin is installed, but not active.
					if ( file_exists( WP_PLUGIN_DIR . '/' . $plugin ) ) {
						$plugins = get_plugins( '/' . $plugin );
						if ( ! empty( $plugins ) ) {
							$plugin_data->is_install = true;
						}
					} else {
						$plugin_data->is_install = false;
					}
				}

				// Prepare all demos.
				$prepared_demos[ $package_slug ] = array(
					'slug'            => $package_slug,
					'name'            => $package_data->title,
					'theme'           => $is_pro_theme_demo ? sprintf( esc_html__( '%s Pro', 'themegrill-demo-importer' ), $available_packages->name ) : $available_packages->name,
					'isPro'           => $is_pro_theme_demo ? false : isset( $package_data->isPro ),
					'active'          => $package_slug === $demo_activated_id,
					'author'          => isset( $package_data->author ) ? $package_data->author : __( 'ThemeGrill', 'themegrill-demo-importer' ),
					'version'         => isset( $package_data->version ) ? $package_data->version : $available_packages->version,
					'description'     => isset( $package_data->description ) ? $package_data->description : '',
					'homepage'        => $available_packages->homepage,
					'preview_url'     => set_url_scheme( $package_data->preview ),
					'screenshot_url'  => $screenshot_url,
					'plugins'         => $plugins_list,
					'requiredTheme'   => isset( $package_data->template ) && ! in_array( $current_template, $package_data->template, true ),
					'requiredPlugins' => wp_list_filter( json_decode( wp_json_encode( $plugins_list ), true ), array( 'is_active' => false ) ) ? true : false,
				);
			}
		}

		/**
		 * Filters the demos prepared for JavaScript.
		 *
		 * Could be useful for changing the order, which is by name by default.
		 *
		 * @param array $prepared_demos Array of demos.
		 */
		$prepared_demos = apply_filters( 'themegrill_demo_importer_prepare_demos_for_js', $prepared_demos );
		$prepared_demos = array_values( $prepared_demos );

		if ( $return ) {
			return $prepared_demos;
		}

		wp_send_json_success( array(
			'info' => array(
				'page'    => 1,
				'pages'   => 1,
				'results' => count( $prepared_demos ),
			),
			'demos' => array_filter( $prepared_demos ),
		) );
	}

	/**
	 * Ajax handler for importing a demo.
	 *
	 * @see TG_Demo_Upgrader
	 *
	 * @global WP_Filesystem_Base $wp_filesystem Subclass
	 */
	public function ajax_import_demo() {
		check_ajax_referer( 'updates' );

		if ( empty( $_POST['slug'] ) ) {
			wp_send_json_error( array(
				'slug'         => '',
				'errorCode'    => 'no_demo_specified',
				'errorMessage' => __( 'No demo specified.', 'themegrill-demo-importer' ),
			) );
		}

		$slug   = sanitize_key( wp_unslash( $_POST['slug'] ) );
		$status = array(
			'import' => 'demo',
			'slug'   => $slug,
		);

		if ( ! defined( 'WP_LOAD_IMPORTERS' ) ) {
			define( 'WP_LOAD_IMPORTERS', true );
		}

		if ( ! current_user_can( 'import' ) ) {
			$status['errorMessage'] = __( 'Sorry, you are not allowed to import content.', 'themegrill-demo-importer' );
			wp_send_json_error( $status );
		}

		include_once( ABSPATH . 'wp-admin/includes/class-wp-upgrader.php' );
		include_once( dirname( __FILE__ ) . '/admin/class-demo-pack-upgrader.php' );

		$skin     = new WP_Ajax_Upgrader_Skin();
		$upgrader = new TG_Demo_Pack_Upgrader( $skin );
		$template = strtolower( str_replace( '-pro', '', get_option( 'template' ) ) );
		$packages = isset( $this->demo_packages->demos ) ? json_decode( wp_json_encode( $this->demo_packages->demos ), true ) : array();
		$result   = $upgrader->install( "https://github.com/themegrill/themegrill-demo-pack/raw/master/packages/{$template}/{$slug}.zip" );

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			$status['debug'] = $skin->get_upgrade_messages();
		}

		if ( is_wp_error( $result ) ) {
			$status['errorCode']    = $result->get_error_code();
			$status['errorMessage'] = $result->get_error_message();
			wp_send_json_error( $status );
		} elseif ( is_wp_error( $skin->result ) ) {
			$status['errorCode']    = $skin->result->get_error_code();
			$status['errorMessage'] = $skin->result->get_error_message();
			wp_send_json_error( $status );
		} elseif ( $skin->get_errors()->get_error_code() ) {
			$status['errorMessage'] = $skin->get_error_messages();
			wp_send_json_error( $status );
		} elseif ( is_null( $result ) ) {
			global $wp_filesystem;

			$status['errorCode']    = 'unable_to_connect_to_filesystem';
			$status['errorMessage'] = __( 'Unable to connect to the filesystem. Please confirm your credentials.', 'themegrill-demo-importer' );

			// Pass through the error from WP_Filesystem if one was raised.
			if ( $wp_filesystem instanceof WP_Filesystem_Base && is_wp_error( $wp_filesystem->errors ) && $wp_filesystem->errors->get_error_code() ) {
				$status['errorMessage'] = esc_html( $wp_filesystem->errors->get_error_message() );
			}

			wp_send_json_error( $status );
		}

		$demo_data            = $packages[ $slug ];
		$status['demoName']   = $demo_data['title'];
		$status['previewUrl'] = get_home_url( '/' );

		do_action( 'themegrill_ajax_before_demo_import' );

		if ( ! empty( $demo_data ) ) {
			$this->import_dummy_xml( $slug, $demo_data, $status );
			$this->import_core_options( $slug, $demo_data );
			$this->import_customizer_data( $slug, $demo_data, $status );
			$this->import_widget_settings( $slug, $demo_data, $status );

			// Update imported demo ID.
			update_option( 'themegrill_demo_importer_activated_id', $slug );

			do_action( 'themegrill_ajax_demo_imported', $slug, $demo_data );
		}

		wp_send_json_success( $status );
	}

	/**
	 * Triggered when clicking the rating footer.
	 */
	public function ajax_footer_text_rated() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( -1 );
		}

		update_option( 'themegrill_demo_importer_admin_footer_text_rated', 1 );
		wp_die();
	}

	/**
	 * Import dummy content from a XML file.
	 *
	 * @param  string $demo_id
	 * @param  array  $demo_data
	 * @param  array  $status
	 * @return bool
	 */
	public function import_dummy_xml( $demo_id, $demo_data, $status ) {
		$import_file = $this->get_import_file_path( 'dummy-data.xml' );

		// Load Importer API.
		require_once ABSPATH . 'wp-admin/includes/import.php';

		if ( ! class_exists( 'WP_Importer' ) ) {
			$class_wp_importer = ABSPATH . 'wp-admin/includes/class-wp-importer.php';

			if ( file_exists( $class_wp_importer ) ) {
				require $class_wp_importer;
			}
		}

		// Include WXR Importer.
		require dirname( __FILE__ ) . '/importers/wordpress-importer/class-wxr-importer.php';

		do_action( 'themegrill_ajax_before_dummy_xml_import', $demo_data, $demo_id );

		// Import XML file demo content.
		if ( is_file( $import_file ) ) {
			$wp_import = new TG_WXR_Importer();
			$wp_import->fetch_attachments = true;

			ob_start();
			$wp_import->import( $import_file );
			ob_end_clean();

			do_action( 'themegrill_ajax_dummy_xml_imported', $demo_data, $demo_id );

			flush_rewrite_rules();
		} else {
			$status['errorMessage'] = __( 'The XML file dummy content is missing.', 'themegrill-demo-importer' );
			wp_send_json_error( $status );
		}

		return true;
	}

	/**
	 * Import site core options from its ID.
	 *
	 * @param  string $demo_id
	 * @param  array  $demo_data
	 * @return bool
	 */
	public function import_core_options( $demo_id, $demo_data ) {
		if ( ! empty( $demo_data['core_options'] ) ) {
			foreach ( $demo_data['core_options'] as $option_key => $option_value ) {
				if ( ! in_array( $option_key, array( 'blogname', 'blogdescription', 'show_on_front', 'page_on_front', 'page_for_posts' ) ) ) {
					continue;
				}

				// Format the value based on option key.
				switch ( $option_key ) {
					case 'show_on_front':
						if ( in_array( $option_value, array( 'posts', 'page' ) ) ) {
							update_option( 'show_on_front', $option_value );
						}
					break;
					case 'page_on_front':
					case 'page_for_posts':
						$page = get_page_by_title( $option_value );

						if ( is_object( $page ) && $page->ID ) {
							update_option( $option_key, $page->ID );
							update_option( 'show_on_front', 'page' );
						}
					break;
					default:
						update_option( $option_key, sanitize_text_field( $option_value ) );
					break;
				}
			}
		}

		return true;
	}

	/**
	 * Import customizer data from a DAT file.
	 *
	 * @param  string $demo_id
	 * @param  array  $demo_data
	 * @param  array  $status
	 * @return bool
	 */
	public function import_customizer_data( $demo_id, $demo_data, $status ) {
		$import_file = $this->get_import_file_path( 'dummy-customizer.dat' );

		if ( is_file( $import_file ) ) {
			$results = TG_Customizer_Importer::import( $import_file, $demo_id, $demo_data );

			if ( is_wp_error( $results ) ) {
				return false;
			}
		} else {
			$status['errorMessage'] = __( 'The DAT file customizer data is missing.', 'themegrill-demo-importer' );
			wp_send_json_error( $status );
		}

		return true;
	}

	/**
	 * Import widgets settings from WIE or JSON file.
	 *
	 * @param  string $demo_id
	 * @param  array  $demo_data
	 * @param  array  $status
	 * @return bool
	 */
	public function import_widget_settings( $demo_id, $demo_data, $status ) {
		$import_file = $this->get_import_file_path( 'dummy-widgets.wie' );

		if ( is_file( $import_file ) ) {
			$results = TG_Widget_Importer::import( $import_file, $demo_id, $demo_data );

			if ( is_wp_error( $results ) ) {
				return false;
			}
		} else {
			$status['errorMessage'] = __( 'The WIE file widget content is missing.', 'themegrill-demo-importer' );
			wp_send_json_error( $status );
		}

		return true;
	}

	/**
	 * Update custom nav menu items URL.
	 */
	public function update_nav_menu_items() {
		$menu_locations = get_nav_menu_locations();

		foreach ( $menu_locations as $location => $menu_id ) {

			if ( is_nav_menu( $menu_id ) ) {
				$menu_items = wp_get_nav_menu_items( $menu_id, array( 'post_status' => 'any' ) );

				if ( ! empty( $menu_items ) ) {
					foreach ( $menu_items as $menu_item ) {
						if ( isset( $menu_item->url ) && isset( $menu_item->db_id ) && 'custom' == $menu_item->type ) {
							$site_parts = parse_url( home_url( '/' ) );
							$menu_parts = parse_url( $menu_item->url );

							// Update existing custom nav menu item URL.
							if ( isset( $menu_parts['path'] ) && isset( $menu_parts['host'] ) && apply_filters( 'themegrill_demo_importer_nav_menu_item_url_hosts', in_array( $menu_parts['host'], array( 'demo.themegrill.com' ) ) ) ) {
								$menu_item->url = str_replace( array( $menu_parts['scheme'], $menu_parts['host'], $menu_parts['path'] ), array( $site_parts['scheme'], $site_parts['host'], trailingslashit( $site_parts['path'] ) ), $menu_item->url );
								update_post_meta( $menu_item->db_id, '_menu_item_url', esc_url_raw( $menu_item->url ) );
							}
						}
					}
				}
			}
		}
	}

	/**
	 * Updates widgets settings data.
	 *
	 * @param  array  $widget
	 * @param  string $widget_type
	 * @param  int    $instance_id
	 * @param  array  $demo_data
	 * @return array
	 */
	public function update_widget_data( $widget, $widget_type, $instance_id, $demo_data ) {
		if ( 'nav_menu' == $widget_type ) {
			$nav_menu = wp_get_nav_menu_object( $widget['title'] );

			if ( is_object( $nav_menu ) && $nav_menu->term_id ) {
				$widget['nav_menu'] = $nav_menu->term_id;
			}
		} elseif ( ! empty( $demo_data['widgets_data_update'] ) ) {
			foreach ( $demo_data['widgets_data_update'] as $dropdown_type => $dropdown_data ) {
				if ( ! in_array( $dropdown_type, array( 'dropdown_pages', 'dropdown_categories' ) ) ) {
					continue;
				}

				// Format the value based on dropdown type.
				switch ( $dropdown_type ) {
					case 'dropdown_pages':
						foreach ( $dropdown_data as $widget_id => $widget_data ) {
							if ( ! empty( $widget_data[ $instance_id ] ) && $widget_id == $widget_type ) {
								foreach ( $widget_data[ $instance_id ] as $widget_key => $widget_value ) {
									$page = get_page_by_title( $widget_value );

									if ( is_object( $page ) && $page->ID ) {
										$widget[ $widget_key ] = $page->ID;
									}
								}
							}
						}
					break;
					case 'dropdown_categories':
						foreach ( $dropdown_data as $taxonomy => $taxonomy_data ) {
							if ( ! taxonomy_exists( $taxonomy ) ) {
								continue;
							}

							foreach ( $taxonomy_data as $widget_id => $widget_data ) {
								if ( ! empty( $widget_data[ $instance_id ] ) && $widget_id == $widget_type ) {
									foreach ( $widget_data[ $instance_id ] as $widget_key => $widget_value ) {
										$term = get_term_by( 'name', $widget_value, $taxonomy );

										if ( is_object( $term ) && $term->term_id ) {
											$widget[ $widget_key ] = $term->term_id;
										}
									}
								}
							}
						}
					break;
				}
			}
		}

		return $widget;
	}

	/**
	 * Update customizer settings data.
	 *
	 * @param  array $data
	 * @param  array $demo_data
	 * @return array
	 */
	public function update_customizer_data( $data, $demo_data ) {
		if ( ! empty( $demo_data['customizer_data_update'] ) ) {
			foreach ( $demo_data['customizer_data_update'] as $data_type => $data_value ) {
				if ( ! in_array( $data_type, array( 'pages', 'categories', 'nav_menu_locations' ) ) ) {
					continue;
				}

				// Format the value based on data type.
				switch ( $data_type ) {
					case 'pages':
						foreach ( $data_value as $option_key => $option_value ) {
							if ( ! empty( $data['mods'][ $option_key ] ) ) {
								$page = get_page_by_title( $option_value );

								if ( is_object( $page ) && $page->ID ) {
									$data['mods'][ $option_key ] = $page->ID;
								}
							}
						}
					break;
					case 'categories':
						foreach ( $data_value as $taxonomy => $taxonomy_data ) {
							if ( ! taxonomy_exists( $taxonomy ) ) {
								continue;
							}

							foreach ( $taxonomy_data as $option_key => $option_value ) {
								if ( ! empty( $data['mods'][ $option_key ] ) ) {
									$term = get_term_by( 'name', $option_value, $taxonomy );

									if ( is_object( $term ) && $term->term_id ) {
										$data['mods'][ $option_key ] = $term->term_id;
									}
								}
							}
						}
					break;
					case 'nav_menu_locations':
						$nav_menus = wp_get_nav_menus();

						if ( ! empty( $nav_menus ) ) {
							foreach ( $nav_menus as $nav_menu ) {
								if ( is_object( $nav_menu ) ) {
									foreach ( $data_value as $location => $location_name ) {
										if ( $nav_menu->name == $location_name ) {
											$data['mods'][ $data_type ][ $location ] = $nav_menu->term_id;
										}
									}
								}
							}
						}
					break;
				}
			}
		}

		return $data;
	}

	/**
	 * Recursive function to address n level deep elementor data update.
	 *
	 * @param  array  $elementor_data
	 * @param  string $data_type
	 * @param  array  $data_value
	 * @return array
	 */
	public function elementor_recursive_update( $elementor_data, $data_type, $data_value ) {
		$elementor_data = json_decode( stripslashes( $elementor_data ), true );

		// Recursively update elementor data.
		foreach ( $elementor_data as $element_id => $element_data ) {
			if ( ! empty( $element_data['elements' ] ) ) {
				foreach ( $element_data['elements'] as $el_key => $el_data ) {
					if ( ! empty( $el_data['elements'] ) ) {
						foreach ( $el_data['elements'] as $el_child_key => $child_el_data ) {
							if ( 'widget' === $child_el_data['elType'] ) {
								$settings   = isset( $child_el_data['settings'] ) ? $child_el_data['settings'] : array();
								$widgetType = isset( $child_el_data['widgetType'] ) ? $child_el_data['widgetType'] : '';

								if ( isset( $settings['display_type'] ) && 'categories' === $settings['display_type'] ) {
									$categories_selected = isset( $settings['categories_selected'] ) ? $settings['categories_selected'] : '';

									if ( ! empty( $data_value['data_update'] ) ) {
										foreach ( $data_value['data_update'] as $taxonomy => $taxonomy_data ) {
											if ( ! taxonomy_exists( $taxonomy ) ) {
												continue;
											}

											foreach ( $taxonomy_data as $widget_id => $widget_data ) {
												if ( ! empty( $widget_data ) && $widget_id == $widgetType ) {
													if ( is_array( $categories_selected ) ) {
														foreach ( $categories_selected as $cat_key => $cat_id ) {
															if ( isset( $widget_data[ $cat_id ] ) ) {
																$term = get_term_by( 'name', $widget_data[ $cat_id ], $taxonomy );

																if ( is_object( $term ) && $term->term_id ) {
																	$categories_selected[ $cat_key ] = $term->term_id;
																}
															}
														}
													} elseif ( isset( $widget_data[ $categories_selected ] ) ) {
														$term = get_term_by( 'name', $widget_data[ $categories_selected ], $taxonomy );

														if ( is_object( $term ) && $term->term_id ) {
															$categories_selected = $term->term_id;
														}
													}
												}
											}
										}
									}

									// Update the elementor data.
									$elementor_data[ $element_id ][ 'elements' ][ $el_key ]['elements'][ $el_child_key ]['settings']['categories_selected'] = $categories_selected;
								}
							}
						}
					}
				}
			}
		}

		return wp_json_encode( $elementor_data );
	}

	/**
	 * Update elementor settings data.
	 *
	 * @param string $demo_id Demo ID.
	 * @param array  $demo_data Demo Data.
	 */
	public function update_elementor_data( $demo_id, $demo_data ) {
		if ( ! empty( $demo_data['elementor_data_update'] ) ) {
			foreach ( $demo_data['elementor_data_update'] as $data_type => $data_value ) {
				if ( ! empty( $data_value['post_title'] ) ) {
					$page = get_page_by_title( $data_value['post_title'] );

					if ( is_object( $page ) && $page->ID ) {
						$elementor_data = get_post_meta( $page->ID, '_elementor_data', true );

						if ( ! empty( $elementor_data ) ) {
							$elementor_data = $this->elementor_recursive_update( $elementor_data, $data_type, $data_value );
						}

						// Update elementor data.
						update_post_meta( $page->ID, '_elementor_data', $elementor_data );
					}
				}
			}
		}
	}

	/**
	 * Recursive function to address n level deep layoutbuilder data update.
	 *
	 * @param  array  $panels_data
	 * @param  string $data_type
	 * @param  array  $data_value
	 * @return array
	 */
	public function siteorigin_recursive_update( $panels_data, $data_type, $data_value ) {
		static $instance = 0;

		foreach ( $panels_data as $panel_type => $panel_data ) {
			// Format the value based on panel type.
			switch ( $panel_type ) {
				case 'grids':
					foreach ( $panel_data as $instance_id => $grid_instance ) {
						if ( ! empty( $data_value['data_update']['grids_data'] ) ) {
							foreach ( $data_value['data_update']['grids_data'] as $grid_id => $grid_data ) {
								if ( ! empty( $grid_data['style'] ) && $instance_id === $grid_id ) {
									$level = isset( $grid_data['level'] ) ? $grid_data['level'] : (int) 0;
									if ( $level == $instance ) {
										foreach ( $grid_data['style'] as $style_key => $style_value ) {
											if ( empty( $style_value ) ) {
												continue;
											}

											// Format the value based on style key.
											switch ( $style_key ) {
												case 'background_image_attachment':
													$attachment_id = tg_get_attachment_id( $style_value );

													if ( 0 !== $attachment_id ) {
														$grid_instance['style'][ $style_key ] = $attachment_id;
													}
												break;
												default:
													$grid_instance['style'][ $style_key ] = $style_value;
												break;
											}
										}
									}
								}
							}
						}

						// Update panel grids data.
						$panels_data['grids'][ $instance_id ] = $grid_instance;
				   }
				break;

				case 'widgets':
					foreach ( $panel_data as $instance_id => $widget_instance ) {
						if ( isset( $widget_instance['panels_data']['widgets'] ) ) {
							$instance = $instance + 1;
							$child_panels_data = $widget_instance['panels_data'];
							$panels_data['widgets'][ $instance_id ]['panels_data'] = $this->siteorigin_recursive_update( $child_panels_data, $data_type, $data_value );
							$instance = $instance - 1;
							continue;
						}

						if ( isset( $widget_instance['nav_menu'] ) && isset( $widget_instance['title'] ) ) {
							$nav_menu = wp_get_nav_menu_object( $widget_instance['title'] );

							if ( is_object( $nav_menu ) && $nav_menu->term_id ) {
								$widget_instance['nav_menu'] = $nav_menu->term_id;
							}
						} elseif ( ! empty( $data_value['data_update']['widgets_data'] ) ) {
							$instance_class = $widget_instance['panels_info']['class'];

							foreach ( $data_value['data_update']['widgets_data'] as $dropdown_type => $dropdown_data ) {
								if ( ! in_array( $dropdown_type, array( 'dropdown_pages', 'dropdown_categories' ) ) ) {
									continue;
								}

								// Format the value based on data type.
								switch ( $dropdown_type ) {
									case 'dropdown_pages':
										foreach ( $dropdown_data as $widget_id => $widget_data ) {
											if ( ! empty( $widget_data[ $instance_id ] ) && $widget_id == $instance_class ) {
												$level = isset( $widget_data['level'] ) ? $widget_data['level'] : (int) 0;

												if ( $level == $instance ) {
													foreach ( $widget_data[ $instance_id ] as $widget_key => $widget_value ) {
														$page = get_page_by_title( $widget_value );

														if ( is_object( $page ) && $page->ID ) {
															$widget_instance[ $widget_key ] = $page->ID;
														}
													}
												}
											}
										}
									break;
									case 'dropdown_categories':
										foreach ( $dropdown_data as $taxonomy => $taxonomy_data ) {
											if ( ! taxonomy_exists( $taxonomy ) ) {
												continue;
											}

											foreach ( $taxonomy_data as $widget_id => $widget_data ) {
												if ( ! empty( $widget_data[ $instance_id ] ) && $widget_id == $instance_class ) {
													$level = isset( $widget_data['level'] ) ? $widget_data['level'] : (int) 0;

													if ( $level == $instance ) {
														foreach ( $widget_data[ $instance_id ] as $widget_key => $widget_value ) {
															$term = get_term_by( 'name', $widget_value, $taxonomy );

															if ( is_object( $term ) && $term->term_id ) {
																$widget_instance[ $widget_key ] = $term->term_id;
															}
														}
													}
												}
											}
										}
									break;
								}
							}
						}

						$panels_data['widgets'][ $instance_id ] = $widget_instance;
					}
				break;
			}
		}

		return $panels_data;
	}

	/**
	 * Update siteorigin panel settings data.
	 *
	 * @param string $demo_id Demo ID.
	 * @param array  $demo_data Demo Data.
	 */
	public function update_siteorigin_data( $demo_id, $demo_data ) {
		if ( ! empty( $demo_data['siteorigin_panels_data_update'] ) ) {
			foreach ( $demo_data['siteorigin_panels_data_update'] as $data_type => $data_value ) {
				if ( ! empty( $data_value['post_title'] ) ) {
					$page = get_page_by_title( $data_value['post_title'] );

					if ( is_object( $page ) && $page->ID ) {
						$panels_data = get_post_meta( $page->ID, 'panels_data', true );

						if ( ! empty( $panels_data ) ) {
							$panels_data = $this->siteorigin_recursive_update( $panels_data, $data_type, $data_value );
						}

						// Update siteorigin panels data.
						update_post_meta( $page->ID, 'panels_data', $panels_data );
					}
				}
			}
		}
	}
}

new TG_Demo_Importer();
