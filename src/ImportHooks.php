<?php

namespace ThemeGrill\Demo\Importer;

use ThemeGrill\Demo\Importer\Traits\Singleton;
use WP_Query;

class ImportHooks {
	use Singleton;

	protected function init() {
		add_action( 'admin_init', array( $this, 'tg_update_demo_importer_options' ) );

		add_action( 'themegrill_ajax_before_demo_import', array( $this, 'reset_widgets' ), 10 );
		add_action( 'themegrill_ajax_before_demo_import', array( $this, 'delete_nav_menus' ), 20 );
		add_action( 'themegrill_ajax_before_demo_import', array( $this, 'remove_theme_mods' ), 30 );

		add_action( 'themegrill_ajax_demo_imported', array( $this, 'update_customizer_data' ), 9 );
		add_action( 'themegrill_ajax_demo_imported', array( $this, 'update_nav_menu_items' ) );
		add_action( 'themegrill_ajax_demo_imported', array( $this, 'set_elementor_load_fa4_shim' ) );
		add_action( 'themegrill_ajax_demo_imported', array( $this, 'set_elementor_active_kit' ) );
		add_action( 'themegrill_ajax_demo_imported', array( $this, 'set_wc_pages' ) );
		add_action( 'themegrill_ajax_demo_imported', array( $this, 'set_masteriyo_pages' ) );
		add_action( 'themegrill_ajax_demo_imported', array( $this, 'set_siteorigin_settings' ) );
		add_action( 'themegrill_ajax_demo_imported', array( $this, 'setup_yith_woocommerce_wishlist' ), 10, 2 );
		add_action( 'themegrill_ajax_demo_imported', array( $this, 'regenerate_elementor_styles' ), 10 );
		add_action( 'themegrill_ajax_demo_imported', array( $this, 'update_masteriyo_data' ), 10, 3 );
		add_action( 'themegrill_ajax_demo_imported', array( $this, 'update_magazine_blocks_settings' ), 10, 3 );
		add_action( 'themegrill_ajax_demo_imported', array( $this, 'update_blockart_blocks_settings' ), 10, 3 );

		add_filter( 'themegrill_widget_import_settings', array( $this, 'update_widget_data' ), 10, 2 );
		// Disable Masteriyo setup wizard.
		add_filter( 'masteriyo_enable_setup_wizard', '__return_false' );

		// Disable BlockArt redirection.
		add_filter( 'blockart_activation_redirect', '__return_false' );
		add_action(
			'init',
			function () {
				if (
				! in_array( 'elementor/elementor.php', get_option( 'active_plugins', array() ), true ) ||
				! get_option( 'themegrill_demo_importer_activated_id' )
				) {
					return;
				}
				if ( defined( 'ELEMENTOR_VERSION' ) && version_compare( ELEMENTOR_VERSION, '3.0.0', '>=' ) ) {
					$query = new WP_Query(
						array(
							'post_type' => 'elementor_library',
						)
					);

					$ids = array_map(
						function ( $post ) {
							return $post->ID;
						},
						$query->posts
					);

					$found = null;

					foreach ( $ids as $id ) {
						if ( is_array( get_post_meta( $id, '_elementor_page_settings', true ) ) ) {
							$found = $id;
							continue;
						}
					}

					if ( $found ) {
						update_option( 'elementor_active_kit', $found );
					}
				}
			},
			PHP_INT_MAX
		);
	}

	public function update_customizer_data() {
		$theme_mods = get_option( 'themegrill_starter_template_theme_mods' );
		foreach ( $theme_mods as $key => $value ) {
			set_theme_mod( $key, $value );
		}
		delete_option( 'themegrill_starter_template_theme_mods' );
	}
	/**
	 * Update demo importer options.
	 *
	 * @since 1.3.4
	 */
	public function tg_update_demo_importer_options() {
		$migrate_options = array(
			'themegrill_demo_imported_id' => 'themegrill_demo_importer_activated_id',
		);

		foreach ( $migrate_options as $old_option => $new_option ) {
			$value = get_option( $old_option );

			if ( $value ) {
				update_option( $new_option, $value );
				delete_option( $old_option );
			}
		}
	}

	/**
	 * Reset existing active widgets.
	 */
	public function reset_widgets() {
		$sidebars_widgets = wp_get_sidebars_widgets();

		// Reset active widgets.
		foreach ( $sidebars_widgets as $key => $widgets ) {
			$sidebars_widgets[ $key ] = array();
		}

		wp_set_sidebars_widgets( $sidebars_widgets );
	}

	/**
	 * Delete existing navigation menus.
	 */
	public function delete_nav_menus() {
		$nav_menus = wp_get_nav_menus();

		// Delete navigation menus.
		if ( ! empty( $nav_menus ) ) {
			foreach ( $nav_menus as $nav_menu ) {
				wp_delete_nav_menu( $nav_menu->slug );
			}
		}
	}

	/**
	 * Remove theme modifications option.
	 */
	public function remove_theme_mods() {
		remove_theme_mods();
	}

	/**
	 * Set Elementor Load FontAwesome 4 support.
	 */
	public function set_elementor_load_fa4_shim() {
		$elementor_load_fa4_shim = get_option( 'elementor_load_fa4_shim' );

		if ( ! $elementor_load_fa4_shim ) {
			update_option( 'elementor_load_fa4_shim', 'yes' );
		}
	}

	/**
	 * Set Elementor kit properly.
	 */
	public function set_elementor_active_kit() {
		$elementor_version = defined( 'ELEMENTOR_VERSION' ) ? ELEMENTOR_VERSION : false;

		if ( version_compare( $elementor_version, '3.0.0', '>=' ) ) {
			$query = new WP_Query(
				array(
					'post_type' => 'elementor_library',
				)
			);

			$ids = array_map(
				function ( $post ) {
					return $post->ID;
				},
				$query->posts
			);

			$found = null;

			foreach ( $ids as $id ) {
				if ( is_array( get_post_meta( $id, '_elementor_page_settings', true ) ) ) {
					$found = $id;
					break;
				}
			}

			if ( $found ) {
				update_option( 'elementor_active_kit', $found );
				\Elementor\Plugin::$instance->files_manager->clear_cache();
			}
		}
	}

	/**
	 * Set WC pages properly and disable setup wizard redirect.
	 *
	 * After importing demo data filter out duplicate WC pages and set them properly.
	 * Happens when the user run default woocommerce setup wizard during installation.
	 *
	 * Note: WC pages ID are stored in an option and slug are modified to remove any numbers.
	 *
	 * @param string $demo_id
	 */
	public function set_wc_pages( $demo_id ) {
		if ( class_exists( 'WooCommerce' ) ) {

			global $wpdb;
			$wc_pages = apply_filters(
				'themegrill_wc_' . $demo_id . '_pages',
				array(
					'shop'      => array(
						'name'  => 'shop',
						'title' => 'Shop',
					),
					'cart'      => array(
						'name'  => 'cart',
						'title' => 'Cart',
					),
					'checkout'  => array(
						'name'  => 'checkout',
						'title' => 'Checkout',
					),
					'myaccount' => array(
						'name'  => 'my-account',
						'title' => 'My Account',
					),
				)
			);

			// Set WC pages properly.
			foreach ( $wc_pages as $key => $wc_page ) {

				// Get the ID of every page with matching name or title.
				$page_ids = $wpdb->get_results( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE (post_name = %s OR post_title = %s) AND post_type = 'page' AND post_status = 'publish'", $wc_page['name'], $wc_page['title'] ) );
				if ( ! is_null( $page_ids ) ) {

					$page_id    = 0;
					$delete_ids = array();

					// Retrieve page with greater id and delete others.
					if ( sizeof( $page_ids ) > 1 ) {

						foreach ( $page_ids as $page ) {
							if ( $page->ID > $page_id ) {
								if ( $page_id ) {
									$delete_ids[] = $page_id;
								}

								$page_id = $page->ID;
							} else {
								$delete_ids[] = $page->ID;
							}
						}
					} else {
						$page_id = $page_ids[0]->ID;
					}

					// Delete posts.
					foreach ( $delete_ids as $delete_id ) {
						wp_delete_post( $delete_id, true );
					}

					// Update WC page.
					if ( $page_id > 0 ) {
						wp_update_post(
							array(
								'ID'        => $page_id,
								'post_name' => sanitize_title( $wc_page['name'] ),
							)
						);
						update_option( 'woocommerce_' . $key . '_page_id', $page_id );
					}
				}
			}

			// We no longer need WC setup wizard redirect.
			delete_transient( '_wc_activation_redirect' );
		}
	}

	/**
	 * Set Masteriyo pages properly and disable setup wizard redirect.
	 *
	 * After importing demo data filter out duplicate Masteriyo pages and set them properly.
	 * Happens when the user run default Masteriyo setup wizard during installation.
	 *
	 * Note: Masteriyo pages ID are stored in an option and slug are modified to remove any numbers.
	 *
	 * @param string $demo_id
	 */
	public function set_masteriyo_pages( $demo_id ) {

		if ( function_exists( 'masteriyo' ) ) {

			global $wpdb;
			$masteriyo_pages = apply_filters(
				'themegrill_masteriyo_' . $demo_id . '_pages',
				array(
					'courses'                 => array(
						'name'         => 'courses',
						'title'        => 'Courses',
						'setting_name' => 'courses_page_id',
					),
					'account'                 => array(
						'name'         => 'account',
						'title'        => 'Account',
						'setting_name' => 'account_page_id',
					),
					'checkout'                => array(
						'name'         => 'checkout',
						'title'        => 'Checkout',
						'setting_name' => 'checkout_page_id',
					),
					'learn'                   => array(
						'name'         => 'learn',
						'title'        => 'Learn',
						'setting_name' => 'learn_page_id',
					),
					'instructor-registration' => array(
						'name'         => 'instructor-registration',
						'title'        => 'Instructor Registration',
						'setting_name' => 'instructor_registration_page_id',
					),
					'instructors-list'        => array(
						'name'         => 'instructors-list',
						'title'        => 'Instructors list',
						'setting_name' => 'instructors_list_page_id',
					),
				)
			);

			// Set Masteriyo pages properly.
			foreach ( $masteriyo_pages as $key => $masteriyo_page ) {

				// Get the ID of every page with matching name or title.
				$page_ids = $wpdb->get_results( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE (post_name = %s OR post_title = %s) AND post_type = 'page' AND post_status = 'publish'", $masteriyo_page['name'], $masteriyo_page['title'] ) );

				if ( ! is_null( $page_ids ) ) {

					$page_id    = 0;
					$delete_ids = array();

					// Retrieve page with greater id and delete others.
					if ( count( $page_ids ) > 1 ) {

						foreach ( $page_ids as $page ) {
							if ( $page->ID > $page_id ) {
								if ( $page_id ) {
									$delete_ids[] = $page_id;
								}

								$page_id = $page->ID;
							} else {
								$delete_ids[] = $page->ID;
							}
						}
					} else {
						$page_id = ! empty( $page_ids ) ? $page_ids[0]->ID : 0;
					}

					// Delete posts.
					foreach ( $delete_ids as $delete_id ) {
						wp_delete_post( $delete_id, true );
					}

					// Update Masteriyo page.
					if ( $page_id > 0 ) {
						wp_update_post(
							array(
								'ID'        => $page_id,
								'post_name' => sanitize_title( $masteriyo_page['name'] ),
							)
						);

						$setting_name = $masteriyo_page['setting_name'];
						$version      = masteriyo_get_version();
						$tab          = version_compare( '1.5.4', $version, '<=' ) ? 'general' : 'advance';
						function_exists( 'masteriyo_set_setting' ) && masteriyo_set_setting( "$tab.pages.{$setting_name}", $page_id );
					}
				}
			}

			delete_transient( '_masteriyo_activation_redirect' );
		}
	}

	/**
	 * Set SiteOrigin PageBuilder Default Setting.
	 */
	public function set_siteorigin_settings() {
		$siteorigin_version = defined( 'SITEORIGIN_PANELS_VERSION' ) ? SITEORIGIN_PANELS_VERSION : false;

		if ( version_compare( $siteorigin_version, '2.12.0', '>=' ) ) {

			$settings = get_option( 'siteorigin_panels_settings' );

			$settings['parallax-type'] = 'legacy';

			update_option( 'siteorigin_panels_settings', $settings );
		}
	}

	/**
	 * Update YITH Wishlist settings.
	 *
	 * @param string $id Demo Id.
	 * @param array  $data Demo data.
	 * @return void
	 */
	public function setup_yith_woocommerce_wishlist( $demo_id, $demo_data ) {

		if ( ! function_exists( 'YITH_WCWL_Install' ) || YITH_WCWL_Install()->is_installed() ) {
			return;
		}

		YITH_WCWL_Install()->init();

		foreach ( $demo_data['yith_woocommerce_wishlist_settings'] as $key => $value ) {
			update_option( $key, $value );
		}
	}

	/**
	 * Regenerate elementor styles settings.
	 *
	 * @return void
	 */
	public function regenerate_elementor_styles() {
		if ( class_exists( 'Elementor\Plugin' ) ) {
			\Elementor\Plugin::instance()->files_manager->clear_cache();
		}
	}


	/**
	 * Update Masteriyo data.
	 *
	 * @param string $id Demo Id.
	 * @param array  $data Demo data.
	 * @return void
	 */
	public function update_masteriyo_data( $id, $data ) {

		if ( empty( $data['masteriyo_data'] ) ) {
			return;
		}
		if ( function_exists( 'masteriyo_set_setting' ) && ! empty( $data['masteriyo_data']['masteriyo_settings'] ) ) {
			foreach ( $data['masteriyo_data']['masteriyo_settings'] as $key => $value ) {
				masteriyo_set_setting( $key, $value );
			}
		}
		if ( ! empty( $data['masteriyo_data']['masteriyo_active_addons'] ) ) {
			update_option( 'masteriyo_active_addons', $data['masteriyo_data']['masteriyo_active_addons'] );
		}
	}

	/**
	 * Update Magazine Blocks settings.
	 *
	 * @param string $id Demo Id.
	 * @param array  $data Demo data.
	 * @return void
	 */
	public function update_magazine_blocks_settings( $id, $data ) {
		$settings = $data['magazine_blocks_settings'] ?? array();

		if ( empty( $settings ) ) {
			return;
		}

		if ( is_string( $settings ) ) {
			$decoded = json_decode( $settings, true );
			if ( json_last_error() === JSON_ERROR_NONE ) {
				$settings = $decoded;
			}
		}

		update_option( '_magazine_blocks_settings', $settings );
	}


	/**
	 * Update Blockart Blocks settings.
	 *
	 * @param string $id Demo Id.
	 * @param array  $data Demo data.
	 * @return void
	 */
	public function update_blockart_blocks_settings( $id, $data ) {
		$settings = $data['blockart_blocks_settings'] ?? array();

		if ( empty( $settings ) ) {
			return;
		}

		if ( is_string( $settings ) ) {
			$decoded = json_decode( $settings, true );
			if ( json_last_error() === JSON_ERROR_NONE ) {
				$settings = $decoded;
			}
		}

		update_option( '_blockart_settings', $settings );
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

	public function update_widget_data( $widget, $widget_type ) {
		if ( ! empty( $widget ) ) {
			$term_mapped_data = array();
			$mapping_data     = get_option( 'themegrill_demo_importer_mapping', array() );
			$term_mapped_data = $mapping_data['term_id'] ?? array();
			$post_mapped_data = $mapping_data['post'] ?? array();

			if ( ! empty( $term_mapped_data ) ) {
				if ( 'nav_menu' === $widget_type ) {
					$menu     = $term_mapped_data[ $widget['nav_menu'] ] ?? '';
					$nav_menu = wp_get_nav_menu_object( $menu );
					if ( is_object( $nav_menu ) && $nav_menu->term_id ) {
						$widget['nav_menu'] = $nav_menu->term_id;
					}
				} elseif ( is_array( $widget ) ) {
					$keys = array( 'category', 'tag', 'author', 'page_id', 'page_id0', 'page_id1', 'page_id2', 'page_id3', 'page_id4', 'page_id5', 'cat_id0', 'cat_id1', 'cat_id2', 'category1', 'category2', 'category3', 'category4' ); // for dropdown categories and pages in widgets
					foreach ( $keys as $key ) {
						if ( isset( $widget[ $key ] ) ) {
							if ( 'author' === $key ) {
								$widget[ $key ] = get_current_user_id();
							} elseif ( str_starts_with( $key, 'page_id' ) ) {
								$widget[ $key ] = $post_mapped_data[ $widget[ $key ] ] ?? $widget[ $key ];
							} else {
								$widget[ $key ] = $term_mapped_data[ $widget[ $key ] ] ?? $widget[ $key ];
							}
						}
					}
				}
			}
		}
		return $widget;
	}
}
