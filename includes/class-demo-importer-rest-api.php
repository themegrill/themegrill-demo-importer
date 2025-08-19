<?php

// Include WXR Importer.
require_once __DIR__ . '/importers/wordpress-importer/class-wxr-importer.php';

class TG_Importer_REST_Controller extends WP_REST_Controller {
	protected $namespace = 'tg-demo-importer/v1';

	protected $fetch_attachments = true;

	public function __construct() {
		$this->includes();
		add_action( 'themegrill_ajax_demo_imported', array( $this, 'update_nav_menu_items' ) );
		// add_action( 'themegrill_ajax_demo_imported', array( $this, 'update_elementor_data' ), 10, 2 );
		// add_action( 'themegrill_ajax_demo_imported', array( $this, 'update_siteorigin_data' ), 10, 2 );
		add_action( 'themegrill_import_customizer', array( $this, 'update_additional_settings' ), 10, 2 );
		add_filter( 'themegrill_widget_import_settings', array( $this, 'update_widget_data' ), 10, 4 );
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
			'/data',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'tgdi_site_data' ),
					'permission_callback' => '__return_true',
				),
			)
		);
		register_rest_route(
			$this->namespace,
			'/install',
			array(
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'install' ),
					'permission_callback' => function () {
						return current_user_can( 'install_themes' );
					},
					'args'                => array(
						'action' => array(
							'type'     => 'string',
							'required' => 'true',
							'enum'     => array( 'install-theme', 'install-plugins', 'import-content', 'import-customizer', 'import-widgets', 'complete' ),
						),
						// 'complete' => array(
						//  'type'     => 'boolean',
						//  'required' => true,
						//  'default'  => false,
						// ),
						// 'demo-data' => [

						// ],
						// 'opts'   => array(
						//  'type'       => 'object',
						//  'required'   => false,
						//  'properties' => array(
						//      'force_install_theme' => array(
						//          'type'    => 'boolean',
						//          'default' => true,
						//      ),
						//      'blogname'            => array(
						//          'type' => 'string',
						//      ),
						//      'blogdescription'     => array(
						//          'type' => 'string',
						//      ),
						//      'logo'                => array(
						//          'type' => 'number',
						//      ),
						//  ),
						// ),
					),
				),
			)
		);
		register_rest_route(
			$this->namespace,
			'/cleanup',
			array(
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'tgdi_cleanup' ),
					'permission_callback' => function () {
						return current_user_can( 'install_themes' );
					},
				),
			)
		);
		register_rest_route(
			$this->namespace,
			'/activate-pro',
			array(
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'tgdi_activate_pro' ),
					'permission_callback' => function () {
						return current_user_can( 'install_themes' );
					},
				),
			)
		);
		register_rest_route(
			$this->namespace,
			'/localized-data',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'tgdi_get_localized_data' ),
					'permission_callback' => '__return_true',
				),
			)
		);
	}

	public function tgdi_site_data( $request ) {
		$site       = $request->get_param( 'slug' );
		$demo_theme = $request->get_param( 'theme' );
		$base_url   = '';

		if ( 'zakra' === $demo_theme ) {
			$base_url = TG_Demo_Importer::$zakra_base_url;
		} else {
			$base_url = TG_Demo_Importer::$themegrill_base_url;
		}

		$api_url = $base_url . '/' . $site . TG_Demo_Importer::$namespace . '/sites/data';

		$site_data = wp_remote_get(
			$api_url,
			array(
				'headers'   => array(
					'User-Agent'   => 'ThemeGrill/1.0',
					'Content-Type' => 'application/json',
				),
				'sslverify' => false,
			)
		);

		if ( is_wp_error( $site_data ) ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => $site_data->get_error_message(),
				),
				200
			);
		}
		$data = json_decode( wp_remote_retrieve_body( $site_data ) );

		if ( json_last_error() !== JSON_ERROR_NONE ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => 'Invalid JSON in API response.',
				),
				500
			);
		}

		if ( empty( $data ) ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => 'No data found.',
				),
				200
			);
		}

		return new WP_REST_Response(
			array(
				'success' => true,
				'data'    => $data,
			),
			200
		);
	}

	public function tgdi_get_localized_data( $request ) {
		$refetch        = (bool) $request->get_param( 'refetch' ) ?? false;
		$localized_data = TG_Demo_Importer::get_localized_data();
		$demos          = TG_Demo_Importer::get_demo_packages( $refetch );

		if ( array_key_exists( 'message', $demos ) ) {
				$localized_data['data']      = array();
				$localized_data['error_msg'] = $demos['message'];
		} else {
			$localized_data['data'] = $demos;
		}
		return $localized_data;
	}

	public function tgdi_activate_pro( $request ) {
		$slug = $request['slug'] ?? '';

		if ( empty( $slug ) ) {
			return new WP_Error(
				'invalid_slug',
				__( 'Invalid slug provided', 'themegrill-demo-importer' ),
				array( 'status' => 500 )
			);
		}

		if ( 'zakra-pro' === $slug ) {
			activate_plugin( 'zakra-pro/zakra-pro.php' );
		} else {
			switch_theme( $slug );
		}

		return new WP_REST_Response(
			array(
				'success' => true,
				'message' => __( 'Pro activated successfully.', 'themegrill-demo-importer' ),
			),
			200
		);
	}

	public function tgdi_cleanup() {
		$imported_posts = get_option( 'themegrill_demo_importer_imported_posts', array() );
		$imported_terms = get_option( 'themegrill_demo_importer_imported_terms', array() );

		// Prevents elementor from breaking the cleaning process.
		$_GET['force_delete_kit'] = true;

		foreach ( $imported_posts as $post_id ) {
			// Delete post attachments
			$attachments = get_attached_media( '', $post_id );
			if ( ! empty( $attachments ) ) {
				foreach ( $attachments as $attachment ) {
					wp_delete_attachment( $attachment->ID, true );
				}
			}

			wp_delete_post( $post_id, true );
		}

		foreach ( $imported_terms as $term_id ) {
			$term = get_term( $term_id );
			if ( $term && ! is_wp_error( $term ) ) {
				// Delete term meta
				$term_meta = get_term_meta( $term_id );
				if ( ! empty( $term_meta ) ) {
					foreach ( $term_meta as $meta_key => $meta_value ) {
						delete_term_meta( $term_id, $meta_key );
					}
				}

				// Delete the term
				wp_delete_term( $term_id, $term->taxonomy );
			}
		}

		delete_option( 'themegrill_demo_importer_imported_posts' );
		delete_option( 'themegrill_demo_importer_imported_terms' );

		return new WP_REST_Response(
			array(
				'success' => true,
				'message' => __( 'Cleaned up successfully.', 'themegrill-demo-importer' ),
			),
			200
		);
	}

	public function install( $request ) {
		$action = $request['action'] ?? '';
		if ( ! $action ) {
			$this->tgdi_log_error( 'Invalid action provided' );

			return new WP_Error(
				'invalid_action',
				__( 'Invalid action provided', 'themegrill-demo-importer' ),
				array( 'status' => 500 )
			);
		}
		$demo_config = $request['demo_config'] ?? array();

		if ( ! $demo_config ) {
			$this->tgdi_log_error( 'Invalid demo config provided' );

			return new WP_Error(
				'invalid_demo_config',
				__( 'Invalid demo config provided', 'themegrill-demo-importer' ),
				array( 'status' => 500 )
			);
		}
		$options     = $request['opts'] ?? array();
		$pagebuilder = $options['pagebuilder'];

		/** @var WP_REST_Response|WP_Error $response */
		$response = null;
		switch ( $action ) {
			case 'install-plugins':
				$plugins  = $options['plugins'] ?? array();
				$result   = array_map(
					function ( $plugin ) {
						return $this->install_activate_plugin( $plugin );
					},
					$plugins
				);
				$response = rest_ensure_response( $result );
				break;
			case 'import-content':
				$this->tgdi_cleanup(); //delete all previous imported data if any
				$pages    = $options['pages'] ?? array();
				$response = $this->import_content( $demo_config, $pages );
				break;
			case 'import-customizer':
				$args     = array(
					'blogname'        => $options['blogname'] ?? '',
					'blogdescription' => $options['blogdescription'] ?? '',
					'custom_logo'     => $options['custom_logo'] ?? 0,
				);
				$response = $this->import_customizer( $demo_config );
				if ( ! is_wp_error( $response ) ) {
					do_action( 'themegrill_import_customizer', $demo_config, $args );
				}
				break;
			case 'import-widgets':
				$response = $this->import_widget( $demo_config );
				break;
			case 'complete':
				update_option( 'themegrill_demo_importer_activated_id', $demo_config['slug'] );

				do_action( 'themegrill_ajax_demo_imported', $demo_config['slug'], $demo_config );
				flush_rewrite_rules();
				wp_cache_flush();
				$response = rest_ensure_response(
					array(
						'success' => true,
						'message' => 'Demo Imported successfully.',
					),
					200
				);
				break;
		}

		return rest_ensure_response( $response );
	}


	public function install_activate_plugin( $plugin ) {
		$pg          = explode( '/', $plugin );
		$plugin_file = WP_PLUGIN_DIR . '/' . $plugin;
		$results     = array();
		if ( 'companion-elementor/companion-elementor.php' === $plugin ) {
			$response = apply_filters( 'tgda_install_companion_elementor', 'companion-elementor/companion-elementor.php' );
			if ( is_array( $response ) ) {
				if ( isset( $response['success'] ) && $response['success'] ) {
					$results[ $pg[0] ] = array(
						'status'  => 'success',
						/* translators: %s Plugin name */
						'message' => __( 'Companion Elementor installed and activated.', 'themegrill-demo-importer' ),
					);
				} else {
					$this->tgdi_log_error( 'Failed to install plugin ' . $pg[0] . ': ' . $response['message'] );
					$results[ $pg[0] ] = array(
						'status'  => 'error',
						'message' => $response['message'],
					);
				}
			} else {
				$this->tgdi_log_error( 'Failed to install Companion Elementor.' );
				$results[ $pg[0] ] = array(
					'status'  => 'error',
					'message' => 'Failed to install Companion Elementor.',
				);
			}
		} else {
			if ( file_exists( $plugin_file ) ) {
				$plugin_data = get_plugin_data( $plugin_file );

				if ( is_plugin_active( $plugin ) ) {
					$results[ $pg[0] ] = array(
						'status'  => 'success',
						'message' => $plugin_data['Name'] . ' already activated.',
					);
					return $results;
				}
				$result = activate_plugin( $plugin );

				if ( is_wp_error( $result ) ) {
					$this->tgdi_log_error( 'Failed to activate plugin ' . $plugin . ': ' . $result->get_error_message() );

					$results[ $pg[0] ] = array(
						'status'  => 'error',
						'message' => $result->get_error_message(),
					);
				}
				$results[ $pg[0] ] = array(
					'status'  => 'success',
					'message' => $plugin_data['Name'] . ' activated.',
				);
				return $results;
			}
			$api = plugins_api(
				'plugin_information',
				array(
					'slug' => sanitize_key( wp_unslash( $pg[0] ) ),
				)
			);
			if ( is_wp_error( $api ) ) {
				$this->tgdi_log_error( 'Failed to fetch plugin info for ' . $pg[0] . ': ' . $api->get_error_message() );

				$results[ $pg[0] ] = array(
					'status'  => 'error',
					'message' => $api->get_error_message(),
				);

				return $results;

			}

			$skin      = new WP_Ajax_Upgrader_Skin();
			$upgrader  = new Plugin_Upgrader( $skin );
			$installed = $upgrader->install( $api->download_link );

			if ( is_wp_error( $installed ) ) {
				$this->tgdi_log_error( 'Failed to install plugin ' . $pg[0] . ': ' . $installed->get_error_message() );

				$results[ $pg[0] ] = array(
					'status'  => 'error',
					'message' => $installed->get_error_message(),
				);
				return $results;

			}

			$install_status = install_plugin_install_status( $api );

			if ( is_plugin_inactive( $install_status['file'] ) ) {
				$result = activate_plugin( $install_status['file'] );

				if ( is_wp_error( $result ) ) {
					$this->tgdi_log_error( 'Failed to activate plugin after install ' . $pg[0] . ': ' . $result->get_error_message() );

					$results[ $pg[0] ] = array(
						'status'  => 'error',
						'message' => $result->get_error_message(),
					);
									return $results;

				}
			}
			$results[ $pg[0] ] = array(
				'status'  => 'success',
				/* translators: %s Plugin name */
				'message' => sprintf( __( '%s installed and activated.', 'themegrill-demo-importer' ), $api->name ),
			);
		}
		return $results;
	}

	public function import_content( $demo, $pages ) {
		do_action( 'themegrill_ajax_before_demo_import' );
		if ( $pages ) {
			foreach ( $pages as $page ) {
				$this->import_xml( $page['content'] );
			}
		} else {
			$content = $demo['content'];
			if ( ! $content ) {
				$this->tgdi_log_error( 'No XML content file provided for import.' );
				return new WP_Error( 'no_content_file', 'No content file.', array( 'status' => 500 ) );
			}
			$response = $this->import_xml( $content );
			if ( is_wp_error( $response ) ) {
				$this->tgdi_log_error( 'Error importing content: ' . $response->get_error_message() );
				return $response;
			}
		}
		$this->import_core_options( $demo );

		return new WP_REST_Response(
			array(
				'success' => true,
				'message' => 'Content Imported.',
			),
			200
		);
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
		TG_Demo_Importer::$importer->set_logger( $logger );
		ob_start();
		$data = TG_Demo_Importer::$importer->import( $content );
		ob_end_clean();

		update_option( 'themegrill_demo_importer_mapping', TG_Demo_Importer::$importer->get_mapping_data() );

		if ( is_wp_error( $data ) ) {
			return new WP_Error( 'import_content_failed', 'Error importing content:' . $data, array( 'status' => 500 ) );
		}

		return true;
	}

	public function import_core_options( $demo ) {
		$show_on_front  = $demo['show_on_front'] ?? '';
		$page_on_front  = $demo['page_on_front'] ?? '';
		$page_for_posts = $demo['page_for_posts'] ?? '';
		if ( $show_on_front ) {
			if ( in_array( $show_on_front, array( 'posts', 'page' ), true ) ) {
							update_option( 'show_on_front', $show_on_front );
			}
		}
		if ( $page_on_front ) {
			$page = $this->get_page_by_title( $page_on_front );

			if ( is_object( $page ) && $page->ID ) {
				update_option( 'page_on_front', $page->ID );
			}
		}
		if ( $page_for_posts ) {
			$page = $this->get_page_by_title( $page_for_posts );

			if ( is_object( $page ) && $page->ID ) {
				update_option( 'page_for_posts', $page->ID );
				update_option( 'show_on_front', 'page' );
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

	public function import_customizer( $demo ) {
		if ( ! $demo['themeMods'] ) {
			return;
		}
		$mapping_data = get_option( 'themegrill_demo_importer_mapping', array() );
		$term_id_map  = array();
		if ( ! empty( $mapping_data ) ) {
			$term_id_map = $mapping_data['term_id'] ?? array();
		}
		$import = TG_Customizer_Importer::import( $demo['themeMods'], $demo['slug'], $demo, $term_id_map );
		if ( is_wp_error( $import ) ) {
			$this->tgdi_log_error( 'Error importing customizer: ' . $import->get_error_message() );
			return new WP_Error( 'import_customizer_failed', 'Error importing customizer.', array( 'status' => 500 ) );
		}
		return new WP_REST_Response(
			array(
				'success' => true,
				'message' => 'Customizer Imported.',
			),
			200
		);
	}

	public function import_widget( $demo ) {
		if ( ! $demo['widgets'] ) {
			return;
		}
		$mapping_data = get_option( 'themegrill_demo_importer_mapping', array() );
		$term_id_map  = array();
		if ( ! empty( $mapping_data ) ) {
			$term_id_map = $mapping_data['term_id'] ?? array();
		}
		$import = TG_Widget_Importer::import( $demo['widgets'], $demo['slug'], $demo, $term_id_map );
		if ( is_wp_error( $import ) ) {
			$this->tgdi_log_error( 'Error importing widget: ' . $import->get_error_message() );
			return new WP_Error( 'import_widget_failed', 'Error importing widget.', array( 'status' => 500 ) );
		}
		return new WP_REST_Response(
			array(
				'success' => true,
				'message' => 'Widget Imported.',
			),
			200
		);
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
		if ( ! empty( $widget ) ) {
			if ( 'nav_menu' === $widget_type ) {
				$mapping_data     = get_option( 'themegrill_demo_importer_mapping', array() );
				$term_mapped_data = array();
				if ( ! empty( $mapping_data ) ) {
					$term_mapped_data = $mapping_data['term_id'] ?? array();
				}
				if ( ! empty( $term_mapped_data ) ) {
					$menu     = $term_mapped_data[ $widget['nav_menu'] ] ?? '';
					$nav_menu = wp_get_nav_menu_object( $menu );
					if ( is_object( $nav_menu ) && $nav_menu->term_id ) {
						$widget['nav_menu'] = $nav_menu->term_id;
					}
				}
			}

			return $widget;
		}
	}

	public function update_customizer_data( $data, $demo_data ) {
		// if ( ! empty( $demo_data['customizer_data_update'] ) ) {
		//  foreach ( $demo_data['customizer_data_update'] as $data_type => $data_value ) {
		//      if ( ! in_array( $data_type, array( 'pages', 'categories', 'nav_menu_locations' ), true ) ) {
		//          continue;
		//      }

		//      // Format the value based on data type.
		//      switch ( $data_type ) {
		//          case 'pages':
		//              foreach ( $data_value as $option_key => $option_value ) {
		//                  if ( ! empty( $data['mods'][ $option_key ] ) ) {
		//                      $page = $this->get_page_by_title( $option_value );

		//                      if ( is_object( $page ) && $page->ID ) {
		//                          $data['mods'][ $option_key ] = $page->ID;
		//                      }
		//                  }
		//              }
		//              break;
		//          case 'categories':
		//              foreach ( $data_value as $taxonomy => $taxonomy_data ) {
		//                  if ( ! taxonomy_exists( $taxonomy ) ) {
		//                      continue;
		//                  }

		//                  foreach ( $taxonomy_data as $option_key => $option_value ) {
		//                      if ( ! empty( $data['mods'][ $option_key ] ) ) {
		//                          $term = get_term_by( 'name', $option_value, $taxonomy );

		//                          if ( is_object( $term ) && $term->term_id ) {
		//                              $data['mods'][ $option_key ] = $term->term_id;
		//                          }
		//                      }
		//                  }
		//              }
		//              break;
		//          case 'nav_menu_locations':
		//              $nav_menus = wp_get_nav_menus();

		//              if ( ! empty( $nav_menus ) ) {
		//                  foreach ( $nav_menus as $nav_menu ) {
		//                      if ( is_object( $nav_menu ) ) {
		//                          foreach ( $data_value as $location => $location_name ) {
		//                              if ( $nav_menu->slug === $location_name ) {
		//                                  $data['mods'][ $data_type ][ $location ] = $nav_menu->term_id;
		//                              }
		//                          }
		//                      }
		//                  }
		//              }
		//              break;
		//      }
		//  }
		// }
		$nav_menus = wp_get_nav_menus();

		if ( ! empty( $nav_menus ) ) {
			foreach ( $nav_menus as $nav_menu ) {
				if ( is_object( $nav_menu ) ) {
					foreach ( $data['nav_menu_locations'] as $location => $id ) {
						if ( $nav_menu->slug === $location ) {
							$data['nav_menu_locations'][ $location ] = $nav_menu->term_id;
						}
					}
				}
			}
		}
		return $data;
	}

	// public function update_elementor_data( $demo_id, $demo_data ) {
	//  if ( ! empty( $demo_data['elementor_data_update'] ) ) {
	//      foreach ( $demo_data['elementor_data_update'] as $data_type => $data_value ) {
	//          if ( ! empty( $data_value['post_title'] ) ) {
	//              $page = $this->get_page_by_title( $data_value['post_title'] );

	//              if ( is_object( $page ) && $page->ID ) {
	//                  $elementor_data = get_post_meta( $page->ID, '_elementor_data', true );

	//                  if ( ! empty( $elementor_data ) ) {
	//                      $elementor_data = $this->elementor_recursive_update( $elementor_data, $data_type, $data_value );
	//                  }

	//                  // Update elementor data.
	//                  update_post_meta( $page->ID, '_elementor_data', $elementor_data );
	//              }
	//          }
	//      }
	//  }
	// }

	// public function elementor_recursive_update( $elementor_data, $data_type, $data_value ) {
	//  $elementor_data = json_decode( stripslashes( $elementor_data ), true );

	//  // Recursively update elementor data.
	//  foreach ( $elementor_data as $element_id => $element_data ) {
	//      if ( ! empty( $element_data['elements'] ) ) {
	//          foreach ( $element_data['elements'] as $el_key => $el_data ) {
	//              if ( ! empty( $el_data['elements'] ) ) {
	//                  foreach ( $el_data['elements'] as $el_child_key => $child_el_data ) {
	//                      if ( 'widget' === $child_el_data['elType'] ) {
	//                          $settings   = isset( $child_el_data['settings'] ) ? $child_el_data['settings'] : array();
	//                          $widgetType = isset( $child_el_data['widgetType'] ) ? $child_el_data['widgetType'] : '';

	//                          if ( isset( $settings['display_type'] ) && 'categories' === $settings['display_type'] ) {
	//                              $categories_selected = isset( $settings['categories_selected'] ) ? $settings['categories_selected'] : '';

	//                              if ( ! empty( $data_value['data_update'] ) ) {
	//                                  foreach ( $data_value['data_update'] as $taxonomy => $taxonomy_data ) {
	//                                      if ( ! taxonomy_exists( $taxonomy ) ) {
	//                                          continue;
	//                                      }

	//                                      foreach ( $taxonomy_data as $widget_id => $widget_data ) {
	//                                          if ( ! empty( $widget_data ) && $widget_id == $widgetType ) {
	//                                              if ( is_array( $categories_selected ) ) {
	//                                                  foreach ( $categories_selected as $cat_key => $cat_id ) {
	//                                                      if ( isset( $widget_data[ $cat_id ] ) ) {
	//                                                          $term = get_term_by( 'name', $widget_data[ $cat_id ], $taxonomy );

	//                                                          if ( is_object( $term ) && $term->term_id ) {
	//                                                              $categories_selected[ $cat_key ] = $term->term_id;
	//                                                          }
	//                                                      }
	//                                                  }
	//                                              } elseif ( isset( $widget_data[ $categories_selected ] ) ) {
	//                                                  $term = get_term_by( 'name', $widget_data[ $categories_selected ], $taxonomy );

	//                                                  if ( is_object( $term ) && $term->term_id ) {
	//                                                      $categories_selected = $term->term_id;
	//                                                  }
	//                                              }
	//                                          }
	//                                      }
	//                                  }
	//                              }

	//                              // Update the elementor data.
	//                              $elementor_data[ $element_id ]['elements'][ $el_key ]['elements'][ $el_child_key ]['settings']['categories_selected'] = $categories_selected;
	//                          }
	//                      }
	//                  }
	//              }
	//          }
	//      }
	//  }

	//  return wp_json_encode( $elementor_data );
	// }

	// public function update_siteorigin_data( $demo_id, $demo_data ) {
	//  if ( ! empty( $demo_data['siteorigin_panels_data_update'] ) ) {
	//      foreach ( $demo_data['siteorigin_panels_data_update'] as $data_type => $data_value ) {
	//          if ( ! empty( $data_value['post_title'] ) ) {
	//              $page = $this->get_page_by_title( $data_value['post_title'] );

	//              if ( is_object( $page ) && $page->ID ) {
	//                  $panels_data = get_post_meta( $page->ID, 'panels_data', true );

	//                  if ( ! empty( $panels_data ) ) {
	//                      $panels_data = $this->siteorigin_recursive_update( $panels_data, $data_type, $data_value );
	//                  }

	//                  // Update siteorigin panels data.
	//                  update_post_meta( $page->ID, 'panels_data', $panels_data );
	//              }
	//          }
	//      }
	//  }
	// }

	// public function siteorigin_recursive_update( $panels_data, $data_type, $data_value ) {
	//  static $instance = 0;

	//  foreach ( $panels_data as $panel_type => $panel_data ) {
	//      // Format the value based on panel type.
	//      switch ( $panel_type ) {
	//          case 'grids':
	//              foreach ( $panel_data as $instance_id => $grid_instance ) {
	//                  if ( ! empty( $data_value['data_update']['grids_data'] ) ) {
	//                      foreach ( $data_value['data_update']['grids_data'] as $grid_id => $grid_data ) {
	//                          if ( ! empty( $grid_data['style'] ) && $instance_id === $grid_id ) {
	//                              $level = isset( $grid_data['level'] ) ? $grid_data['level'] : (int) 0;
	//                              if ( $level == $instance ) {
	//                                  foreach ( $grid_data['style'] as $style_key => $style_value ) {
	//                                      if ( empty( $style_value ) ) {
	//                                          continue;
	//                                      }

	//                                      // Format the value based on style key.
	//                                      switch ( $style_key ) {
	//                                          case 'background_image_attachment':
	//                                              $attachment_id = tg_get_attachment_id( $style_value );

	//                                              if ( 0 !== $attachment_id ) {
	//                                                  $grid_instance['style'][ $style_key ] = $attachment_id;
	//                                              }
	//                                              break;
	//                                          default:
	//                                              $grid_instance['style'][ $style_key ] = $style_value;
	//                                              break;
	//                                      }
	//                                  }
	//                              }
	//                          }
	//                      }
	//                  }

	//                  // Update panel grids data.
	//                  $panels_data['grids'][ $instance_id ] = $grid_instance;
	//              }
	//              break;

	//          case 'widgets':
	//              foreach ( $panel_data as $instance_id => $widget_instance ) {
	//                  if ( isset( $widget_instance['panels_data']['widgets'] ) ) {
	//                      $instance          = $instance + 1;
	//                      $child_panels_data = $widget_instance['panels_data'];
	//                      $panels_data['widgets'][ $instance_id ]['panels_data'] = $this->siteorigin_recursive_update( $child_panels_data, $data_type, $data_value );
	//                      $instance = $instance - 1;
	//                      continue;
	//                  }

	//                  if ( isset( $widget_instance['nav_menu'] ) && isset( $widget_instance['title'] ) ) {
	//                      $nav_menu = wp_get_nav_menu_object( $widget_instance['title'] );

	//                      if ( is_object( $nav_menu ) && $nav_menu->term_id ) {
	//                          $widget_instance['nav_menu'] = $nav_menu->term_id;
	//                      }
	//                  } elseif ( ! empty( $data_value['data_update']['widgets_data'] ) ) {
	//                      $instance_class = $widget_instance['panels_info']['class'];

	//                      foreach ( $data_value['data_update']['widgets_data'] as $dropdown_type => $dropdown_data ) {
	//                          if ( ! in_array( $dropdown_type, array( 'dropdown_pages', 'dropdown_categories' ) ) ) {
	//                              continue;
	//                          }

	//                          // Format the value based on data type.
	//                          switch ( $dropdown_type ) {
	//                              case 'dropdown_pages':
	//                                  foreach ( $dropdown_data as $widget_id => $widget_data ) {
	//                                      if ( ! empty( $widget_data[ $instance_id ] ) && $widget_id == $instance_class ) {
	//                                          $level = isset( $widget_data['level'] ) ? $widget_data['level'] : (int) 0;

	//                                          if ( $level == $instance ) {
	//                                              foreach ( $widget_data[ $instance_id ] as $widget_key => $widget_value ) {
	//                                                  $page = $this->get_page_by_title( $widget_value );

	//                                                  if ( is_object( $page ) && $page->ID ) {
	//                                                      $widget_instance[ $widget_key ] = $page->ID;
	//                                                  }
	//                                              }
	//                                          }
	//                                      }
	//                                  }
	//                                  break;
	//                              case 'dropdown_categories':
	//                                  foreach ( $dropdown_data as $taxonomy => $taxonomy_data ) {
	//                                      if ( ! taxonomy_exists( $taxonomy ) ) {
	//                                          continue;
	//                                      }

	//                                      foreach ( $taxonomy_data as $widget_id => $widget_data ) {
	//                                          if ( ! empty( $widget_data[ $instance_id ] ) && $widget_id == $instance_class ) {
	//                                              $level = isset( $widget_data['level'] ) ? $widget_data['level'] : (int) 0;

	//                                              if ( $level == $instance ) {
	//                                                  foreach ( $widget_data[ $instance_id ] as $widget_key => $widget_value ) {
	//                                                      $term = get_term_by( 'name', $widget_value, $taxonomy );

	//                                                      if ( is_object( $term ) && $term->term_id ) {
	//                                                          $widget_instance[ $widget_key ] = $term->term_id;
	//                                                      }
	//                                                  }
	//                                              }
	//                                          }
	//                                      }
	//                                  }
	//                                  break;
	//                          }
	//                      }
	//                  }

	//                  $panels_data['widgets'][ $instance_id ] = $widget_instance;
	//              }
	//              break;
	//      }
	//  }

	//  return $panels_data;
	// }

	public function update_additional_settings( $demo_config, $args ) {
		if ( $args['blogname'] ) {
			update_option( 'blogname', $args['blogname'] );
		}

		if ( $args['blogdescription'] ) {
			update_option( 'blogdescription', $args['blogdescription'] );
		}

		if ( $args['custom_logo'] ) {
			$theme_mods = get_theme_mods();
			$post_id    = $theme_mods['custom_logo'] ?? null;

			if ( $post_id ) {
				set_theme_mod( 'custom_logo', $args['custom_logo'] );
			}
		}
	}

	public function tgdi_log_error( $message ) {
		$upload_dir = wp_upload_dir();
		$log_dir    = trailingslashit( $upload_dir['basedir'] ) . 'tgdi-logs';

		if ( ! file_exists( $log_dir ) ) {
			wp_mkdir_p( $log_dir );
		}

		$log_file  = trailingslashit( $log_dir ) . 'import-log.txt';
		$log_entry = '[' . current_time( 'mysql' ) . ']: ' . $message . PHP_EOL;

		file_put_contents( $log_file, $log_entry, FILE_APPEND );
	}
}
