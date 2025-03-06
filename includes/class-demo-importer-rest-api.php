<?php

// Include WXR Importer.
require_once __DIR__ . '/importers/wordpress-importer/class-wxr-importer.php';

class TG_Importer_REST_Controller extends WP_REST_Controller {
	protected $namespace;

	protected $importer;

	protected $fetch_attachments = true;

	public function __construct() {
		$this->namespace = 'tg-demo-importer/v1';
		$this->importer  = new TG_WXR_Importer( $this->get_import_options() );
		$this->includes();
		add_action( 'themegrill_demo_imported', array( $this, 'update_nav_menu_items' ) );
		add_action( 'themegrill_demo_imported', array( $this, 'update_elementor_data' ), 10, 2 );
		add_action( 'themegrill_demo_imported', array( $this, 'update_siteorigin_data' ), 10, 2 );
		add_filter( 'themegrill_widget_import_settings', array( $this, 'update_widget_data' ), 10, 4 );
		add_filter( 'themegrill_customizer_import_settings', array( $this, 'update_customizer_data' ), 10, 2 );
	}

	protected function get_import_options() {
		$options = array(
			'fetch_attachments' => $this->fetch_attachments,
			'default_author'    => get_current_user_id(),
		);

		/**
		 * Filter the importer options used in the admin UI.
		 *
		 * @param array $options Options to pass to WXR_Importer::__construct
		 */
		return apply_filters( 'wxr_importer.admin.import_options', $options );
	}

	public function includes() {
		require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
		require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
	}

	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/install-theme',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'install_theme' ),
					'permission_callback' => '__return_true',
				),
			)
		);
		register_rest_route(
			$this->namespace,
			'/import',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'import' ),
					'permission_callback' => '__return_true', // TODO: proper permission check
				),
			)
		);
		register_rest_route(
			$this->namespace,
			'/import-plugins',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'import_plugins' ),
					'permission_callback' => '__return_true', // TODO: proper permission check
				),
			)
		);
	}

	public function install_theme( $request ) {
		$theme_slug = $request->get_param( 'theme' );
		$theme      = wp_get_theme( $theme_slug );
		if ( ! $theme->exists() ) { //check if theme exists in the list of installed themes
			require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
			require_once ABSPATH . 'wp-admin/includes/theme.php';
			require_once ABSPATH . 'wp-admin/includes/file.php';

			$api = themes_api(
				'theme_information',
				array(
					'slug' => sanitize_text_field( $theme_slug ),
				)
			);

			if ( is_wp_error( $api ) ) {
				return new WP_Error( 'rest_custom_error', 'Error fetching theme information: ' . esc_html( $api->get_error_message() ), array( 'status' => 400 ) );
			}

			$skin      = new WP_Ajax_Upgrader_Skin();
			$upgrader  = new Theme_Upgrader( $skin );
			$installed = $upgrader->install( $api->download_link );

			if ( is_wp_error( $installed ) ) {
				return new WP_Error( 'rest_custom_error', 'Failed to install the theme: ' . esc_html( $installed->get_error_message() ), array( 'status' => 400 ) );
			}
		}

		switch_theme( $theme_slug ); //activate the theme

		return new WP_REST_Response(
			array(
				'success' => true,
				'message' => 'Theme installed and activated.',
			),
			200
		);
	}

	public function import( $request ) {
		$demo        = $request->get_param( 'demo' );
		$slugs       = $request->get_param( 'slugs' );
		$pagebuilder = $request->get_param( 'selectedPagebuilder' );
		// if ( in_array( 'plugins', $slugs, true ) ) {
		//  $status['plugins'] = $this->import_plugins( $demo );
		// }
		if ( in_array( 'evf', $slugs, true ) ) {
			$status['evf'] = $this->import_evf();
		}
		if ( in_array( 'content', $slugs, true ) ) {
			$status['content'] = $this->import_content( $demo, $pagebuilder );
		}
		if ( in_array( 'customizer', $slugs, true ) ) {
			$status['customizer'] = $this->import_customizer( $demo, $pagebuilder );
		}
		if ( in_array( 'widget', $slugs, true ) ) {
			$status['widget'] = $this->import_widget( $demo, $pagebuilder );
		}
		// Update imported demo ID.
		update_option( 'themegrill_demo_importer_activated_id', $demo['slug'] );
		do_action( 'themegrill_demo_imported', $demo['slug'], $demo );

		flush_rewrite_rules();
		wp_cache_flush();
		return new WP_REST_Response( array( 'status' => $status ), 200 );
	}

	public function import_content( $demo, $pagebuilder ) {
		$content = $demo['pagebuilder_data'][ $pagebuilder ]['content'];
		if ( ! $content ) {
			return new WP_Error( 'rest_custom_error', 'No content file.', array( 'status' => 400 ) );
		}
		do_action( 'themegrill_ajax_before_demo_import' );
		$this->import_xml( $content );
		$this->import_core_options( $demo );

		return new WP_REST_Response( array( 'success' => 'Content Imported.' ), 200 );
	}

	public function import_xml( $content ) {
		// Load Importer API.
		require_once ABSPATH . 'wp-admin/includes/import.php';

		if ( ! class_exists( 'WP_Importer' ) ) {
			$class_wp_importer = ABSPATH . 'wp-admin/includes/class-wp-importer.php';

			if ( file_exists( $class_wp_importer ) ) {
				require $class_wp_importer;
			}
		}

		$logger = new WP_Importer_Logger_ServerSentEvents();
		$this->importer->set_logger( $logger );
		ob_start();
		$data = $this->importer->import( $content );
		ob_end_clean();
		if ( is_wp_error( $data ) ) {
			return new WP_Error( 'rest_custom_error', 'Error importing content:' . $data, array( 'status' => 400 ) );
		}

		return true;
	}

	public function import_core_options( $demo ) {
		if ( ! empty( $demo['core_options'] ) ) {
			foreach ( $demo['core_options'] as $option_key => $option_value ) {
				if ( ! in_array( $option_key, array( 'blogname', 'blogdescription', 'show_on_front', 'page_on_front', 'page_for_posts' ), true ) ) {
					continue;
				}

				// Format the value based on option key.
				switch ( $option_key ) {
					case 'show_on_front':
						if ( in_array( $option_value, array( 'posts', 'page' ), true ) ) {
							update_option( 'show_on_front', $option_value );
						}
						break;
					case 'page_on_front':
					case 'page_for_posts':
						$page = $this->get_page_by_title( $option_value );

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

	public function get_page_by_title( $title ) {
		if ( ! $title ) {
			return null;
		}

		$query = new WP_Query(
			array(
				'post_type'              => 'page',
				'title'                  => $title,
				'post_status'            => 'all',
				'posts_per_page'         => 1,
				'no_found_rows'          => true,
				'ignore_sticky_posts'    => true,
				'update_post_term_cache' => false,
				'update_post_meta_cache' => false,
			)
		);

		if ( ! $query->have_posts() ) {
			return null;
		}

		return current( $query->posts );
	}

	public function import_customizer( $demo, $pagebuilder ) {
		$customizer = $demo['pagebuilder_data'][ $pagebuilder ]['customizer'];
		if ( ! $customizer ) {
			return new WP_Error( 'rest_custom_error', 'No customizer file.', array( 'status' => 400 ) );
		}

		$import = TG_Customizer_Importer::import( $customizer, $demo['slug'], $demo, $pagebuilder );
		if ( is_wp_error( $import ) ) {
			return new WP_Error( 'rest_custom_error', 'Error importing customizer.', array( 'status' => 400 ) );
		}
		return new WP_REST_Response( array( 'success' => 'Customizer Imported.' ), 200 );
	}

	public function import_widget( $demo, $pagebuilder ) {
		$widget = $demo['pagebuilder_data'][ $pagebuilder ]['widget'];
		if ( ! $widget ) {
			return new WP_Error( 'rest_custom_error', 'No widget file.', array( 'status' => 400 ) );
		}

		$import = TG_Widget_Importer::import( $widget, $demo['slug'], $demo );
		if ( is_wp_error( $import ) ) {
			return new WP_Error( 'rest_custom_error', 'Error importing widget.', array( 'status' => 400 ) );
		}
		return new WP_REST_Response( array( 'success' => 'Widget Imported.' ), 200 );
	}

	public function import_plugins( $request ) {
		$demo        = $request->get_param( 'demo' );
		$slugs       = $request->get_param( 'slugs' );
		$pagebuilder = $request->get_param( 'selectedPagebuilder' );
		$plugins     = $demo['pagebuilder_data'][ $pagebuilder ]['plugins'];
		if ( ! $plugins ) {
			return new WP_Error( 'rest_custom_error', 'No plugins specified.', array( 'status' => 400 ) );
		}
		if ( ! in_array( 'evf', $slugs, true ) ) {
			$index = array_search( 'everest-forms/everest-forms.php', $plugins, true );
			if ( false !== $index ) {
				array_splice( $plugins, $index, 1 );
			}
		}
		foreach ( $plugins as $plugin ) {
			$pg = explode( '/', $plugin );
			if ( file_exists( WP_PLUGIN_DIR . '/' . $plugin ) ) {
				$plugin_data = get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin );
				if ( is_plugin_inactive( $plugin ) ) {
					$result = activate_plugin( $plugin );

					if ( is_wp_error( $result ) ) {
						// $status['errorCode']    = $result->get_error_code();
						// $status['errorMessage'] = $result->get_error_message();
						// wp_send_json_error( $status );
						$error[ $pg[0] ] = 'Error activating plugin : ' . esc_html( $result->get_error_message() );
					}

					$status[ $pg[0] ] = $plugin_data['Name'] . ' activated.';
				} else {
					$status[ $pg[0] ] = $plugin_data['Name'] . ' already activated.';
				}
				continue;
			}
			$api = plugins_api(
				'plugin_information',
				array(
					'slug' => sanitize_key( wp_unslash( $pg[0] ) ),
				)
			);
			if ( is_wp_error( $api ) ) {
				$error[ $pg[0] ] = 'Error fetching plugin information: ' . esc_html( $api->get_error_message() );
				// return new WP_Error( 'rest_custom_error', 'Error fetching plugin information: ' . esc_html( $api->get_error_message() ), array( 'status' => 400 ) );
			}

			$skin      = new WP_Ajax_Upgrader_Skin();
			$upgrader  = new Plugin_Upgrader( $skin );
			$installed = $upgrader->install( $api->download_link );

			if ( is_wp_error( $installed ) ) {
				$error[ $pg[0] ] = 'Failed to install the plugin: ' . esc_html( $installed->get_error_message() );
				// return new WP_Error( 'rest_custom_error', 'Failed to install the plugin: ' . esc_html( $installed->get_error_message() ), array( 'status' => 400 ) );
			}

			$install_status = install_plugin_install_status( $api );

			if ( is_plugin_inactive( $install_status['file'] ) ) {
				$result = activate_plugin( $install_status['file'] );

				if ( is_wp_error( $result ) ) {
					// $status['errorCode']    = $result->get_error_code();
					// $status['errorMessage'] = $result->get_error_message();
					// wp_send_json_error( $status );
					// return new WP_Error( $result->get_error_code(), 'Error activating plugin : ' . esc_html( $result->get_error_message() ), array( 'status' => 400 ) );
					$error[ $pg[0] ] = 'Error activating plugin : ' . esc_html( $result->get_error_message() );
				}
			}
			$status[ $pg[0] ] = $api->name . ' installed and activated.';

		}

		return new WP_REST_Response(
			array(
				'success' => true,
				'message' => $status,
			),
			200
		);
	}

	// public function import_plugins( $demo ) {
	//  if ( ! $demo['plugins'] ) {
	//      return new WP_Error( 'rest_custom_error', 'No plugins specified.', array( 'status' => 400 ) );
	//  }
	//  foreach ( $demo['plugins'] as $plugin ) {
	//      $pg = explode( '/', $plugin );
	//      if ( file_exists( WP_PLUGIN_DIR . '/' . $plugin ) ) {
	//          $plugin_data = get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin );
	//          if ( is_plugin_inactive( $plugin ) ) {
	//              $result = activate_plugin( $plugin );

	//              if ( is_wp_error( $result ) ) {
	//                  // $status['errorCode']    = $result->get_error_code();
	//                  // $status['errorMessage'] = $result->get_error_message();
	//                  // wp_send_json_error( $status );
	//                  $error[ $pg[0] ] = 'Error activating plugin : ' . esc_html( $result->get_error_message() );
	//              }

	//              $status[ $pg[0] ] = $plugin_data['Name'] . ' activated.';
	//          } else {
	//              $status[ $pg[0] ] = $plugin_data['Name'] . ' already activated.';
	//          }
	//          continue;
	//      }
	//      $api = plugins_api(
	//          'plugin_information',
	//          array(
	//              'slug' => sanitize_key( wp_unslash( $pg[0] ) ),
	//          )
	//      );
	//      if ( is_wp_error( $api ) ) {
	//          $error[ $pg[0] ] = 'Error fetching plugin information: ' . esc_html( $api->get_error_message() );
	//          // return new WP_Error( 'rest_custom_error', 'Error fetching plugin information: ' . esc_html( $api->get_error_message() ), array( 'status' => 400 ) );
	//      }

	//      $skin      = new WP_Ajax_Upgrader_Skin();
	//      $upgrader  = new Plugin_Upgrader( $skin );
	//      $installed = $upgrader->install( $api->download_link );

	//      if ( is_wp_error( $installed ) ) {
	//          $error[ $pg[0] ] = 'Failed to install the plugin: ' . esc_html( $installed->get_error_message() );
	//          // return new WP_Error( 'rest_custom_error', 'Failed to install the plugin: ' . esc_html( $installed->get_error_message() ), array( 'status' => 400 ) );
	//      }

	//      $install_status = install_plugin_install_status( $api );

	//      if ( is_plugin_inactive( $install_status['file'] ) ) {
	//          $result = activate_plugin( $install_status['file'] );

	//          if ( is_wp_error( $result ) ) {
	//              // $status['errorCode']    = $result->get_error_code();
	//              // $status['errorMessage'] = $result->get_error_message();
	//              // wp_send_json_error( $status );
	//              // return new WP_Error( $result->get_error_code(), 'Error activating plugin : ' . esc_html( $result->get_error_message() ), array( 'status' => 400 ) );
	//              $error[ $pg[0] ] = 'Error activating plugin : ' . esc_html( $result->get_error_message() );
	//          }
	//      }
	//      $status[ $pg[0] ] = $api->name . ' installed.';

	//  }

	//  return new WP_REST_Response( array( 'success' => $status ), 200 );
	// }

	public function import_evf() {
		$plugin = 'everest-forms/everest-forms.php';
		if ( file_exists( WP_PLUGIN_DIR . '/' . $plugin ) ) {
			$plugin_data = get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin );
			if ( is_plugin_inactive( $plugin ) ) {
				$result = activate_plugin( $plugin );

				if ( is_wp_error( $result ) ) {
					// $status['errorCode']    = $result->get_error_code();
					// $status['errorMessage'] = $result->get_error_message();
					// wp_send_json_error( $status );
					return new WP_Error( $result->get_error_code(), 'Error activating plugin : ' . esc_html( $result->get_error_message() ), array( 'status' => 400 ) );
				}
				return new WP_REST_Response( array( 'success' => $plugin_data['Name'] . ' Activated.' ), 200 );
			}
			return new WP_REST_Response( array( 'success' => $plugin_data['Name'] . ' already activated.' ), 200 );
		}
		$pg      = explode( '/', $plugin );
			$api = plugins_api(
				'plugin_information',
				array(
					'slug' => sanitize_key( wp_unslash( $pg[0] ) ),
				)
			);
		if ( is_wp_error( $api ) ) {
			return new WP_Error( 'rest_custom_error', 'Error fetching plugin information: ' . esc_html( $api->get_error_message() ), array( 'status' => 400 ) );
		}

		$skin      = new WP_Ajax_Upgrader_Skin();
		$upgrader  = new Plugin_Upgrader( $skin );
		$installed = $upgrader->install( $api->download_link );

		if ( is_wp_error( $installed ) ) {
			return new WP_Error( 'rest_custom_error', 'Failed to install the plugin: ' . esc_html( $installed->get_error_message() ), array( 'status' => 400 ) );
		}

		$install_status = install_plugin_install_status( $api );

		if ( is_plugin_inactive( $install_status['file'] ) ) {
			$result = activate_plugin( $install_status['file'] );

			if ( is_wp_error( $result ) ) {
				return new WP_Error( $result->get_error_code(), 'Error activating plugin : ' . esc_html( $result->get_error_message() ), array( 'status' => 400 ) );

			}
		}

		return new WP_REST_Response( array( 'success' => 'Everest Form Installed.' ), 200 );
	}

	public function update_nav_menu_items() {
		$menu_locations = get_nav_menu_locations();

		foreach ( $menu_locations as $location => $menu_id ) {
			if ( is_nav_menu( $menu_id ) ) {
				$menu_items = wp_get_nav_menu_items( $menu_id, array( 'post_status' => 'any' ) );

				if ( ! empty( $menu_items ) ) {
					foreach ( $menu_items as $menu_item ) {
						if ( isset( $menu_item->url ) && isset( $menu_item->db_id ) && 'custom' === $menu_item->type ) {
							$site_parts = wp_parse_url( home_url( '/' ) );
							$menu_parts = wp_parse_url( $menu_item->url );

							// Update existing custom nav menu item URL.
							if ( isset( $menu_parts['path'] ) && isset( $menu_parts['host'] ) && apply_filters( 'themegrill_demo_importer_nav_menu_item_url_hosts', in_array( $menu_parts['host'], array( 'demo.themegrill.com', 'zakrademos.com' ) ) ) ) {
								$menu_item->url = str_replace( array( $menu_parts['scheme'], $menu_parts['host'], $menu_parts['path'] ), array( $site_parts['scheme'], $site_parts['host'], trailingslashit( $site_parts['path'] ) ), $menu_item->url );
								update_post_meta( $menu_item->db_id, '_menu_item_url', esc_url_raw( $menu_item->url ) );
							}
						}
					}
				}
			}
		}
	}

	public function update_widget_data( $widget, $widget_type, $instance_id, $demo_data ) {
		if ( 'nav_menu' === $widget_type ) {
			// $menu     = isset( $widget['title'] ) ? $widget['title'] : $this->importer->get_term_new_id( $widget['nav_menu'] );
			$menu     = $this->importer->get_term_new_id( $widget['nav_menu'] );
			$nav_menu = wp_get_nav_menu_object( $menu );
			if ( is_object( $nav_menu ) && $nav_menu->term_id ) {
				$widget['nav_menu'] = $nav_menu->term_id;
			}
		} elseif ( ! empty( $demo_data['widgets_data_update'] ) ) {
			foreach ( $demo_data['widgets_data_update'] as $dropdown_type => $dropdown_data ) {
				if ( ! in_array( $dropdown_type, array( 'dropdown_pages', 'dropdown_categories' ), true ) ) {
					continue;
				}

				// Format the value based on dropdown type.
				switch ( $dropdown_type ) {
					case 'dropdown_pages':
						foreach ( $dropdown_data as $widget_id => $widget_data ) {
							if ( ! empty( $widget_data[ $instance_id ] ) && $widget_id === $widget_type ) {
								foreach ( $widget_data[ $instance_id ] as $widget_key => $widget_value ) {
									$page = $this->get_page_by_title( $widget_value );

									if ( is_object( $page ) && $page->ID ) {
										$widget[ $widget_key ] = $page->ID;
									}
								}
							}
						}
						break;
					default:
					case 'dropdown_categories':
						foreach ( $dropdown_data as $taxonomy => $taxonomy_data ) {
							if ( ! taxonomy_exists( $taxonomy ) ) {
								continue;
							}

							foreach ( $taxonomy_data as $widget_id => $widget_data ) {
								if ( ! empty( $widget_data[ $instance_id ] ) && $widget_id === $widget_type ) {
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

	public function update_customizer_data( $data, $demo_data ) {
		if ( ! empty( $demo_data['customizer_data_update'] ) ) {
			foreach ( $demo_data['customizer_data_update'] as $data_type => $data_value ) {
				if ( ! in_array( $data_type, array( 'pages', 'categories', 'nav_menu_locations' ), true ) ) {
					continue;
				}

				// Format the value based on data type.
				switch ( $data_type ) {
					case 'pages':
						foreach ( $data_value as $option_key => $option_value ) {
							if ( ! empty( $data['mods'][ $option_key ] ) ) {
								$page = $this->get_page_by_title( $option_value );

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
										if ( $nav_menu->slug === $location_name ) {
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

	public function update_elementor_data( $demo_id, $demo_data ) {
		if ( ! empty( $demo_data['elementor_data_update'] ) ) {
			foreach ( $demo_data['elementor_data_update'] as $data_type => $data_value ) {
				if ( ! empty( $data_value['post_title'] ) ) {
					$page = $this->get_page_by_title( $data_value['post_title'] );

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

	public function elementor_recursive_update( $elementor_data, $data_type, $data_value ) {
		$elementor_data = json_decode( stripslashes( $elementor_data ), true );

		// Recursively update elementor data.
		foreach ( $elementor_data as $element_id => $element_data ) {
			if ( ! empty( $element_data['elements'] ) ) {
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
									$elementor_data[ $element_id ]['elements'][ $el_key ]['elements'][ $el_child_key ]['settings']['categories_selected'] = $categories_selected;
								}
							}
						}
					}
				}
			}
		}

		return wp_json_encode( $elementor_data );
	}

	public function update_siteorigin_data( $demo_id, $demo_data ) {
		if ( ! empty( $demo_data['siteorigin_panels_data_update'] ) ) {
			foreach ( $demo_data['siteorigin_panels_data_update'] as $data_type => $data_value ) {
				if ( ! empty( $data_value['post_title'] ) ) {
					$page = $this->get_page_by_title( $data_value['post_title'] );

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
							$instance          = $instance + 1;
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
														$page = $this->get_page_by_title( $widget_value );

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
}
