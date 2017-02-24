<?php
/**
 * ThemeGrill Demo Importer.
 *
 * @class    TG_Demo_Importer
 * @version  1.0.0
 * @package  Importer/Classes
 * @category Admin
 * @author   ThemeGrill
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * TG_Demo_Importer Class.
 */
class TG_Demo_Importer {

	/**
	 * Demo config.
	 * @var array
	 */
	public $demo_config;

	/**
	 * Demo packages.
	 * @var array
	 */
	public $demo_packages;

	/**
	 * Demo installer.
	 * @var bool
	 */
	public $demo_installer = true;

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'setup' ), 5 );
		add_action( 'init', array( $this, 'includes' ) );

		// Add Demo Importer menu.
		if ( apply_filters( 'themegrill_show_demo_importer_page', true ) ) {
			add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
			add_action( 'admin_head', array( $this, 'add_menu_classes' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		}

		// AJAX Events to import demo and dismiss notice.
		add_action( 'wp_ajax_import-demo', array( $this, 'ajax_import_demo' ) );
		add_action( 'wp_ajax_dismiss-notice', array( $this, 'ajax_dismiss_notice' ) );

		// Update custom nav menu items and siteorigin panel data.
		add_action( 'themegrill_ajax_demo_imported', array( $this, 'update_nav_menu_items' ) );
		add_action( 'themegrill_ajax_demo_imported', array( $this, 'update_siteorigin_data' ), 10, 2 );

		// Update widget and customizer demo import settings data.
		add_filter( 'themegrill_widget_demo_import_settings', array( $this, 'update_widget_data' ), 10, 4 );
		add_filter( 'themegrill_customizer_demo_import_settings', array( $this, 'update_customizer_data' ), 10, 2 );
	}

	/**
	 * Demo importer setup.
	 */
	public function setup() {
		$this->demo_config    = apply_filters( 'themegrill_demo_importer_config', array() );
		$this->demo_packages  = apply_filters( 'themegrill_demo_importer_packages', array() );
		$this->demo_installer = apply_filters( 'themegrill_demo_importer_installer', true );
	}

	/**
	 * Include required core files.
	 */
	public function includes() {
		include_once( dirname( __FILE__ ) . '/includes/functions-demo-importer.php' );
		include_once( dirname( __FILE__ ) . '/includes/class-customizer-importer.php' );
		include_once( dirname( __FILE__ ) . '/includes/class-widget-importer.php' );
	}

	/**
	 * Get the import file URL.
	 *
	 * @param  string $demo_dir demo dir.
	 * @param  string $filename import filename.
	 * @return string the demo import data file URL.
	 */
	private function import_file_url( $demo_dir, $filename ) {
		$working_dir = tg_get_demo_file_url( $demo_dir );

		// If enabled demo pack, load from upload dir.
		if ( $this->is_enabled_demo_pack( $demo_dir ) ) {
			$upload_dir  = wp_upload_dir();
			$working_dir = $upload_dir['baseurl'] . '/tg-demo-pack/' . $demo_dir;
		}

		return trailingslashit( $working_dir ) . sanitize_file_name( $filename );
	}

	/**
	 * Get the import file path.
	 *
	 * @param  string $demo_dir demo dir.
	 * @param  string $filename import filename.
	 * @return string the import data file path.
	 */
	private function import_file_path( $demo_dir, $filename ) {
		$working_dir = tg_get_demo_file_path( $demo_dir );

		// If enabled demo pack, load from upload dir.
		if ( $this->is_enabled_demo_pack( $demo_dir ) ) {
			$upload_dir  = wp_upload_dir();
			$working_dir = $upload_dir['basedir'] . '/tg-demo-pack/' . $demo_dir . '/dummy-data';
		}

		return trailingslashit( $working_dir ) . sanitize_file_name( $filename );
	}

	/**
	 * Check if demo pack is enabled.
	 * @param  array $demo_id
	 * @return bool
	 */
	public function is_enabled_demo_pack( $demo_id ) {
		if ( isset( $this->demo_config[ $demo_id ]['demo_pack'] ) && true === $this->demo_config[ $demo_id ]['demo_pack'] ) {
			return true;
		}

		return false;
	}

	/**
	 * Add menu item.
	 */
	public function add_admin_menu() {
		add_theme_page( __( 'Demo Importer', 'themegrill-demo-importer' ), __( 'Demo Importer', 'themegrill-demo-importer' ), 'switch_themes', 'demo-importer', array( $this, 'demo_importer' ) );
	}

	/**
	 * Adds the class to the menu.
	 */
	public function add_menu_classes() {
		global $submenu;

		if ( isset( $submenu['themes.php'] ) ) {
			$submenu_class = $this->demo_installer ? 'demo-installer hide-if-no-js' : 'demo-importer';

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
		$assets_path = ThemeGrill_Demo_Importer::plugin_url() . '/assets/';

		// Register Styles.
		wp_register_style( 'tg-demo-importer', $assets_path . 'css/demo-importer.css', array() );

		// Register Scripts.
		wp_register_script( 'jquery-tiptip', $assets_path . 'js/jquery-tiptip/jquery.tipTip' . $suffix . '.js', array( 'jquery' ), '1.3', true );
		wp_register_script( 'tg-demo-updates', $assets_path . 'js/admin/demo-updates' . $suffix . '.js', array( 'jquery', 'updates' ), '1.1.0', true );
		wp_register_script( 'tg-demo-importer', $assets_path . 'js/admin/demo-importer' . $suffix . '.js', array( 'jquery', 'jquery-tiptip', 'wp-backbone', 'wp-a11y', 'tg-demo-updates' ), '1.1.0', true );

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
				)
			) );
			wp_localize_script( 'tg-demo-importer', 'demoImporterLocalizeScript', array(
				'demos'    => $this->is_preview() ? $this->prepare_previews_for_js( $this->demo_packages ) : $this->prepare_demos_for_js( $this->demo_config ),
				'settings' => array(
					'isPreview'     => $this->is_preview(),
					'isInstall'     => $this->demo_installer,
					'canInstall'    => current_user_can( 'upload_files' ),
					'installURI'    => current_user_can( 'upload_files' ) ? self_admin_url( 'themes.php?page=demo-importer&browse=preview' ) : null,
					'confirmDelete' => __( "Are you sure you want to delete this demo?\n\nClick 'Cancel' to go back, 'OK' to confirm the delete.", 'themegrill-demo-importer' ),
					'confirmImport' => __( 'Importing demo content will replicate the live demo and overwrites your current customizer, widgets and other settings. It might take few minutes to complete the demo import. Are you sure you want to import this demo?', 'themegrill-demo-importer' ),
					'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
					'adminUrl'      => parse_url( self_admin_url(), PHP_URL_PATH ),
				),
				'l10n' => array(
					'addNew'            => __( 'Add New Demo', 'themegrill-demo-importer' ),
					'search'            => __( 'Search Demos', 'themegrill-demo-importer' ),
					'searchPlaceholder' => __( 'Search demos...', 'themegrill-demo-importer' ), // placeholder (no ellipsis)
					'demosFound'        => __( 'Number of Demos found: %d', 'themegrill-demo-importer' ),
					'noDemosFound'      => __( 'No demos found. Try a different search.', 'themegrill-demo-importer' ),
				),
				'installedDemos' => array_keys( $this->demo_config ),
			) );
		}
	}

	/**
	 * Check for preview filter.
	 * @return bool
	 */
	public function is_preview() {
		if ( $this->demo_installer && isset( $_GET['browse'] ) ) {
			return 'preview' === $_GET['browse'] ? true : false;
		}

		return false;
	}

	/**
	 * Prepare previews for JavaScript.
	 */
	private function prepare_previews_for_js( $demos = null ) {
		$prepared_demos   = array();
		$current_template = get_option( 'template' );
		$demo_imported_id = get_option( 'themegrill_demo_imported_id' );
		$demo_assets_path = ThemeGrill_Demo_Importer::plugin_url() . '/assets/';

		/**
		 * Filters demo data before it is prepared for JavaScript.
		 *
		 * @param array      $prepared_demos   An associative array of demo data. Default empty array.
		 * @param null|array $demos            An array of demo config to prepare, if any.
		 * @param string     $demo_imported_id The current demo imported id.
		 */
		$prepared_demos = (array) apply_filters( 'themegrill_demo_importer_pre_prepare_demos_for_js', array(), $demos, $demo_imported_id );

		if ( ! empty( $prepared_demos ) ) {
			return $prepared_demos;
		}

		if ( ! empty( $demos ) ) {
			foreach ( $demos as $demo_id => $demo_data ) {
				$author       = isset( $demo_data['author'] ) ? $demo_data['author'] : __( 'ThemeGrill', 'themegrill-demo-importer' );
				$download_url = isset( $demo_data['download'] ) ? $demo_data['download'] : "https://github.com/themegrill/themegrill-demo-pack/raw/master/packages/{$current_template}/{$demo_id}.zip";

				// Check if demo is installed.
				$installed = false;
				if ( in_array( $demo_id, array_keys( $this->demo_config ) ) ) {
					$installed = true;
				}

				// Prepare all demos.
				$prepared_demos[ $demo_id ] = array(
					'id'              => $demo_id,
					'name'            => $demo_data['name'],
					'author'          => $author,
					'installed'       => $installed,
					'screenshot'      => "{$demo_assets_path}images/{$current_template}/{$demo_id}.jpg",
					'description'     => isset( $demo_data['description'] ) ? $demo_data['description'] : '',
					'actions'         => array(
						'preview_url'  => $demo_data['preview'],
						'download_url' => $download_url,
					),
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
		return array_filter( $prepared_demos );
	}

	/**
	 * Prepare demos for JavaScript.
	 *
	 * @param  array $demos Demo config array.
	 * @return array An associative array of demo data, sorted by name.
	 */
	private function prepare_demos_for_js( $demos = null ) {
		$prepared_demos   = array();
		$current_template = get_option( 'template' );
		$demo_imported_id = get_option( 'themegrill_demo_imported_id' );

		/**
		 * Filters demo data before it is prepared for JavaScript.
		 *
		 * @param array      $prepared_demos   An associative array of demo data. Default empty array.
		 * @param null|array $demos            An array of demo config to prepare, if any.
		 * @param string     $demo_imported_id The current demo imported id.
		 */
		$prepared_demos = (array) apply_filters( 'themegrill_demo_importer_pre_prepare_demos_for_js', array(), $demos, $demo_imported_id );

		if ( ! empty( $prepared_demos ) ) {
			return $prepared_demos;
		}

		// Make sure the imported demo is listed first.
		if ( isset( $demos[ $demo_imported_id ] ) ) {
			$prepared_demos[ $demo_imported_id ] = array();
		}

		if ( ! empty( $demos ) ) {
			foreach ( $demos as $demo_id => $demo_data ) {
				$demo_notices = array();
				$encoded_slug = urlencode( $demo_id );
				$demo_package = isset( $demo_data['demo_pack'] ) ? $demo_data['demo_pack'] : false;
				$plugins_list = isset( $demo_data['plugins_list'] ) ? $demo_data['plugins_list'] : array();

				// Plugins status.
				foreach ( $plugins_list as $plugin => $plugin_data ) {
					$plugins_list[ $plugin ]['is_active'] = is_plugin_active( $plugin_data['slug'] );
				}

				// Add demo notices.
				if ( isset( $demo_data['template'] ) && $current_template !== $demo_data['template'] ) {
					$demo_notices['required_theme'] = true;
				} elseif ( wp_list_filter( $plugins_list, array( 'required' => true, 'is_active' => false ) ) ) {
					$demo_notices['required_plugins'] = true;
				}

				// Prepare all demos.
				$prepared_demos[ $demo_id ] = array(
					'id'              => $demo_id,
					'name'            => $demo_data['name'],
					'theme'           => $demo_data['theme'],
					'package'         => $demo_package,
					'screenshot'      => $this->import_file_url( $demo_id, 'screenshot.jpg' ),
					'description'     => isset( $demo_data['description'] ) ? $demo_data['description'] : '',
					'author'          => isset( $demo_data['author'] ) ? $demo_data['author'] : __( 'ThemeGrill', 'themegrill-demo-importer' ),
					'authorAndUri'    => '<a href="http://themegrill.com" target="_blank">ThemeGrill</a>',
					'version'         => isset( $demo_data['version'] ) ? $demo_data['version'] : '1.1.0',
					'active'          => $demo_id === $demo_imported_id,
					'hasNotice'       => $demo_notices,
					'plugins'         => $plugins_list,
					'actions'         => array(
						'preview'  => home_url( '/' ),
						'demo_url' => $demo_data['demo_url'],
						'delete'   => current_user_can( 'upload_files' ) ? wp_nonce_url( admin_url( 'themes.php?page=demo-importer&browse=uploads&action=delete&amp;demo_pack=' . urlencode( $demo_id ) ), 'delete-demo_' . $demo_id ) : null,
					),
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
		return array_filter( $prepared_demos );
	}

	/**
	 * Demo Importer page output.
	 */
	public function demo_importer() {
		$demos = $this->prepare_demos_for_js( $this->demo_config );

		if ( isset( $_GET['action'] ) && 'upload-demo' === $_GET['action'] ) {
			$this->upload_demo_pack();
		} else {
			$suffix = $this->demo_installer ? 'installer' : 'importer';
			include_once( dirname( __FILE__ ) . "/includes/admin/views/html-admin-page-{$suffix}.php" );
		}
	}

	/**
	 * Upload demo pack.
	 */
	private function upload_demo_pack() {
		include_once( ABSPATH . 'wp-admin/includes/class-wp-upgrader.php' );

		if ( ! current_user_can( 'upload_files' ) ) {
			wp_die( __( 'Sorry, you are not allowed to install demo on this site.', 'themegrill-demo-importer' ) );
		}

		check_admin_referer( 'demo-upload' );

		$file_upload = new File_Upload_Upgrader( 'demozip', 'package' );

		$title = sprintf( __( 'Installing Demo from uploaded file: %s', 'themegrill-demo-importer' ), esc_html( basename( $file_upload->filename ) ) );
		$nonce = 'demo-upload';
		$url   = add_query_arg( array( 'package' => $file_upload->id ), 'themes.php?page=demo-importer&action=upload-demo' );
		$type  = 'upload'; // Install demo type, From Web or an Upload.

		// Demo Upgrader Class.
		include_once( dirname( __FILE__ ) . '/includes/admin/class-demo-upgrader.php' );
		include_once( dirname( __FILE__ ) . '/includes/admin/class-demo-installer-skin.php' );

		$upgrader = new TG_Demo_Upgrader( new TG_Demo_Installer_Skin( compact( 'type', 'title', 'nonce', 'url' ) ) );
		$result = $upgrader->install( $file_upload->package );

		if ( $result || is_wp_error( $result ) ) {
			$file_upload->cleanup();
		}
	}

	/**
	 * Ajax handler for dismissing notice.
	 */
	public function ajax_dismiss_notice() {
		if ( ! current_user_can( 'manage_options' ) ) {
			die( -1 );
		}

		$notice_id = sanitize_text_field( stripslashes( $_POST['notice_id'] ) );

		if ( ! empty( $notice_id ) && 'demo-importer' == $notice_id ) {
			update_option( 'themegrill_demo_imported_notice_dismiss', 1 );
		}

		die();
	}

	/**
	 * Ajax handler for importing a demo.
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

		$slug = sanitize_key( wp_unslash( $_POST['slug'] ) );

		$status = array(
			'import' => 'demo',
			'slug'   => $slug,
		);

		if ( ! defined( 'WP_LOAD_IMPORTERS' ) ) {
			define( 'WP_LOAD_IMPORTERS', true );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			$status['errorMessage'] = __( 'Sorry, you are not allowed to import.', 'themegrill-demo-importer' );
			wp_send_json_error( $status );
		}

		$demo_data = isset( $this->demo_config[ $slug ] ) ? $this->demo_config[ $slug ] : array();

		do_action( 'themegrill_ajax_before_demo_import' );

		if ( ! empty( $demo_data ) ) {
			$this->import_dummy_xml( $slug, $demo_data, $status );
			$this->import_core_options( $slug, $demo_data );
			$this->import_customizer_data( $slug, $demo_data, $status );
			$this->import_widget_settings( $slug, $demo_data, $status );

			// Update imported demo ID.
			update_option( 'themegrill_demo_imported_id', $slug );

			do_action( 'themegrill_ajax_demo_imported', $slug, $demo_data );
		}

		$status['demoName']   = $demo_data['name'];
		$status['previewUrl'] = get_home_url( '/' );

		wp_send_json_success( $status );
	}

	/**
	 * Import dummy content from a XML file.
	 * @param  string $demo_id
	 * @param  array  $demo_data
	 * @param  array  $status
	 * @return bool
	 */
	public function import_dummy_xml( $demo_id, $demo_data, $status ) {
		$import_file = $this->import_file_path( $demo_id, 'dummy-data.xml' );

		// Load Importer API
		require_once ABSPATH . 'wp-admin/includes/import.php';

		if ( ! class_exists( 'WP_Importer' ) ) {
			$class_wp_importer = ABSPATH . 'wp-admin/includes/class-wp-importer.php';

			if ( file_exists( $class_wp_importer ) ) {
				require $class_wp_importer;
			}
		}

		// Include WXR Importer.
		require dirname( __FILE__ ) . '/includes/importers/class-wxr-importer.php';

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
	 * @param  string $demo_id
	 * @param  array  $demo_data
	 * @param  array  $status
	 * @return bool
	 */
	public function import_customizer_data( $demo_id, $demo_data, $status ) {
		$import_file = $this->import_file_path( $demo_id, 'dummy-customizer.dat' );

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
	 * @param  string $demo_id
	 * @param  array  $demo_data
	 * @param  array  $status
	 * @return bool
	 */
	public function import_widget_settings( $demo_id, $demo_data, $status ) {
		$import_file = $this->import_file_path( $demo_id, 'dummy-widgets.wie' );

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
    * Recursive function to address n level deep layoutbuilder data update.
    * @param  array $panels_data
    * @param  string $data_type
    * @param  array $data_value
    * @return array
    */
   public function siteorigin_recursive_update( $panels_data, $data_type, $data_value) {
      static $instance = 0;
      foreach ( $panels_data as $panel_type => $panel_data ) {
         // Format the value based on panel type.
         switch ( $panel_type ) {
            case 'grids':
               foreach ( $panel_data as $instance_id => $grid_instance ) {
                  if ( ! empty( $data_value['data_update']['grids_data'] ) ) {
                     foreach ( $data_value['data_update']['grids_data'] as $grid_id => $grid_data ) {
                        if ( ! empty( $grid_data['style'] ) && $instance_id === $grid_id ) {
                           $level = isset( $grid_data['level'] ) ? $grid_data['level'] : (int)0;
                           if( $level == $instance ) {
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
               foreach ($panel_data as $instance_id => $widget_instance ) {
                  if( isset( $widget_instance['panels_data']['widgets'] ) ) {
                     $instance = $instance+1;
                     $child_panels_data = $widget_instance['panels_data'];
                     $panels_data['widgets'][$instance_id]['panels_data'] = $this->siteorigin_recursive_update( $child_panels_data, $data_type, $data_value );
                     $instance = $instance-1;
                     continue;
                  }
                  if ( isset( $widget_instance['nav_menu'] ) && isset( $widget_instance['title'] ) ) {
                     $nav_menu = wp_get_nav_menu_object( $widget_instance['title'] );

                     if ( is_object( $nav_menu ) && $nav_menu->term_id ) {
                        $widget_instance['nav_menu'] = $nav_menu->term_id;
                     }
                  }elseif ( ! empty( $data_value['data_update']['widgets_data'] ) ) {
                     $instance_class = $widget_instance['panels_info']['class'];
                     foreach ( $data_value['data_update']['widgets_data'] as $dropdown_type => $dropdown_data ) {
                        if ( ! in_array( $dropdown_type, array( 'dropdown_pages', 'dropdown_categories' ) ) ) {
                           continue;
                        }
                        switch ( $dropdown_type ) {
                           case 'dropdown_pages':
                              foreach ( $dropdown_data as $widget_id => $widget_data ) {
                                 if ( ! empty( $widget_data[ $instance_id ] ) && $widget_id == $instance_class ) {
                                    $level = isset( $widget_data['level'] ) ? $widget_data['level'] : (int)0;
                                    if( $level == $instance ) {
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
                                       $level = isset( $widget_data['level'] ) ? $widget_data['level'] : (int)0;
                                       if( $level == $instance ) {
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
    * @param string $demo_id
    * @param array  $demo_data
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
