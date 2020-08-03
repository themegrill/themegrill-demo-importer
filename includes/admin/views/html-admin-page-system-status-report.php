<?php
/**
 * Admin View: Page - System Status
 *
 * @package ThemeGrill_Demo_Importer
 */

defined( 'ABSPATH' ) || exit;

global $wpdb;
?>
<div class="demo-importer-system-status">
	<h2><?php esc_html_e( 'System Status', 'themegrill-demo-importer' ); ?></h2>

	<table class="widefat">
		<thead>
		<tr>
			<th><?php esc_html_e( 'System Info', 'themegrill-demo-importer' ); ?></th>
			<th></th>
			<th></th>
		</tr>
		</thead>
		<tbody>
		<tr>
			<td><?php esc_html_e( 'Operating System:', 'themegrill-demo-importer' ); ?></td>
			<td><?php echo esc_html( PHP_OS ); ?></td>
			<td></td>
		</tr>
		<tr>
			<td><?php esc_html_e( 'Serve:r', 'themegrill-demo-importer' ); ?></td>
			<td><?php echo esc_html( wp_unslash( $_SERVER['SERVER_SOFTWARE'] ) ); ?></td>
			<td></td>
		</tr>
		<tr>
			<td><?php esc_html_e( 'MySQL Version:', 'themegrill-demo-importer' ); ?></td>
			<td><?php echo esc_html( $wpdb->get_var( 'SELECT VERSION()' ) ); ?></td>
			<td></td>
		</tr>
		<tr>
			<td><?php esc_html_e( 'PHP Version:', 'themegrill-demo-importer' ); ?></td>
			<td><?php echo esc_html( PHP_VERSION ); ?></td>
			<td></td>
		</tr>
		<tr>
			<td><?php esc_html_e( 'PHP Max Execution Time:', 'themegrill-demo-importer' ); ?></td>
			<td><?php echo esc_html( ini_get( 'max_execution_time' ) ); ?></td>
			<td></td>
		</tr>
		<tr>
			<td><?php esc_html_e( 'PHP Max Upload Size:', 'themegrill-demo-importer' ); ?></td>
			<td><?php echo esc_html( ini_get( 'upload_max_filesize' ) ); ?></td>
			<td></td>
		</tr>
		<tr>
			<td><?php esc_html_e( 'PHP Post Max Size:', 'themegrill-demo-importer' ); ?></td>
			<td><?php echo esc_html( ini_get( 'post_max_size' ) ); ?></td>
			<td></td>
		</tr>
		<tr>
			<td><?php esc_html_e( 'PHP Max Input Vars:', 'themegrill-demo-importer' ); ?></td>
			<td><?php echo esc_html( ini_get( 'max_input_vars' ) ); ?></td>
			<td></td>
		</tr>
		<tr>
			<td><?php esc_html_e( 'PHP Memory Limit:', 'themegrill-demo-importer' ); ?></td>
			<td><?php echo esc_html( ini_get( 'memory_limit' ) ); ?></td>
			<td></td>
		</tr>
		<tr>
			<td><?php esc_html_e( 'GD Installed:', 'themegrill-demo-importer' ); ?></td>
			<td><?php echo esc_html( extension_loaded( 'gd' ) ? __( 'Yes', 'themegrill-demo-importer' ) : __( 'No', 'themegrill-demo-importer' ) ); ?></td>
			<td></td>
		</tr>
		<tr>
			<td><?php esc_html_e( 'Write Permission:', 'themegrill-demo-importer' ); ?></td>
			<td><?php echo TG_Demo_Importer_Status::get_write_permission(); ?></td>
			<td></td>
		</tr>
		<tr>
			<td><?php esc_html_e( 'Demo Pack Server Connection:', 'themegrill-demo-importer' ); ?></td>
			<td><?php echo TG_Demo_Importer_Status::get_demo_server_connection_status(); ?></td>
			<td></td>
		</tr>
		</tbody>
	</table>
</div>
