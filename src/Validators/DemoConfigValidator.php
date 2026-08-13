<?php

namespace ThemeGrill\Demo\Importer\Validators;

use ThemeGrill\Demo\Importer\Helpers\RemoteRequest;
use WP_Error;

/**
 * Schema validation for the `demo_config` payload of /tg-demo-importer/v1/install.
 *
 * Demo configs are theme specific, so the schema only pins down the fields that
 * can reach a dangerous sink (remote downloads and option writes) and lets every
 * other theme supplied key pass through untouched via `additionalProperties`.
 */
class DemoConfigValidator {

	/**
	 * Maximum nesting level accepted inside a demo config.
	 */
	const MAX_DEPTH = 20;

	/**
	 * Schema for the demo_config object.
	 *
	 * @return array
	 */
	public static function get_schema() {
		$free_form = array(
			'type'                 => array( 'object', 'null' ),
			'additionalProperties' => true,
		);

		$remote_file = array(
			'type'      => array( 'string', 'null' ),
			'maxLength' => 2048,
		);

		$schema = array(
			'type'                 => 'object',
			'required'             => array( 'slug' ),
			// Theme specific keys (colormag_*, zakra_*, masteriyo_data, ...) stay allowed.
			'additionalProperties' => true,
			'properties'           => array(
				'slug'                               => array(
					'type'      => 'string',
					'minLength' => 1,
					'maxLength' => 100,
					'pattern'   => '^[A-Za-z0-9_-]+$',
				),
				'title'                              => array(
					'type'      => array( 'string', 'null' ),
					'maxLength' => 200,
				),
				'theme_slug'                         => array(
					'type'      => array( 'string', 'null' ),
					'maxLength' => 100,
				),
				'content'                            => $remote_file,
				'pages'                              => array(
					'type'  => array( 'array', 'null' ),
					'items' => array(
						'type'                 => 'object',
						'additionalProperties' => true,
						'properties'           => array(
							'content' => $remote_file,
						),
					),
				),
				'themeMods'                          => $free_form,
				'widgets'                            => $free_form,
				'plugins'                            => $free_form,
				'wp_options'                         => $free_form,
				'elementor_settings'                 => $free_form,
				'yith_woocommerce_wishlist_settings' => $free_form,
			),
		);

		/**
		 * Filter the demo_config schema.
		 *
		 * @param array $schema JSON schema for demo_config.
		 */
		return apply_filters( 'themegrill_demo_importer_demo_config_schema', $schema );
	}

	/**
	 * Option maps inside demo_config, and the key prefixes each one may write.
	 *
	 * @return array<string, string[]>
	 */
	public static function get_option_map_rules() {
		/**
		 * Filter which demo_config fields write options and what prefixes they may use.
		 *
		 * @param array<string, string[]> $rules Field name => allowed option key prefixes.
		 */
		return apply_filters(
			'themegrill_demo_importer_demo_config_option_map_rules',
			array(
				'elementor_settings'                 => array( 'elementor_' ),
				'yith_woocommerce_wishlist_settings' => array( 'yith_wcwl', 'yith_wishlist' ),
				// Keep in sync with the URM option keys list on the demo export side.
				'urm_settings'               => array(
					'urm_bank_connection_status',
					'user_registration_bank_enabled',
					'user_registration_global_bank_details',
					'user_registration_payment_currency',
				),
			)
		);
	}

	/**
	 * Options a demo import must never overwrite.
	 *
	 * @return string[]
	 */
	public static function get_protected_option_keys() {
		global $wpdb;

		$keys = array(
			'siteurl',
			'home',
			'admin_email',
			'new_admin_email',
			'users_can_register',
			'default_role',
			'active_plugins',
			'template',
			'stylesheet',
			'current_theme',
			'upload_path',
			'upload_url_path',
			'fileupload_url',
			'cron',
			'db_version',
			'initial_db_version',
			'recently_activated',
			'auto_update_core_dev',
			'auto_update_core_minor',
			'auto_update_core_major',
			'auto_update_plugins',
			'auto_update_themes',
		);

		if ( isset( $wpdb ) && ! empty( $wpdb->prefix ) ) {
			$keys[] = $wpdb->prefix . 'user_roles';
		}

		/**
		 * Filter the options a demo import may never write.
		 *
		 * @param string[] $keys Protected option names.
		 */
		return apply_filters( 'themegrill_demo_importer_protected_option_keys', $keys );
	}

	/**
	 * Validate callback for the demo_config REST argument.
	 *
	 * @param mixed            $value   Value sent in the request body.
	 * @param \WP_REST_Request $request Request object.
	 * @param string           $param   Parameter name.
	 * @return true|WP_Error
	 */
	public static function validate( $value, $request = null, $param = 'demo_config' ) {
		if ( ! is_array( $value ) || array() === $value ) {
			return self::error( __( 'Invalid demo config provided.', 'themegrill-demo-importer' ) );
		}

		$valid = rest_validate_value_from_schema( $value, self::get_schema(), $param );
		if ( is_wp_error( $valid ) ) {
			return $valid;
		}

		if ( self::exceeds_max_depth( $value, self::MAX_DEPTH ) ) {
			return self::error( __( 'The demo config is nested too deeply.', 'themegrill-demo-importer' ) );
		}

		// XML sources are downloaded server side, so only allowlisted demo hosts are accepted.
		foreach ( self::collect_remote_files( $value ) as $field => $url ) {
			if ( ! RemoteRequest::is_allowed_url( $url ) ) {
				return self::error(
					sprintf(
						/* translators: %s: demo config field name. */
						__( '%s must be a URL on an allowed ThemeGrill demo host.', 'themegrill-demo-importer' ),
						$field
					)
				);
			}
		}

		foreach ( self::get_option_map_rules() as $field => $prefixes ) {
			$invalid = self::find_invalid_option_key( $value[ $field ] ?? array(), $prefixes );
			if ( null !== $invalid ) {
				return self::error(
					sprintf(
						/* translators: 1: option name, 2: demo config field name. */
						__( 'The option "%1$s" is not allowed in %2$s.', 'themegrill-demo-importer' ),
						$invalid,
						$field
					)
				);
			}
		}

		// Customizer imports write these straight to wp_options via CustomizeDemoImporterSetting.
		$invalid = self::find_invalid_option_key( $value['wp_options']['options'] ?? array(), array() );
		if ( null !== $invalid ) {
			return self::error(
				sprintf(
					/* translators: %s: option name. */
					__( 'The option "%s" is not allowed in wp_options.', 'themegrill-demo-importer' ),
					$invalid
				)
			);
		}

		return true;
	}

	/**
	 * Sanitize callback for the demo_config REST argument.
	 *
	 * @param mixed            $value   Value sent in the request body.
	 * @param \WP_REST_Request $request Request object.
	 * @param string           $param   Parameter name.
	 * @return array|WP_Error
	 */
	public static function sanitize( $value, $request = null, $param = 'demo_config' ) {
		$value = rest_sanitize_value_from_schema( $value, self::get_schema(), $param );

		if ( is_wp_error( $value ) || ! is_array( $value ) ) {
			return $value;
		}

		// Drop anything the option maps are not allowed to write, in case validation was filtered out.
		foreach ( self::get_option_map_rules() as $field => $prefixes ) {
			if ( empty( $value[ $field ] ) || ! is_array( $value[ $field ] ) ) {
				continue;
			}

			foreach ( array_keys( $value[ $field ] ) as $key ) {
				if ( ! self::is_allowed_option_key( (string) $key, $prefixes ) ) {
					unset( $value[ $field ][ $key ] );
				}
			}
		}

		return $value;
	}

	/**
	 * Keep only the option keys a demo_config field is allowed to write.
	 *
	 * The REST layer already rejects and strips disallowed keys; this is the same
	 * rule set for callers that receive demo data outside a validated request,
	 * such as handlers on the public `themegrill_ajax_demo_imported` hook.
	 *
	 * @param mixed  $settings Settings map from the demo config.
	 * @param string $field    demo_config field name, e.g. `elementor_settings`.
	 * @return array Sanitized key => value pairs.
	 */
	public static function filter_option_map( $settings, $field ) {
		if ( empty( $settings ) || ! is_array( $settings ) ) {
			return array();
		}

		$rules    = self::get_option_map_rules();
		$prefixes = $rules[ $field ] ?? array();
		$filtered = array();

		foreach ( $settings as $key => $value ) {
			$key = sanitize_key( (string) $key );
			if ( '' === $key || ! self::is_allowed_option_key( $key, $prefixes ) ) {
				continue;
			}
			$filtered[ $key ] = $value;
		}

		return $filtered;
	}

	/**
	 * Whether an option key may be written by a demo import.
	 *
	 * @param string   $key      Option key from the request.
	 * @param string[] $prefixes Allowed prefixes, empty to allow any non protected key.
	 * @return bool
	 */
	public static function is_allowed_option_key( $key, $prefixes = array() ) {
		$key = sanitize_key( $key );

		if ( '' === $key || in_array( $key, self::get_protected_option_keys(), true ) ) {
			return false;
		}

		if ( empty( $prefixes ) ) {
			return true;
		}

		foreach ( $prefixes as $prefix ) {
			if ( '' !== $prefix && 0 === strpos( $key, $prefix ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Return the first disallowed option key of a settings map, if any.
	 *
	 * @param mixed    $settings Settings map from the request.
	 * @param string[] $prefixes Allowed prefixes.
	 * @return string|null
	 */
	private static function find_invalid_option_key( $settings, $prefixes ) {
		if ( empty( $settings ) || ! is_array( $settings ) ) {
			return null;
		}

		foreach ( array_keys( $settings ) as $key ) {
			if ( ! self::is_allowed_option_key( (string) $key, $prefixes ) ) {
				return (string) $key;
			}
		}

		return null;
	}

	/**
	 * Collect every remote file reference the importer would download.
	 *
	 * @param array $config Demo config.
	 * @return array<string, string> Field label => URL.
	 */
	private static function collect_remote_files( $config ) {
		$files = array();

		if ( ! empty( $config['content'] ) && is_string( $config['content'] ) ) {
			$files['content'] = $config['content'];
		}

		if ( ! empty( $config['pages'] ) && is_array( $config['pages'] ) ) {
			foreach ( $config['pages'] as $index => $page ) {
				if ( ! empty( $page['content'] ) && is_string( $page['content'] ) ) {
					$files[ 'pages[' . $index . '].content' ] = $page['content'];
				}
			}
		}

		return $files;
	}

	/**
	 * Whether a value nests deeper than the given limit.
	 *
	 * @param mixed $value Value to measure.
	 * @param int   $limit Maximum allowed depth.
	 * @param int   $depth Current depth.
	 * @return bool
	 */
	private static function exceeds_max_depth( $value, $limit, $depth = 1 ) {
		if ( ! is_array( $value ) ) {
			return false;
		}

		if ( $depth > $limit ) {
			return true;
		}

		foreach ( $value as $child ) {
			if ( self::exceeds_max_depth( $child, $limit, $depth + 1 ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Build a 400 error response.
	 *
	 * @param string $message Error message.
	 * @return WP_Error
	 */
	private static function error( $message ) {
		return new WP_Error( 'tgdm_invalid_demo_config', $message, array( 'status' => 400 ) );
	}
}
