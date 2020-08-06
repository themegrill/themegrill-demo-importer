<?php
/**
 * Admin View: Page - Demo Import FAQ's
 *
 * @package ThemeGrill_Demo_Importer
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="demo-importer-faq">
	<h2><?php esc_html_e( 'FAQ\'s', 'themegrill-demo-importer' ); ?></h2>

	<?php
	$faq_rss = 'https://docs.themegrill.com/themegrill-demo-importer/docs-category/faqs/feed/';

	// Fetch the RSS feeds.
	if ( is_string( $faq_rss ) ) {
		$faq_rss = fetch_feed( $faq_rss );
	} elseif ( is_array( $faq_rss ) && isset( $faq_rss['url'] ) ) {
		$faq_rss = fetch_feed( $faq_rss['url'] );
	} elseif ( ! is_object( $faq_rss ) ) {
		return;
	}

	// If error, show them.
	if ( is_wp_error( $faq_rss ) ) {
		if ( is_admin() || current_user_can( 'switch_theme' ) ) {
			echo '<p><strong>' . __( 'Error fetching the FAQ\'s', 'themegrill-demo-importer' ) . '</strong> ' . $faq_rss->get_error_message() . '</p>';
		}

		return;
	}

	// Return if empty quantity from RSS feed.
	if ( ! $faq_rss->get_item_quantity() ) {
		echo '<p>' . __( 'An error has occurred, which probably means our server is down. Try again later.', 'themegrill-demo-importer' ) . '</p>>';
		$faq_rss->__destruct();
		unset( $faq_rss );

		return;
	}

	// Loop through RSS feeds.
	echo '<div class="demo-importer-faq-wrapper">';
	foreach ( $faq_rss->get_items( 0, 5 ) as $faq ) {
		$link        = $faq->get_permalink();
		$title       = $faq->get_title();
		$description = $faq->get_content();
		$description = substr( wpautop( $description ), 0, strpos( wpautop( $description ), '</p>' ) + 4 );

		echo '<div class="postbox"><div class="inside"><div class="faq main">';
		echo '<h3><a href="' . esc_url( strip_tags( $link ) ) . '" target="_blank">' . esc_html( strip_tags( $title ) ) . '</a></h3>';
		echo '<p>' . strip_tags( $description ) . '</p>';
		echo '</div></div></div>';
	}
	echo '</div>';

	$faq_rss->__destruct();
	unset( $faq_rss );
	?>

	<a class="button button-primary button-hero" href="https://docs.themegrill.com/themegrill-demo-importer/docs-category/faqs/" target="_blank"><?php esc_html_e( 'View More FAQ\'s', 'themegrill-demo-importer' ); ?></a>
</div>
