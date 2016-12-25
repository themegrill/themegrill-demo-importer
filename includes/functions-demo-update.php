<?php
/**
 * Demo Importer Updates.
 *
 * Backward compatibility for demo configs.
 *
 * @author   ThemeGrill
 * @category Admin
 * @package  Importer/Functions
 * @version  1.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Update demo importer config.
 *
 * @since 1.1.0
 *
 * @param  array $demo_config
 * @return array
 */
function tg_update_demo_importer_config( $demo_config ) {
	if ( ! empty( $demo_config ) ) {
		foreach ( $demo_config as $demo_id => $demo_data ) {

			// Set theme name, if not found.
			if ( ! isset( $demo_data['theme'] ) ) {
				$demo_config[ $demo_id ]['theme'] = current( explode( ' ', $demo_data['name'] ) );
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
