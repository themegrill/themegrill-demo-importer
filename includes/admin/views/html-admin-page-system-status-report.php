<?php
/**
 * Admin View: Page - System Status
 *
 * @package ThemeGrill_Demo_Importer
 */

defined( 'ABSPATH' ) || exit;
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
			<td><?php esc_html_e( 'Operating System', 'themegrill-demo-importer' ); ?></td>
			<td><?php echo esc_html( PHP_OS ); ?></td>
			<td></td>
		</tr>
		<tr>
			<td><?php esc_html_e( 'Server', 'themegrill-demo-importer' ); ?></td>
			<td><?php echo esc_html( wp_unslash( $_SERVER['SERVER_SOFTWARE'] ) ); ?></td>
			<td></td>
		</tr>
		<tr>
			<td><?php esc_html_e( 'MySQL Version', 'themegrill-demo-importer' ); ?></td>
			<td><?php echo esc_html( get_mysql_version() ); ?></td>
			<td></td>
		</tr>
		<tr>
			<td><?php esc_html_e( 'PHP Version', 'themegrill-demo-importer' ); ?></td>
			<td><?php echo esc_html( PHP_VERSION ); ?></td>
			<td></td>
		</tr>
		</tbody>
	</table>
</div>
