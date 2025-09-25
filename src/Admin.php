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
	// public $demo_packages;
	public static $starter_templates_link = '';

	/**
	 * Initialize admin functionality
	 */
	protected function init() {

		// Add Demo Importer menu.
		add_action( 'admin_menu', array( $this, 'admin_menu' ), 12 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		// Disable WooCommerce setup wizard.
		add_action( 'current_screen', array( $this, 'woocommerce_disable_setup_wizard' ) );
	}

	/**
	 * Add menu item.
	 */
	public function admin_menu() {
		global $menu;
		$menu_exists = false;
		$parent_slug = '';

		if ( isset( $menu ) && is_array( $menu ) ) {
			foreach ( $menu as $menu_item ) {
				if ( isset( $menu_item[2] ) && in_array( $menu_item[2], array( 'zakra', 'colormag' ), true ) ) {
					$menu_exists = true;
					$parent_slug = $menu_item[2];
					remove_submenu_page( $parent_slug, $parent_slug . '-starter-templates' );
					remove_submenu_page( $parent_slug, 'demo-importer-status' );
					break;
				}
			}
		}

		if ( $menu_exists ) {
			$page                         = add_submenu_page(
				$parent_slug,
				__( 'Starter Templates', 'themegrill-demo-importer' ),
				__( 'Starter Templates', 'themegrill-demo-importer' ),
				'switch_themes',
				'tg-starter-templates',
				function () {
					echo '<div id="tg-demo-importer"></div>';
				}
			);
			self::$starter_templates_link = 'admin.php?page=tg-starter-templates';
		} else {
			$page                         = add_theme_page(
				__( 'Starter Templates', 'themegrill-demo-importer' ),
				__( 'Starter Templates', 'themegrill-demo-importer' ),
				'switch_themes',
				'tg-starter-templates',
				function () {
					echo '<div id="tg-demo-importer"></div>';
				}
			);
			self::$starter_templates_link = 'themes.php?page=tg-starter-templates';
		}
		add_action( "admin_print_scripts-$page", array( $this, 'enqueue_demo_importer_assets' ) );
	}

	/**
	 * Enqueue scripts.
	 */
	public function enqueue_scripts() {
		wp_enqueue_media();
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
		$demos          = static::get_demo_packages();
		if ( array_key_exists( 'message', $demos ) ) {
			$localized_data['data']      = array();
			$localized_data['error_msg'] = $demos['message'];
		} else {
			$localized_data['data'] = $demos;
		}

		wp_localize_script(
			'tdi-dashboard',
			'__TDI_DASHBOARD__',
			$localized_data
		);
		wp_enqueue_style( 'tdi-dashboard', $asset_url( 'dashboard.css' ), array(), $asset( 'dashboard' )['version'] );
	}

	/**
	 * Disable the WooCommerce Setup Wizard on `Starter Templates & Sites Pack by ThemeGrill` page only.
	 */
	public function woocommerce_disable_setup_wizard() {

		$screen = get_current_screen();

		if ( 'appearance_page_tg-starter-templates' === $screen->id ) {
			add_filter( 'woocommerce_enable_setup_wizard', '__return_false', 1 );
		}
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
	public static function get_demo_packages( $force = false ) {
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

			$themegrill_url   = THEMEGRILL_BASE_URL . TGDM_NAMESPACE;
			$themegrill_demos = static::fetch_demo_data( $themegrill_url );
			if ( is_array( $themegrill_demos ) && isset( $themegrill_demos['message'] ) ) {
				return array(
					'success' => false,
					'message' => 'Failed to fetch ThemeGrill demos: ' . ( $themegrill_demos['message'] ?? 'Unknown error' ),
				);
			}

			$zakra_url   = ZAKRA_BASE_URL . TGDM_NAMESPACE;
			$zakra_demos = static::fetch_demo_data( $zakra_url );
			if ( is_array( $zakra_demos ) && isset( $zakra_demos['message'] ) ) {
				return array(
					'success' => false,
					'message' => 'Failed to fetch Zakra demos: ' . ( $zakra_demos['message'] ?? 'Unknown error' ),
				);
			}

			$demos = array_merge( $zakra_demos, $themegrill_demos );
			usort(
				$demos,
				function ( $a, $b ) {
					return strtotime( $b->created ) - strtotime( $a->created );
				}
			);

			set_transient( 'themegrill_demo_importer_demos', $demos, WEEK_IN_SECONDS );

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
				'sslverify' => true,
				'timeout'   => 30,
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
