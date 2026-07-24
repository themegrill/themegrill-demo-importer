<?php

namespace ThemeGrill\Demo\Importer;

use ThemeGrill\Demo\Importer\Traits\Singleton;
use WP_Query;
use WP_REST_Request;

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
		add_action( 'themegrill_ajax_demo_imported', array( $this, 'update_masteriyo_data' ), 10, 2 );
		add_action( 'themegrill_ajax_demo_imported', array( $this, 'update_magazine_blocks_settings' ), 10, 2 );
		add_action( 'themegrill_ajax_demo_imported', array( $this, 'update_blockart_blocks_settings' ), 10, 2 );
		add_action( 'themegrill_ajax_demo_imported', array( $this, 'update_elementor_settings' ), 10, 2 );
		add_action( 'themegrill_ajax_demo_imported', array( $this, 'process_evf_posts' ) );
		add_action( 'themegrill_ajax_demo_imported', array( $this, 'setup_allfeedback_survey' ), 10, 2 );

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

		add_filter(
			'themegrill_import_post_data_processed',
			function ( $post_data, $term_id_map = null ) {
				if ( isset( $post_data['post_content'] ) && has_blocks( $post_data['post_content'] ) && $term_id_map ) {
					$blocks = parse_blocks( $post_data['post_content'] );
					$this->themegrill_update_block_term_ids( $blocks, $term_id_map );
					$post_data['post_content'] = serialize_blocks( $blocks );
				}
				return $post_data;
			},
			10,
			2
		);
		add_action(
			'themegrill_widget_importer_after_widgets_import',
			function ( $term_id_map ) {
				remove_all_actions( 'themegrill_widget_importer_after_widgets_import' );
				$widget_blocks = get_option( 'widget_block', array() );
				if ( ! empty( $widget_blocks ) ) {
					foreach ( $widget_blocks as $index => $widget ) {
						if ( isset( $widget['content'] ) ) {
							$blocks = parse_blocks( $widget['content'] );
							$this->themegrill_update_block_term_ids( $blocks, $term_id_map );
							$widget_blocks[ $index ]['content'] = serialize_blocks( $blocks );
						}
					}
					update_option( 'widget_block', $widget_blocks );
				}
			},
			9
		);
	}

	public function update_customizer_data() {
		$theme_mods = get_option( 'themegrill_starter_template_theme_mods', array() );
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

	/**
	 * Suppress AllFeedback's post-activation Setup Wizard redirect, and create a
	 * curated popup survey for demos that request one.
	 *
	 * AllFeedback stores surveys in its own database table (`{$wpdb->prefix}af_surveys`),
	 * not a post type, so a survey can't ride along with the WXR content import the way
	 * Everest Forms forms do (see `process_evf_posts()`/`update_evf_form_ids()` above) —
	 * there is no "old ID" in the import stream to remap. Instead, this creates the survey
	 * directly through AllFeedback's own REST controllers via `rest_do_request()`, which
	 * keeps this integration decoupled from AllFeedback's internal domain/service classes
	 * and reuses its own request validation (allowed field-type/trigger/position enums, etc.)
	 * rather than duplicating it here.
	 *
	 * Expects `$data['allfeedback_survey']` with at least a `title`. `form_schema`,
	 * `settings`, and `styling` are optional and merged over sensible defaults, so the
	 * demo payload only needs to specify what's actually curated per demo (e.g. the
	 * survey copy) — see `default_allfeedback_form_schema()`, `default_allfeedback_settings()`,
	 * and `default_allfeedback_styling()` below for what "sensible defaults" means.
	 *
	 * @param string $id   Demo Id.
	 * @param array  $data Demo data.
	 * @return void
	 */
	public function setup_allfeedback_survey( $id, $data ) {
		if ( ! function_exists( 'is_plugin_active' ) ) {
			include_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		if ( ! is_plugin_active( 'allfeedback/allfeedback.php' ) ) {
			return;
		}

		// AllFeedback redirects to its Setup Wizard on the next admin page load after activation, so mark it completed to suppress that redirect.
		update_option( 'allfeedback_wizard_status', 'completed' );

		$survey_data = $data['allfeedback_survey'] ?? $this->default_allfeedback_survey_for_demo( $id );

		if ( empty( $survey_data['title'] ) ) {
			return;
		}

		$create_request = $this->build_json_rest_request(
			'POST',
			'/allfeedback/v1/surveys',
			array(
				'title'       => sanitize_text_field( $survey_data['title'] ),
				'description' => wp_kses_post( $survey_data['description'] ?? '' ),
				'form_schema' => $survey_data['form_schema'] ?? $this->default_allfeedback_form_schema(),
			)
		);

		$create_response = rest_do_request( $create_request );
		$created         = $create_response->get_data();

		if ( $create_response->get_status() >= 300 || empty( $created['data']['id'] ) ) {
			return;
		}

		$update_request = $this->build_json_rest_request(
			'PUT',
			'/allfeedback/v1/surveys/' . (int) $created['data']['id'],
			array(
				'settings' => array_merge( $this->default_allfeedback_settings(), $survey_data['settings'] ?? array() ),
				'styling'  => array_merge( $this->default_allfeedback_styling(), $survey_data['styling'] ?? array() ),
				'status'   => 'published',
			)
		);

		rest_do_request( $update_request );
	}

	/**
	 * Build a `WP_REST_Request` with a genuine JSON body.
	 *
	 * AllFeedback's `SurveysController::update()` (the `PUT /surveys/{id}`
	 * handler) only checks whether a field was submitted via
	 * `$request->get_json_params()` — not the more forgiving `get_param()`
	 * that merges every parameter source — so `set_body_params()` alone isn't
	 * enough: it fills the `POST` param store, not the `JSON` one, and every
	 * `array_key_exists( ..., $body )` check in `update()` then silently
	 * fails, leaving the survey unchanged (still draft, no styling applied).
	 * Setting an actual JSON body + content-type makes `get_json_params()`
	 * parse it as intended.
	 *
	 * @param string $method HTTP method.
	 * @param string $route  REST route.
	 * @param array  $params Request body params.
	 * @return WP_REST_Request
	 */
	protected function build_json_rest_request( $method, $route, $params ) {
		$request = new WP_REST_Request( $method, $route );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body( wp_json_encode( $params ) );

		return $request;
	}

	/**
	 * Per-demo curated AllFeedback survey content (ZAK-238).
	 *
	 * Fallback used only when the remote demo payload doesn't itself supply an
	 * `allfeedback_survey` key — once the demos server is updated to curate a
	 * given demo directly, its payload takes precedence over the entry here
	 * (see the `?? ` fallback in `setup_allfeedback_survey()` above). Keyed by
	 * demo slug; any key not listed here simply gets no survey until it's
	 * curated on one side or the other.
	 *
	 * Each entry only overrides `title`/`form_schema`/`styling`; `settings`
	 * (trigger/frequency/targeting) keeps the sitewide defaults below unless
	 * noted otherwise. Content and niche below is based on what's actually live
	 * on each demo's reference site (zakrademos.com/<slug>), not guessed:
	 *
	 * - `agency`   — general creative/digital agency (portfolio, pricing plans,
	 *                team, client reviews). Client-services, referral-driven.
	 * - `suffice`  — marketing/growth agency ("Book Now" CTA, services: Marketing,
	 *                Branding, Web Design, Strategy). Same referral logic as agency.
	 * - `lawyer`   — law firm (practice areas, case stats, consultations). Also
	 *                referral-driven professional services; legal-flavoured wording.
	 * - `charity`  — nonprofit (donations, causes, volunteers). Not a commercial
	 *                "would you recommend" fit — visitors are donors/volunteers, not
	 *                clients, so this uses a visitor-intent `radio` question instead
	 *                of NPS (mirrors AllFeedback's own "customer-research" template).
	 * - `foodhunt` — restaurant (menu, reservations, dishes). Dining-experience
	 *                `star_rating` — the one universal restaurant-feedback pattern.
	 *
	 * agency/suffice/lawyer all map to NPS (AllFeedback's own flagship survey
	 * type) because all three are referral-driven client-services businesses;
	 * the wording differs per niche rather than reusing identical copy.
	 *
	 * @param string $id Demo Id/slug.
	 * @return array
	 */
	protected function default_allfeedback_survey_for_demo( $id ) {
		$curated = array(
			'agency' => array(
				'title'       => __( 'How Are We Doing?', 'themegrill-demo-importer' ),
				'form_schema' => array(
					'version'  => '1.0',
					'sections' => array(
						array(
							'id'     => 's1',
							'title'  => __( 'Feedback', 'themegrill-demo-importer' ),
							'fields' => array(
								array(
									'id'       => 'f1',
									'type'     => 'nps',
									'label'    => __( 'How likely are you to recommend our agency to a friend or colleague?', 'themegrill-demo-importer' ),
									'required' => true,
									'settings' => array(),
								),
								array(
									'id'       => 'f2',
									'type'     => 'long_text',
									'label'    => __( 'What\'s the main reason for your score?', 'themegrill-demo-importer' ),
									'required' => false,
									'settings' => array(
										'placeholder' => __( 'Tell us what stood out — good or bad…', 'themegrill-demo-importer' ),
									),
								),
							),
						),
					),
				),
				// Matches this demo's own accent colour (zakra_breadcrumbs_link_hover_color).
				'styling'     => array(
					'widget_color' => '#23ab70',
				),
			),
			'suffice' => array(
				'title'       => __( 'How Are We Doing?', 'themegrill-demo-importer' ),
				'form_schema' => array(
					'version'  => '1.0',
					'sections' => array(
						array(
							'id'     => 's1',
							'title'  => __( 'Feedback', 'themegrill-demo-importer' ),
							'fields' => array(
								array(
									'id'       => 'f1',
									'type'     => 'nps',
									'label'    => __( 'How likely are you to recommend our marketing services to a friend or colleague?', 'themegrill-demo-importer' ),
									'required' => true,
									'settings' => array(),
								),
								array(
									'id'       => 'f2',
									'type'     => 'long_text',
									'label'    => __( 'What\'s the main reason for your score?', 'themegrill-demo-importer' ),
									'required' => false,
									'settings' => array(
										'placeholder' => __( 'Tell us what stood out — good or bad…', 'themegrill-demo-importer' ),
									),
								),
							),
						),
					),
				),
				// Matches this demo's own primary colour (zakra_primary_color).
				'styling'     => array(
					'widget_color' => '#3867D6',
				),
			),
			'lawyer' => array(
				'title'       => __( 'How Are We Doing?', 'themegrill-demo-importer' ),
				'form_schema' => array(
					'version'  => '1.0',
					'sections' => array(
						array(
							'id'     => 's1',
							'title'  => __( 'Feedback', 'themegrill-demo-importer' ),
							'fields' => array(
								array(
									'id'       => 'f1',
									'type'     => 'nps',
									'label'    => __( 'How likely are you to recommend our law firm to a friend or colleague?', 'themegrill-demo-importer' ),
									'required' => true,
									'settings' => array(),
								),
								array(
									'id'       => 'f2',
									'type'     => 'long_text',
									'label'    => __( 'What\'s the main reason for your score?', 'themegrill-demo-importer' ),
									'required' => false,
									'settings' => array(
										'placeholder' => __( 'Tell us what stood out — good or bad…', 'themegrill-demo-importer' ),
									),
								),
							),
						),
					),
				),
				// Matches this demo's own primary colour (zakra_primary_color).
				'styling'     => array(
					'widget_color' => '#b89b5e',
				),
			),
			'charity' => array(
				'title'       => __( 'What Brings You Here?', 'themegrill-demo-importer' ),
				'form_schema' => array(
					'version'  => '1.0',
					'sections' => array(
						array(
							'id'     => 's1',
							'title'  => __( 'Feedback', 'themegrill-demo-importer' ),
							'fields' => array(
								array(
									'id'       => 'f1',
									'type'     => 'radio',
									'label'    => __( 'What brings you here today?', 'themegrill-demo-importer' ),
									'required' => true,
									'settings' => array(
										'options'     => array(
											__( 'I want to donate', 'themegrill-demo-importer' ),
											__( 'I want to volunteer', 'themegrill-demo-importer' ),
											__( 'Learning about your cause', 'themegrill-demo-importer' ),
											__( 'Other', 'themegrill-demo-importer' ),
										),
										'placeholder' => '',
									),
								),
								array(
									'id'       => 'f2',
									'type'     => 'long_text',
									'label'    => __( 'Anything else you\'d like to share with us?', 'themegrill-demo-importer' ),
									'required' => false,
									'settings' => array(
										'placeholder' => __( 'Your message…', 'themegrill-demo-importer' ),
									),
								),
							),
						),
					),
				),
				// Matches this demo's own primary colour (zakra_primary_color).
				'styling'     => array(
					'widget_color' => '#f96703',
				),
			),
			'foodhunt' => array(
				'title'       => __( 'How Was Your Visit?', 'themegrill-demo-importer' ),
				'form_schema' => array(
					'version'  => '1.0',
					'sections' => array(
						array(
							'id'     => 's1',
							'title'  => __( 'Feedback', 'themegrill-demo-importer' ),
							'fields' => array(
								array(
									'id'       => 'f1',
									'type'     => 'star_rating',
									'label'    => __( 'How would you rate your dining experience with us?', 'themegrill-demo-importer' ),
									'required' => true,
									'settings' => array(
										'starRange' => 5,
										'starScale' => 'star',
									),
								),
								array(
									'id'       => 'f2',
									'type'     => 'long_text',
									'label'    => __( 'Tell us what you loved — or what we could do better.', 'themegrill-demo-importer' ),
									'required' => false,
									'settings' => array(
										'placeholder' => __( 'Your thoughts…', 'themegrill-demo-importer' ),
									),
								),
							),
						),
					),
				),
				// Matches this demo's own accent colour (zakra_header_button_background_color).
				'styling'     => array(
					'widget_color' => '#AE7729',
				),
			),
		);

		return $curated[ $id ] ?? array();
	}

	/**
	 * Default AllFeedback form schema used when a demo doesn't supply its own.
	 *
	 * A single NPS question — the minimal valid schema AllFeedback's
	 * `validateFormSchema()` accepts (a `sections[].fields[]` list of
	 * `{id, type, label, required, settings}`, `type` one of AllFeedback's
	 * supported field types).
	 *
	 * @return array
	 */
	protected function default_allfeedback_form_schema() {
		return array(
			'version'  => '1.0',
			'sections' => array(
				array(
					'id'     => 's1',
					'title'  => __( 'Feedback', 'themegrill-demo-importer' ),
					'fields' => array(
						array(
							'id'       => 'f1',
							'type'     => 'nps',
							'label'    => __( 'How likely are you to recommend us to a friend or colleague?', 'themegrill-demo-importer' ),
							'required' => true,
							'settings' => array(),
						),
					),
				),
			),
		);
	}

	/**
	 * Default AllFeedback display/behaviour settings used when a demo doesn't
	 * fully specify its own — merged under whatever the demo payload provides.
	 *
	 * Shows once per visitor, after a short delay, sitewide, to avoid nagging
	 * demo-preview visitors on every page.
	 *
	 * @return array
	 */
	protected function default_allfeedback_settings() {
		return array(
			'trigger_type'      => 'time_delay',
			'delay_value'       => 8,
			'delay_unit'        => 'seconds',
			'display_frequency' => 'once',
			'user_state'        => 'all',
			'target_pages'      => 'all',
		);
	}

	/**
	 * Default AllFeedback widget styling used when a demo doesn't fully specify
	 * its own — merged under whatever the demo payload provides.
	 *
	 * `bottom-left` is deliberate: the live-chat/support widget most Zakra demos
	 * ship with already occupies bottom-right (see ZAK-238).
	 *
	 * @return array
	 */
	protected function default_allfeedback_styling() {
		return array(
			'widget_position' => 'bottom-left',
			'widget_label'    => __( 'Feedback', 'themegrill-demo-importer' ),
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
							if ( isset( $menu_parts['path'] ) && isset( $menu_parts['host'] ) && apply_filters( 'themegrill_demo_importer_nav_menu_item_url_hosts', in_array( $menu_parts['host'], array( 'demo.themegrill.com', 'zakrademos.com', 'themegrilldemos.com' ) ) ) ) {
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
					$menu     = isset( $widget['nav_menu'] ) ? ( $term_mapped_data[ $widget['nav_menu'] ] ?? '' ) : '';
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

	public function process_evf_posts() {
		$posts_with_evf = get_option( 'themegrill_demo_importer_posts_with_evf', array() );

		if ( empty( $posts_with_evf ) ) {
			return;
		}

		foreach ( $posts_with_evf as $post_id ) {
			$post = get_post( $post_id );

			if ( ! $post || ! has_blocks( $post->post_content ) || ! has_block( 'everest-forms/form-selector', $post->post_content ) ) {
				continue;
			}

			$blocks = parse_blocks( $post->post_content );

			if ( empty( $blocks ) ) {
				continue;
			}

			$mapping_data     = get_option( 'themegrill_demo_importer_mapping', array() );
			$post_mapped_data = $mapping_data['post'] ?? array();

			$this->update_evf_form_ids( $blocks, $post_mapped_data );

			// Convert blocks back to post content.
			$post_content = serialize_blocks( $blocks );

			// Update the post content.
			wp_update_post(
				wp_slash(
					array(
						'ID'           => $post_id,
						'post_content' => $post_content,
					)
				)
			);
		}

		delete_option( 'themegrill_demo_importer_posts_with_evf' );
	}

	public function update_evf_form_ids( array &$blocks, array $post_id_map ) {
		foreach ( $blocks as &$block ) {
			if ( isset( $block['blockName'] ) ) {
				if ( 'everest-forms/form-selector' === $block['blockName'] ) {
					if ( isset( $block['attrs']['formId'] ) ) {
						$current_form_id = $block['attrs']['formId'];
						if ( isset( $post_id_map[ $current_form_id ] ) ) {
							$block['attrs']['formId'] = (string) $post_id_map[ $current_form_id ];
						}
					}
				}
				if ( ! empty( $block['innerBlocks'] ) ) {
					$this->update_evf_form_ids( $block['innerBlocks'], $post_id_map );
				}
			}
		}
	}

	public function themegrill_update_block_term_ids( array &$blocks, array $term_id_map ) {
		foreach ( $blocks as &$block ) {
			if ( isset( $block['blockName'] ) ) {
				if ( str_starts_with( $block['blockName'], 'magazine-blocks/' ) ) {
					if ( isset( $block['attrs'] ) ) {
						$key1 = array( 'category', 'category2', 'tag', 'tag2', 'authorName' );

						foreach ( $key1 as $key ) {
							if ( 'authorName' === $key && isset( $block['attrs'][ $key ] ) ) {
								$block['attrs'][ $key ] = (string) get_current_user_id();
								break;
							}
							if ( isset( $block['attrs'][ $key ] ) && isset( $term_id_map[ $block['attrs'][ $key ] ] ) ) {
								$block['attrs'][ $key ] = (string) $term_id_map[ $block['attrs'][ $key ] ];
							}
						}

						$key2 = array( 'excludedCategory', 'excludedCategory2' );

						foreach ( $key2 as $key ) {
							if ( isset( $block['attrs'][ $key ] ) && is_array( $block['attrs'][ $key ] ) ) {
								$block['attrs'][ $key ] = array_map(
									function ( $cat_id ) use ( $term_id_map ) {
										return isset( $term_id_map[ $cat_id ] ) ? (string) $term_id_map[ $cat_id ] : false;
									},
									$block['attrs'][ $key ]
								);
							}
						}
					}

					// Recursively update inner blocks
					if ( ! empty( $block['innerBlocks'] ) ) {
						$this->themegrill_update_block_term_ids( $block['innerBlocks'], $term_id_map );
					}
				}
				if ( 'core/group' === $block['blockName'] ) {
					if ( ! empty( $block['innerBlocks'] ) ) {
						foreach ( $block['innerBlocks'] as &$inner_block ) {
							if ( 'core/legacy-widget' === $inner_block['blockName'] ) {
								if ( isset( $inner_block['attrs']['idBase'] ) && 'nav_menu' === $inner_block['attrs']['idBase'] ) {
									if ( isset( $inner_block['attrs']['instance']['raw']['nav_menu'] ) ) {
										$current_menu_id = $inner_block['attrs']['instance']['raw']['nav_menu'];
										if ( isset( $term_id_map[ $current_menu_id ] ) ) {
											$new_menu_id = $term_id_map[ $current_menu_id ];
											$inner_block['attrs']['instance']['raw']['nav_menu'] = $new_menu_id;

											// Preserve existing raw data and update nav_menu
											$new_data             = $inner_block['attrs']['instance']['raw'];
											$new_data['nav_menu'] = $new_menu_id;

											// Update encoded and hash with complete data
											$inner_block['attrs']['instance']['encoded'] = base64_encode( serialize( $new_data ) );
											$inner_block['attrs']['instance']['hash']    = wp_hash( serialize( $new_data ) );

										}
									}
								}
							}
						}
					}
				}
			}
		}
	}

	public function update_elementor_settings( $id, $data ) {
		$settings = $data['elementor_settings'] ?? array();

		if ( empty( $settings ) ) {
			return;
		}

		foreach ( $settings as $key => $value ) {
			update_option( $key, $value );
		}
	}
}
