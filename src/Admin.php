<?php

namespace ThemeGrill\Demo\Importer;

use ThemeGrill\Demo\Importer\Importers\WXRImporter;
use ThemeGrill\Demo\Importer\Traits\Singleton;

class Admin {
	use Singleton;

	/**
	 * Demo packages.
	 *
	 * @var array
	 */
	public $demo_packages;

	/**
	 * The importer class object
	 *
	 * @var TG_WXR_Importer
	 */
	// public static $importer;

	/**
	 * Initialize admin functionality
	 */
	protected function init() {
		add_action( 'init', array( $this, 'setup' ), 5 );

		// Add Demo Importer menu.
		add_action( 'admin_menu', array( $this, 'admin_menu' ), 9 );
		add_action( 'admin_head', array( $this, 'add_menu_classes' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		// Help Tabs.
		if ( apply_filters( 'themegrill_demo_importer_enable_admin_help_tab', true ) ) {
			add_action( 'current_screen', array( $this, 'add_help_tabs' ), 50 );
		}

		// Footer rating text.
		add_filter( 'admin_footer_text', array( $this, 'admin_footer_text' ), 1 );

		// Disable WooCommerce setup wizard.
		add_action( 'current_screen', array( $this, 'woocommerce_disable_setup_wizard' ) );
	}

	/**
	 * Demo importer setup.
	 */
	public function setup() {
		$this->demo_packages = static::get_demo_packages();
		// static::$importer    = new WXRImporter();
	}

	/**
	 * Add menu item.
	 */
	public function admin_menu() {

		// if ( apply_filters( 'themegrill_demo_importer_show_main_menu', true ) ) {
		//  add_theme_page( __( 'Demo Importer', 'themegrill-demo-importer' ), __( 'Demo Importer', 'themegrill-demo-importer' ), 'switch_themes', 'demo-importer', array( $this, 'demo_importer' ) );
		// }

		$page = add_theme_page(
			__( 'Demo Importer V2', 'themegrill-demo-importer' ),
			__( 'Demo Importer V2', 'themegrill-demo-importer' ),
			'switch_themes',
			'demo-importer-v2',
			function () {
				echo '<div id="tg-demo-importer"></div>';
			}
		);
		add_action( "admin_print_scripts-$page", array( $this, 'enqueue_demo_importer_assets' ) );
		add_theme_page( __( 'Demo Importer Status', 'themegrill-demo-importer' ), __( 'Demo Importer Status', 'themegrill-demo-importer' ), 'switch_themes', 'demo-importer-status', array( $this, 'status_menu' ) );
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
		$assets_path = App::plugin_url() . '/assets/';

		wp_enqueue_media();

		// Demo Importer appearance page.
		// if ( 'appearance_page_demo-importer' === $screen_id ) {
		//  wp_enqueue_style( 'tg-demo-importer' );
		//  wp_enqueue_script( 'tg-demo-importer' );

		//  // For translation of strings within scripts.
		//  wp_set_script_translations( 'tg-demo-updates', 'themegrill-demo-importer' );
		// }
	}

	/**
	 * Enqueue assets for the demo importer page
	 */
	public function enqueue_demo_importer_assets() {
		$asset_url = function ( $filename ) {
			if ( defined( 'TDI_DEVELOPMENT' ) && TDI_DEVELOPMENT ) {
				return 'http://localhost:8887/' . $filename;
			}
			return plugin_dir_url( TGDM_PLUGIN_FILE ) . 'dist/' . $filename;
		};
		$asset     = function ( $prefix ) {
			$asset_file = plugin_dir_path( TGDM_PLUGIN_FILE ) . DIRECTORY_SEPARATOR . 'dist' . DIRECTORY_SEPARATOR . $prefix . '.asset.php';
			if ( file_exists( $asset_file ) ) {
				return require $asset_file;
			}
			return array(
				'dependencies' => array(),
				'version'      => TGDM_VERSION,
			);
		};
		wp_enqueue_script( 'tdi-dashboard', $asset_url( 'dashboard.js' ), $asset( 'dashboard' )['dependencies'], $asset( 'dashboard' )['version'], true );
		$localized_data = static::get_localized_data();
		if ( array_key_exists( 'message', $this->demo_packages ) ) {
			$localized_data['data']      = array();
			$localized_data['error_msg'] = $this->demo_packages['message'];
		} else {
			$localized_data['data'] = $this->demo_packages;
		}

		wp_localize_script(
			'tdi-dashboard',
			'__TDI_DASHBOARD__',
			$localized_data
		);
		wp_enqueue_style( 'tdi-dashboard', $asset_url( 'dashboard.css' ), array(), $asset( 'dashboard' )['version'] );
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
					esc_html__( 'If you like %1$s, please leave us a %2$s rating. A huge thanks in advance!', 'themegrill-demo-importer' ),
					sprintf( '<strong>%s</strong>', esc_html__( get_template(), 'themegrill-demo-importer' ) ),
					'<a href="https://wordpress.org/support/theme/' . get_template() . '/reviews?rate=5#new-post" target="_blank" class="themegrill-demo-importer-rating-link" data-rated="' . esc_attr__( 'Thanks :)', 'themegrill-demo-importer' ) . '">&#9733;&#9733;&#9733;&#9733;&#9733;</a>'
				);
			} else {
				$footer_text = esc_html__( 'Thank you for importing with ThemeGrill Demo Importer.', 'themegrill-demo-importer' );
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

		$screen->add_help_tab(
			array(
				'id'      => 'themegrill_demo_importer_support_tab',
				'title'   => __( 'Help &amp; Support', 'themegrill-demo-importer' ),
				'content' =>
					'<h2>' . __( 'Help &amp; Support', 'themegrill-demo-importer' ) . '</h2>' .
					'<p>' . sprintf(
					/* translators: %s: Documentation URL */
						__( 'Should you need help understanding, using, or extending ThemeGrill Demo Importer, <a href="%s">please read our documentation</a>. You will find all kinds of resources including snippets, tutorials and much more.', 'themegrill-demo-importer' ),
						'https://themegrill.com/docs/themegrill-demo-importer/'
					) . '</p>' .
					'<p>' . sprintf(
					/* translators: 1: WP support URL. 2: TG support URL  */
						__( 'For further assistance with ThemeGrill Demo Importer core you can use the <a href="%1$s">community forum</a>. If you need help with premium themes sold by ThemeGrill, please <a href="%2$s">use our free support forum</a>.', 'themegrill-demo-importer' ),
						'https://wordpress.org/support/plugin/themegrill-demo-importer',
						'https://themegrill.com/support-forum/'
					) . '</p>' .
					'<p><a href="https://wordpress.org/support/plugin/themegrill-demo-importer" class="button button-primary">' . __( 'Community forum', 'themegrill-demo-importer' ) . '</a> <a href="https://themegrill.com/support-forum/" class="button">' . __( 'ThemeGrill Support', 'themegrill-demo-importer' ) . '</a></p>',
			)
		);

		$screen->add_help_tab(
			array(
				'id'      => 'themegrill_demo_importer_bugs_tab',
				'title'   => __( 'Found a bug?', 'themegrill-demo-importer' ),
				'content' =>
					'<h2>' . __( 'Found a bug?', 'themegrill-demo-importer' ) . '</h2>' .
					'<p>' . sprintf(
					/* translators: %s: GitHub links */
						__( 'If you find a bug within ThemeGrill Demo Importer you can create a ticket via <a href="%1$s">Github issues</a>. Ensure you read the <a href="%2$s">contribution guide</a> prior to submitting your report. To help us solve your issue, please be as descriptive as possible.', 'themegrill-demo-importer' ),
						'https://github.com/themegrill/themegrill-demo-importer/issues?state=open',
						'https://github.com/themegrill/themegrill-demo-importer/blob/master/.github/CONTRIBUTING.md'
					) . '</p>' .
					'<p><a href="https://github.com/themegrill/themegrill-demo-importer/issues?state=open" class="button button-primary">' . __( 'Report a bug', 'themegrill-demo-importer' ) . '</a></p>',

			)
		);

		$screen->set_help_sidebar(
			'<p><strong>' . __( 'For more information:', 'themegrill-demo-importer' ) . '</strong></p>' .
			'<p><a href="https://themegrill.com/demo-importer/" target="_blank">' . __( 'About Demo Importer', 'themegrill-demo-importer' ) . '</a></p>' .
			'<p><a href="https://wordpress.org/plugins/themegrill-demo-importer/" target="_blank">' . __( 'WordPress.org project', 'themegrill-demo-importer' ) . '</a></p>' .
			'<p><a href="https://github.com/themegrill/themegrill-demo-importer" target="_blank">' . __( 'Github project', 'themegrill-demo-importer' ) . '</a></p>' .
			'<p><a href="https://themegrill.com/wordpress-themes/" target="_blank">' . __( 'Official themes', 'themegrill-demo-importer' ) . '</a></p>' .
			'<p><a href="https://themegrill.com/plugins/" target="_blank">' . __( 'Official plugins', 'themegrill-demo-importer' ) . '</a></p>'
		);
	}

	/**
	 * Disable the WooCommerce Setup Wizard on `ThemeGrill Demo Importer` page only.
	 */
	public function woocommerce_disable_setup_wizard() {

		$screen = get_current_screen();

		if ( 'appearance_page_demo-importer' === $screen->id ) {
			add_filter( 'woocommerce_enable_setup_wizard', '__return_false', 1 );
		}
	}

	/**
	 * Demo Importer status page output.
	 */
	public function status_menu() {
		include_once __DIR__ . '/admin/views/html-admin-page-status.php';
	}

	/**
	 * Get core supported themes.
	 *
	 * @return array
	 */
	public static function get_core_supported_themes() {
		$core_themes = array(
			'spacious',
			'colormag',
			'flash',
			'estore',
			'ample',
			'accelerate',
			'colornews',
			'foodhunt',
			'fitclub',
			'radiate',
			'freedom',
			'himalayas',
			'esteem',
			'envince',
			'suffice',
			'explore',
			'masonic',
			'cenote',
			'zakra',
			'webshop',
			'elearning',
			'online-education',
			'skincare',
			'estory',
			'gizmo',
			'libreria',
			'magazinex',
			'vastra',
			'blissful',
			'kirana',
			'ornatedecor',
		);
		// Check for official core themes pro version.
		$pro_themes = array_diff( $core_themes, array( 'explore', 'masonic', 'estory' ) );
		if ( ! empty( $pro_themes ) ) {
			$pro_themes = preg_replace( '/$/', '-pro', $pro_themes );
		}
		return array_merge( $core_themes, $pro_themes );
	}

	/**
	 * check whether the current active theme is in core supported themes list
	 */
	public static function get_theme() {
		$supported_themes = self::get_core_supported_themes();
		$theme            = get_option( 'template' );
		if ( in_array( $theme, $supported_themes, true ) ) {
			$is_pro_theme = strpos( $theme, '-pro' ) !== false;
			if ( $is_pro_theme ) {
				$theme = $is_pro_theme ? str_replace( '-pro', '', $theme ) : $theme;
			}
			return $theme;
		}
		return 'all';
	}

	/**
	 * Get demo packages.
	 *
	 * @return array of objects
	 */
	public static function get_demo_packages( $force = true ) {
		$template   = static::get_theme();
		$demos      = [];
		$need_fetch = false;
		if ( $force ) {
			delete_transient( 'themegrill_demo_importer_demos' );
		}
		$demos = get_transient( 'themegrill_demo_importer_demos', array() );
		if ( empty( $demos ) ) {
			$need_fetch = true;
		}

		if ( $need_fetch ) {
			$zakra_demos      = array();
			$themegrill_demos = array();

			$zakra_url   = ZAKRA_BASE_URL . TGDM_NAMESPACE;
			$zakra_demos = static::fetch_demo_data( $zakra_url );
			if ( is_array( $zakra_demos ) && isset( $zakra_demos['message'] ) ) {
				return array(
					'success' => false,
					'message' => 'Failed to fetch Zakra demos: ' . ( $zakra_demos['message'] ?? 'Unknown error' ),
				);
			}

			$themegrill_url   = THEMEGRILL_BASE_URL . TGDM_NAMESPACE;
			$themegrill_demos = static::fetch_demo_data( $themegrill_url );
			if ( is_array( $themegrill_demos ) && isset( $themegrill_demos['message'] ) ) {
				return array(
					'success' => false,
					'message' => 'Failed to fetch ThemeGrill demos: ' . ( $themegrill_demos['message'] ?? 'Unknown error' ),
				);
			}

			$demos = array_merge( $zakra_demos, $themegrill_demos );
			usort(
				$demos,
				function ( $a, $b ) {
					return strtotime( $b->created ) - strtotime( $a->created );
				}
			);

			set_transient( 'themegrill_demo_importer_demos', $demos );

		}
		$data = static::get_filtered_data( $demos, $template );
		return apply_filters(
			'themegrill_demo_importer_packages_template',
			$data
		);
	}

	public static function get_filtered_data( $demos, $template ) {
		$filtered_demos = [];
		if ( 'all' === $template ) {
			$filtered_demos = array_filter(
				$demos,
				function ( $demo ) {
					return in_array( $demo->theme_slug, [ 'zakra','colormag' ], true );
				}
			);
		} else {
			$filtered_demos = array_filter(
				$demos,
				function ( $demo ) use ( $template ) {
					return $template === $demo->theme_slug;
				}
			);
		}

		$filtered_demos = array_values( $filtered_demos );

		$categories   = array( 'all' => 'All' );
		$pagebuilders = array();
		foreach ( $filtered_demos as $demo ) {
			foreach ( $demo->categories as $category ) {
				$slug = strtolower( trim( str_replace( array( ' ', '_' ), '-', $category ) ) );
				if ( ! isset( $categories[ $slug ] ) ) {
					$value               = strtolower( trim( str_replace( array( '-', '_' ), ' ', $category ) ) );
					$categories[ $slug ] = ucfirst( $value );
				}
			}
			if ( ! empty( $demo->pagebuilder ) ) {
				$slug = strtolower( trim( str_replace( array( ' ', '_' ), '-', $demo->pagebuilder ) ) );
				if ( ! isset( $pagebuilders[ $slug ] ) ) {
					$value                 = strtolower( trim( str_replace( array( '-', '_' ), ' ', $demo->pagebuilder ) ) );
					$pagebuilders[ $slug ] = ucfirst( $value );
				}
			}
		}

		$categories = array_map(
			function ( $label, $slug ) {
				return array(
					'id'    => $slug,
					'value' => $label,
				);
			},
			$categories,
			array_keys( $categories )
		);

		//sort categories as all, free and premium to be the first three categories
		$priority_categories = array();
		$regular_categories  = array();

		foreach ( $categories as $category ) {
			if ( in_array( $category['id'], array( 'all', 'free', 'premium' ), true ) ) {
				$priority_categories[] = $category;
			} else {
				$regular_categories[] = $category;
			}
		}

		$sorted_categories = array();
		foreach ( array( 'all', 'free', 'premium' ) as $priority_slug ) {
			foreach ( $priority_categories as $cat ) {
				if ( $cat['id'] === $priority_slug ) {
					$sorted_categories[] = $cat;
					break;
				}
			}
		}

		usort(
			$regular_categories,
			function ( $a, $b ) {
				return strcasecmp( $a['value'], $b['value'] );
			}
		);

		$categories = array_merge( $sorted_categories, $regular_categories );

		$pagebuilders = array_map(
			function ( $label, $slug ) {
				return array(
					'id'    => $slug,
					'value' => $label,
				);
			},
			$pagebuilders,
			array_keys( $pagebuilders )
		);

		//sort pagebuilders as gutenberg to be first and elementor to be second and others
		$priority_pagebuilders = array();
		$regular_pagebuilders  = array();

		foreach ( $pagebuilders as $pagebuilder ) {
			if ( in_array( $pagebuilder['id'], array( 'gutenberg', 'elementor' ), true ) ) {
				$priority_pagebuilders[] = $pagebuilder;
			} else {
				$regular_pagebuilders[] = $pagebuilder;
			}
		}

		$sorted_pagebuilders = array();
		foreach ( array( 'gutenberg', 'elementor' ) as $priority_slug ) {
			foreach ( $priority_pagebuilders as $cat ) {
				if ( $cat['id'] === $priority_slug ) {
					$sorted_pagebuilders[] = $cat;
					break;
				}
			}
		}

		usort(
			$regular_pagebuilders,
			function ( $a, $b ) {
				return strcasecmp( $a['value'], $b['value'] );
			}
		);

		$pagebuilders = array_merge( $sorted_pagebuilders, $regular_pagebuilders );

		$data = array(
			'categories' => $categories,
			'builders'   => $pagebuilders,
			'demos'      => $filtered_demos,
		);
		return $data;
	}

	public static function fetch_demo_data( $url ) {
		$api_url = $url . '/sites';

		$data = wp_remote_get(
			$api_url,
			array(
				'headers'   => array(
					'User-Agent'   => 'ThemeGrill/1.0',
					'Content-Type' => 'application/json',
				),
				'sslverify' => false,
			)
		);

		if ( is_wp_error( $data ) ) {
			return array(
				'success' => false,
				'message' => $data->get_error_message(),
			);
		}

		$body = wp_remote_retrieve_body( $data );

		if ( empty( $body ) ) {
			return array(
				'success' => false,
				'message' => 'Empty response body',
			);
		}

		$response_code = wp_remote_retrieve_response_code( $data );
		if ( 200 !== $response_code ) {
			return array(
				'success' => false,
				'message' => 'Failed to fetch data.',
			);
		}

		$all_demos = json_decode( $body );

		if ( json_last_error() !== JSON_ERROR_NONE || ! is_array( $all_demos ) ) {
			return array(
				'success' => false,
				'message' => 'Invalid JSON',
			);
		}

		return $all_demos;
	}

	public static function get_localized_data() {
		$installed_themes       = array_keys( wp_get_themes() );
		$installed_plugins      = array_keys( get_plugins() );
		$is_installed_zakra_pro = in_array( 'zakra-pro/zakra-pro.php', $installed_plugins, true ) ? true : false;
		$is_active_zakra_pro    = false;
		if ( $is_installed_zakra_pro ) {
			$is_active_zakra_pro = is_plugin_active( 'zakra-pro/zakra-pro.php' ) ? true : false;
		}
		$theme = static::get_theme();

		$localized_data = array(
			'theme'               => $theme,
			'siteUrl'             => site_url(),
			'installed_themes'    => $installed_themes,
			'current_theme'       => get_option( 'template' ),
			'zakra_pro_installed' => $is_installed_zakra_pro,
			'zakra_pro_activated' => $is_active_zakra_pro,
		);

		return $localized_data;
	}
}
