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
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'setup' ), 5 );
		add_action( 'init', array( $this, 'includes' ) );

		// Add Demo Importer menu.
		if ( apply_filters( 'themegrill_show_demo_importer_page', true ) ) {
			add_action( 'admin_menu', array( $this, 'demo_importer_menu' ) );
		}

		// Add Demo Importer filterable content.
		add_action( 'themegrill_demo_importer_welcome', array( $this, 'welcome_panel' ) );
		add_action( 'themegrill_demo_importer_uploaded', array( $this, 'output_uploaded' ) );
		add_action( 'themegrill_demo_importer_previews', array( $this, 'output_previews' ) );

		// AJAX Events to dismiss notice and import demo data.
		add_action( 'wp_ajax_tg_dismiss_notice', array( $this, 'dismissible_notice' ) );
		add_action( 'wp_ajax_tg_import_demo_data', array( $this, 'import_demo_data' ) );

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
		$this->demo_config   = apply_filters( 'themegrill_demo_importer_config', array() );
		$this->demo_packages = apply_filters( 'themegrill_demo_importer_packages', array() );
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
	public function demo_importer_menu() {
		$page = add_theme_page( __( 'Demo Importer', 'themegrill-demo-importer' ), __( 'Demo Importer', 'themegrill-demo-importer' ), 'switch_themes', 'demo-importer', array( $this, 'demo_importer' ) );
		add_action( 'admin_print_styles-' . $page, array( $this, 'enqueue_styles' ) );
	}

	/**
	 * Enqueue styles.
	 */
	public function enqueue_styles() {
		$suffix      = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		$assets_path = tg_get_demo_importer_assets_path();

		// Register Scripts
		wp_register_script( 'jquery-tiptip', $assets_path . 'js/jquery-tiptip/jquery.tipTip' . $suffix . '.js', array( 'jquery' ), '1.3', true );

		// Enqueue Scripts
		wp_enqueue_style( 'tg-demo-importer', $assets_path . 'css/demo-importer.css', array() );
		wp_enqueue_script( 'tg-demo-importer', $assets_path . 'js/admin/demo-importer' . $suffix . '.js', array( 'jquery', 'jquery-tiptip' ), '1.0.0' );

		wp_localize_script( 'tg-demo-importer', 'demo_importer_params', array(
			'ajax_url'               => admin_url( 'admin-ajax.php' ),
			'import_demo_data_nonce' => wp_create_nonce( 'import-demo-data' ),
			'i18n_import_data_error' => esc_js( __( 'Importing Failed. Try again!', 'themegrill-demo-importer' ) ),
			'i18n_import_dummy_data' => esc_js( __( 'Importing demo content will replicate the live demo and overwrites your current customizer, widgets and other settings. It might take few minutes to complete the demo import. Are you sure you want to import this demo?', 'themegrill-demo-importer' ) ),
		) );
	}

	/**
	 * Demo Importer page output.
	 */
	public function demo_importer() {
		global $current_tab;

		$current_tab = empty( $_GET['tab'] ) ? 'welcome' : sanitize_title( $_GET['tab'] );

		if ( isset( $_GET['action'] ) && 'upload-demo' === $_GET['action'] ) {
			$this->upload_demo_pack();
		} else {
			include_once( dirname( __FILE__ ) . '/includes/admin/views/html-admin-page-importer.php' );
		}
	}

	/**
	 * Output welcome panel page.
	 */
	public function welcome_panel() {
		include_once( dirname( __FILE__ ) . '/includes/admin/views/html-admin-page-importer-welcome.php' );
	}

	/**
	 * Output demo uploaded page.
	 */
	public function output_uploaded() {
		include_once( dirname( __FILE__ ) . '/includes/admin/views/html-admin-page-importer-uploaded.php' );
	}

	/**
	 * Output demo previews page.
	 */
	public function output_previews() {
		include_once( dirname( __FILE__ ) . '/includes/admin/views/html-admin-page-importer-previews.php' );
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
	 * AJAX Dismissible notice.
	 */
	public function dismissible_notice() {
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
	 * AJAX Import demo/dummy data.
	 */
	public function import_demo_data() {
		ob_start();

		check_ajax_referer( 'import-demo-data', 'security' );

		if ( ! defined( 'WP_LOAD_IMPORTERS' ) ) {
			define( 'WP_LOAD_IMPORTERS', true );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			die( -1 );
		}

		$demo_id   = sanitize_text_field( stripslashes( $_POST['demo_id'] ) );
		$demo_data = isset( $this->demo_config[ $demo_id ] ) ? $this->demo_config[ $demo_id ] : array();

		do_action( 'themegrill_ajax_before_demo_import' );

		if ( ! empty( $demo_data ) ) {
			$this->import_dummy_xml( $demo_id, $demo_data );
			$this->import_core_options( $demo_id, $demo_data );
			$this->import_customizer_data( $demo_id, $demo_data );
			$this->import_widget_settings( $demo_id, $demo_data );

			update_option( 'themegrill_demo_imported_id', $demo_id );

			do_action( 'themegrill_ajax_demo_imported', $demo_id, $demo_data );

			wp_send_json_success( array(
				'demo_id' => $demo_id,
				'message' => __( 'Successfully Imported', 'themegrill-demo-importer' ),
			) );
		}

		die();
	}

	/**
	 * Import dummy content from a XML file.
	 * @param  string $demo_id
	 * @param  array  $demo_data
	 * @return bool
	 */
	public function import_dummy_xml( $demo_id, $demo_data ) {
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
			wp_send_json_error( array( 'message' => __( 'The XML file containing the dummy content is not available.', 'themegrill-demo-importer' ) ) );
			exit;
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
	 * @return bool
	 */
	public function import_customizer_data( $demo_id, $demo_data ) {
		$import_file = $this->import_file_path( $demo_id, 'dummy-customizer.dat' );

		if ( is_file( $import_file ) ) {
			$results = TG_Customizer_Importer::import( $import_file, $demo_id, $demo_data );

			if ( is_wp_error( $results ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Import widgets settings from WIE or JSON file.
	 * @param  string $demo_id
	 * @param  array  $demo_data
	 * @return bool
	 */
	public function import_widget_settings( $demo_id, $demo_data ) {
		$import_file = $this->import_file_path( $demo_id, 'dummy-widgets.wie' );

		if ( is_file( $import_file ) ) {
			$results = TG_Widget_Importer::import( $import_file, $demo_id, $demo_data );

			if ( is_wp_error( $results ) ) {
				return false;
			}
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
							foreach ( $panels_data as $panel_type => $panel_data ) {
								if ( ! in_array( $panel_type, array( 'grids', 'widgets' ) ) ) {
									continue;
								}

								// Format the value based on panel type.
								switch ( $panel_type ) {
									case 'grids':
										foreach ( $panel_data as $instance_id => $grid_instance ) {
											if ( ! empty( $data_value['data_update']['grids_data'] ) ) {
												foreach ( $data_value['data_update']['grids_data'] as $grid_id => $grid_data ) {
													if ( ! empty( $grid_data['style'] ) && $instance_id === $grid_id ) {
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

											// Update panel grids data.
											$panels_data['grids'][ $instance_id ] = $grid_instance;
										}
									break;
									case 'widgets':
										foreach ( $panel_data as $instance_id => $widget_instance ) {
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

													// Format the value based on dropdown type.
													switch ( $dropdown_type ) {
														case 'dropdown_pages':
															foreach ( $dropdown_data as $widget_id => $widget_data ) {
																if ( ! empty( $widget_data[ $instance_id ] ) && $widget_id == $instance_class ) {
																	foreach ( $widget_data[ $instance_id ] as $widget_key => $widget_value ) {
																		$page = get_page_by_title( $widget_value );

																		if ( is_object( $page ) && $page->ID ) {
																			$widget_instance[ $widget_key ] = $page->ID;
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
																		foreach ( $widget_data[ $instance_id ] as $widget_key => $widget_value ) {
																			$term = get_term_by( 'name', $widget_value, $taxonomy );

																			if ( is_object( $term ) && $term->term_id ) {
																				$widget_instance[ $widget_key ] = $term->term_id;
																			}
																		}
																	}
																}
															}
														break;
													}
												}
											}

											// Update panel widgets data.
											$panels_data['widgets'][ $instance_id ] = $widget_instance;
										}
									break;
								}
							}
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
