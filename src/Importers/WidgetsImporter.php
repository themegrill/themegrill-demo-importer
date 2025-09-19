<?php

namespace ThemeGrill\Demo\Importer\Importers;

use ThemeGrill\Demo\Importer\Logger;
use WP_Error;
use WP_REST_Response;

class WidgetsImporter {
	private $logger;

	public function __construct() {
		$this->logger = Logger::getInstance();
	}

	public function import( $demo ) {
		if ( ! $demo['widgets'] ) {
			return true;
		}
		$mapping_data = get_option( 'themegrill_demo_importer_mapping', array() );
		$term_id_map  = array();
		if ( ! empty( $mapping_data ) ) {
			$term_id_map = $mapping_data['term_id'] ?? array();
		}
		$this->logger->info( 'Importing widgets...', [ 'start_time' => true ] );

		$import = $this->processImport( $demo['widgets'], $demo['slug'], $demo, $term_id_map );
		if ( is_wp_error( $import ) ) {
			$this->logger->error( 'Error importing widget: ' . $import->get_error_message(), [ 'end_time' => true ] );
			return new WP_Error( 'import_widget_failed', 'Error importing widget.', array( 'status' => 500 ) );
		}
		$this->logger->info( 'Widgets Imported.', [ 'end_time' => true ] );

		return new WP_REST_Response(
			array(
				'success' => true,
				'message' => 'Widget Imported.',
			),
			200
		);
	}


	/**
	 * Import widget JSON data.
	 *
	 * @global array $wp_registered_sidebars
	 * @param  array $data Widgets
	 * @param  string $demo_id     The ID of demo being imported.
	 * @param  array  $demo_data   The data of demo being imported.
	 * @param  array  $term_id_map   Processed Terms Map
	 * @return WP_Error|array WP_Error on failure, $results on success.
	 */
	public static function processImport( $data, $demo_id, $demo_data, $term_id_map ) {
		global $wp_registered_sidebars;
		// Have valid data? If no data or could not decode.
		if ( empty( $data ) || ! is_array( $data ) ) {
			return new WP_Error( 'themegrill_widget_import_data_error', __( 'Invalid data.', 'themegrill-demo-importer' ) );
		}

		// Hook before import.
		do_action( 'themegrill_widget_importer_before_widgets_import' );
		$data = apply_filters( 'themegrill_before_widgets_import_data', $data );

		// Get all available widgets site supports.
		$available_widgets = self::available_widgets();

		// Get all existing widget instances.
		$widget_instances = array();
		foreach ( $available_widgets as $widget_data ) {
			$widget_instances[ $widget_data['id_base'] ] = get_option( 'widget_' . $widget_data['id_base'] );
		}

		// Begin results.
		$results = array();

		// Loop import data's sidebars.
		foreach ( $data as $sidebar_id => $widgets ) {

			// Skip inactive widgets (should not be in export file).
			if ( 'wp_inactive_widgets' === $sidebar_id ) {
				continue;
			}

			// Check if sidebar is available on this site. Otherwise add widgets to inactive, and say so.
			if ( isset( $wp_registered_sidebars[ $sidebar_id ] ) ) {
				$sidebar_available    = true;
				$use_sidebar_id       = $sidebar_id;
				$sidebar_message_type = 'success';
				$sidebar_message      = '';
			} else {
				$sidebar_available    = false;
				$use_sidebar_id       = 'wp_inactive_widgets'; // Add to inactive if sidebar does not exist in theme.
				$sidebar_message_type = 'error';
				$sidebar_message      = __( 'Sidebar does not exist in theme (moving widget to Inactive)', 'themegrill-demo-importer' );
			}

			// Result for sidebar.
			$results[ $sidebar_id ]['name']         = ! empty( $wp_registered_sidebars[ $sidebar_id ]['name'] ) ? $wp_registered_sidebars[ $sidebar_id ]['name'] : $sidebar_id; // Sidebar name if theme supports it; otherwise ID.
			$results[ $sidebar_id ]['message_type'] = $sidebar_message_type;
			$results[ $sidebar_id ]['message']      = $sidebar_message;
			$results[ $sidebar_id ]['widgets']      = array();

			// Loop widgets.
			foreach ( $widgets as $widget_instance_id => $widget ) {

				$fail = false;

				// Get id_base (remove -# from end) and instance ID number.
				$id_base            = preg_replace( '/-[0-9]+$/', '', $widget_instance_id );
				$instance_id_number = str_replace( $id_base . '-', '', $widget_instance_id );

				// Does site support this widget?
				if ( ! $fail && ! isset( $available_widgets[ $id_base ] ) ) {
					$fail                = true;
					$widget_message_type = 'error';
					$widget_message      = __( 'Site does not support widget', 'themegrill-demo-importer' ); // Explain why widget not imported.
				}

				/**
				 * Convert multidimensional objects to multidimensional arrays.
				 *
				 * Some plugins like Jetpack Widget Visibility store settings as multidimensional arrays.
				 * Without this, they are imported as objects and cause fatal error on Widgets page.
				 * If this creates problems for plugins that do actually intend settings in objects then may need to consider other approach: https://wordpress.org/support/topic/problem-with-array-of-arrays.
				 * It is probably much more likely that arrays are used than objects, however.
				 */
				$widget = json_decode( wp_json_encode( $widget ), true );
				/**
				 * Filter to modify settings array.
				 *
				 * Do before identical check because changes may make it identical to end result (such as URL replacements).
				 */
				$widget = apply_filters( 'themegrill_widget_import_settings', $widget, $id_base );

				// Does widget with identical settings already exist in same sidebar?
				if ( ! $fail && isset( $widget_instances[ $id_base ] ) ) {

					// Get existing widgets in this sidebar.
					$sidebars_widgets = get_option( 'sidebars_widgets' );
					$sidebar_widgets  = isset( $sidebars_widgets[ $use_sidebar_id ] ) ? $sidebars_widgets[ $use_sidebar_id ] : array(); // Check Inactive if that's where will go.

					// Loop widgets with ID base.
					$single_widget_instances = ! empty( $widget_instances[ $id_base ] ) ? $widget_instances[ $id_base ] : array();
					foreach ( $single_widget_instances as $check_id => $check_widget ) {

						// Is widget in same sidebar and has identical settings?
						if ( in_array( "$id_base-$check_id", $sidebar_widgets ) && (array) $widget === $check_widget ) {
							$fail                = true;
							$widget_message_type = 'warning';
							$widget_message      = __( 'Widget already exists', 'themegrill-demo-importer' ); // Explain why widget not imported.

							break;
						}
					}
				}

				// No failure.
				if ( ! $fail ) {

					// Add widget instance.
					$single_widget_instances   = get_option( 'widget_' . $id_base ); // All instances for that widget ID base, get fresh every time.
					$single_widget_instances   = ! empty( $single_widget_instances ) ? $single_widget_instances : array( '_multiwidget' => 1 ); // Start fresh if have to.
					$single_widget_instances[] = $widget; // Add it.

					// Get the key it was given.
					end( $single_widget_instances );
					$new_instance_id_number = key( $single_widget_instances );

					// If key is 0, make it 1.
					// When 0, an issue can occur where adding a widget causes data from other widget to load, and the widget doesn't stick (reload wipes it).
					if ( '0' === strval( $new_instance_id_number ) ) {
						$new_instance_id_number                             = 1;
						$single_widget_instances[ $new_instance_id_number ] = $single_widget_instances[0];
						unset( $single_widget_instances[0] );
					}

					// Move _multiwidget to end of array for uniformity.
					if ( isset( $single_widget_instances['_multiwidget'] ) ) {
						$multiwidget = $single_widget_instances['_multiwidget'];
						unset( $single_widget_instances['_multiwidget'] );
						$single_widget_instances['_multiwidget'] = $multiwidget;
					}

					// Update option with new widget.
					update_option( 'widget_' . $id_base, $single_widget_instances );

					// Assign widget instance to sidebar.
					$sidebars_widgets                      = get_option( 'sidebars_widgets' ); // Which sidebars have which widgets, get fresh every time.
					$new_instance_id                       = $id_base . '-' . $new_instance_id_number; // Use ID number from new widget instance.
					$sidebars_widgets[ $use_sidebar_id ][] = $new_instance_id; // Add new instance to sidebar.
					update_option( 'sidebars_widgets', $sidebars_widgets ); // Save the amended data.

					// After widget import action.
					$after_widget_import = array(
						'sidebar'           => $use_sidebar_id,
						'sidebar_old'       => $sidebar_id,
						'widget'            => $widget,
						'widget_type'       => $id_base,
						'widget_id'         => $new_instance_id,
						'widget_id_old'     => $widget_instance_id,
						'widget_id_num'     => $new_instance_id_number,
						'widget_id_num_old' => $instance_id_number,
					);
					do_action( 'themegrill_widget_importer_after_single_widget_import', $after_widget_import );

					// Success message.
					if ( $sidebar_available ) {
						$widget_message_type = 'success';
						$widget_message      = __( 'Imported', 'themegrill-demo-importer' );
					} else {
						$widget_message_type = 'warning';
						$widget_message      = __( 'Imported to Inactive', 'themegrill-demo-importer' );
					}
				}

				// Result for widget instance.
				$results[ $sidebar_id ]['widgets'][ $widget_instance_id ]['name']         = isset( $available_widgets[ $id_base ]['name'] ) ? $available_widgets[ $id_base ]['name'] : $id_base; // Widget name or ID if name not available (not supported by site).
				$results[ $sidebar_id ]['widgets'][ $widget_instance_id ]['title']        = ! empty( $widget['title'] ) ? $widget['title'] : __( 'No Title', 'themegrill-demo-importer' ); // Show "No Title" if widget instance is untitled.
				$results[ $sidebar_id ]['widgets'][ $widget_instance_id ]['message_type'] = $widget_message_type;
				$results[ $sidebar_id ]['widgets'][ $widget_instance_id ]['message']      = $widget_message;
			}
		}

		// Hook after import.
		do_action( 'themegrill_widget_importer_after_widgets_import', $term_id_map );

		// Return results.
		return apply_filters( 'themegrill_widget_import_results', $results );
	}

	/**
	 * Available widgets.
	 *
	 * Gather site's widgets into array with ID base, name, etc.
	 *
	 * @global array $wp_registered_widget_controls
	 * @return array Widget information
	 */
	private static function available_widgets() {
		global $wp_registered_widget_controls;

		$widget_controls   = $wp_registered_widget_controls;
		$available_widgets = array();

		foreach ( $widget_controls as $widget ) {
			if ( ! empty( $widget['id_base'] ) && ! isset( $available_widgets[ $widget['id_base'] ] ) ) {
				$available_widgets[ $widget['id_base'] ]['id_base'] = $widget['id_base'];
				$available_widgets[ $widget['id_base'] ]['name']    = $widget['name'];
			}
		}

		return apply_filters( 'themegrill_widget_importer_available_widgets', $available_widgets );
	}
}
