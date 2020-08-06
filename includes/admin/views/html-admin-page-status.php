<?php
/**
 * Admin View: Page - Status
 *
 * @package ThemeGrill_Demo_Importer
 */

defined( 'ABSPATH' ) || exit;

// Tabs on status page.
$current_tab = ! empty( $_REQUEST['tab'] ) ? sanitize_title( $_REQUEST['tab'] ) : 'status';
$tabs        = array(
	'status' => esc_html__( 'System Status', 'themegrill-demo-importer' ),
	'faq'    => esc_html__( 'FAQ\'s', 'themegrill-demo-importer' ),
);
$tabs        = apply_filters( 'themegrill_demo_importer_status_tabs', $tabs );
?>
<div class="wrap demo-importer-status">
	<nav class="nav-tab-wrapper">
		<?php
		foreach ( $tabs as $name => $label ) {
			echo '<a href="' . admin_url( 'themes.php?page=demo-importer-status&tab=' . $name ) . '" class="nav-tab ';
			if ( $current_tab == $name ) {
				echo 'nav-tab-active';
			}
			echo '">' . $label . '</a>';
		}
		?>
	</nav>
	<h1 class="screen-reader-text"><?php echo esc_html( $tabs[ $current_tab ] ); ?></h1>
	<?php
	switch ( $current_tab ) {
		case 'status':
			TG_Demo_Importer_Status::system_status();
			break;

		case 'faq':
			TG_Demo_Importer_Status::demo_import_faqs();
			break;

		default:
			do_action( 'themegrill_demo_importer_status_content_' . $current_tab );
			break;
	}
	?>
</div>
