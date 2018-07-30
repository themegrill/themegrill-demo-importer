<?php
/**
 * Demo Importer Updates.
 *
 * Backward compatibility for demo importer configs and options.
 *
 * @package Importer/Functions
 * @version 1.1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Update demo importer config.
 *
 * @since 1.1.0
 *
 * @param  array $demo_config Demo config.
 * @return array
 */
function tg_update_demo_importer_config( $demo_config ) {
	if ( ! empty( $demo_config ) ) {
		foreach ( $demo_config as $demo_id => $demo_data ) {

			// Set theme name, if not found.
			if ( ! isset( $demo_data['theme'] ) ) {
				$demo_config[ $demo_id ]['theme'] = current( explode( ' ', $demo_data['name'] ) );
			}

			// Set demo preview URL, if not found.
			if ( ! isset( $demo_data['preview_url'] ) ) {
				$demo_config[ $demo_id ]['preview_url'] = isset( $demo_data['preview'] ) ? $demo_data['preview'] : $demo_data['demo_url'];
			}

			// BW Compat plugins list.
			if ( ! empty( $demo_data['plugins_list'] ) ) {
				foreach ( $demo_data['plugins_list'] as $plugin_type => $plugins ) {
					if ( ! in_array( $plugin_type, array( 'required', 'recommended' ) ) ) {
						continue;
					}

					// Format values base on plugin type.
					switch ( $plugin_type ) {
						case 'required':
							foreach ( $plugins as $plugins_key => $plugins_data ) {
								$demo_data['plugins_list'][ $plugins_key ] = $plugins_data;
								$demo_data['plugins_list'][ $plugins_key ]['required'] = true;
							}
						break;
						case 'recommended':
							foreach ( $plugins as $plugins_key => $plugins_data ) {
								$demo_data['plugins_list'][ $plugins_key ] = $plugins_data;
								$demo_data['plugins_list'][ $plugins_key ]['required'] = false;
							}
						break;
					}

					// Remove the old plugins list.
					unset( $demo_data['plugins_list'][ $plugin_type ] );
				}

				// Update plugin lists data.
				$demo_config[ $demo_id ]['plugins_list'] = $demo_data['plugins_list'];
			}
		}
	}

	return $demo_config;
}
add_filter( 'themegrill_demo_importer_config', 'tg_update_demo_importer_config', 99 );

/**
 * Update demo importer packages.
 *
 * @since 1.5.0
 *
 * @param  array $packages Demo packages.
 * @return array
 */
function tg_update_demo_importer_packages( $packages ) {
	if ( ! empty( $packages ) ) {
		foreach ( $packages as $package_id => $package_data ) {

			// Set theme name, if not found.
			if ( ! isset( $package_data['theme'] ) ) {
				$packages[ $package_id ]['theme'] = current( explode( ' ', $package_data['name'] ) );
			}

			// Set demo preview URL, if not found.
			if ( ! isset( $package_data['preview_url'] ) ) {
				$packages[ $package_id ]['preview_url'] = $package_data['preview'];
			}
		}
	}

	return $packages;
}
add_filter( 'themegrill_demo_importer_packages', 'tg_update_demo_importer_packages', 99 );

/**
 * Update demo importer options.
 *
 * @since 1.3.4
 */
function tg_update_demo_importer_options() {
	$migrate_options = array(
		'themegrill_demo_imported_id'             => 'themegrill_demo_importer_activated_id',
		'themegrill_demo_imported_notice_dismiss' => 'themegrill_demo_importer_reset_notice',
	);

	foreach ( $migrate_options as $old_option => $new_option ) {
		$value = get_option( $old_option );

		if ( $value ) {
			update_option( $new_option, $value );
			delete_option( $old_option );
		}
	}
}
add_action( 'admin_init', 'tg_update_demo_importer_options' );
