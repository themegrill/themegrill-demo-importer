<?php
/**
 * ThemeModsImporter.
 */
namespace ThemeGrill\StarterTemplates\Import\Importers;

use Psr\Log\LoggerInterface;
use ThemeGrill\StarterTemplates\Cache\TransientCache;
use ThemeGrill\StarterTemplates\Import\Contracts\ImporterInterface;
use ThemeGrill\StarterTemplates\Services\ImageService;
use ThemeGrill\StarterTemplates\Traits\Hooks;

defined( 'ABSPATH' ) || exit;

/**
 * ThemeModsImporter class.
 */
class ThemeModsImporter implements ImporterInterface {

	use Hooks;

	/**
	 * Constructor.
	 *
	 * @param LoggerInterface $logger
	 */
	public function __construct( private LoggerInterface $logger ) {}

	/**
	 * Import.
	 *
	 * @param array $data
	 * @return boolean
	 */
	public function import( array $data ) {
		$this->logger->info( 'Starting theme mods import process...' );

		$this->doAction( 'themegrill:starter-templates:import-theme-mods-start', $data );

		$key         = TransientCache::get( 'site_id', '' );
		$importState = TransientCache::get(
			'import_state_' . $key,
			[
				'taxonomy_map'            => [],
				'item_map'                => [],
				'orphaned_items'          => [],
				'thumbnail_map'           => [],
				'url_map'                 => [],
				'menu_items'              => [],
				'content_items'           => [],
				'orphaned_taxonomy_items' => [],
			]
		);

		$this->resetThemeMods();

		foreach ( $data as $key => $value ) {
			$this->doAction( 'themegrill:starter-templates:import-theme-mod', $key, $value );

			if ( is_string( $value ) ) {
				$urls = ImageService::extractImageUrls( $value );
				if ( ! empty( $urls ) ) {
					$urls  = array_combine( $urls, $urls );
					$urls  = array_map( 'wp_unslash', $urls );
					$urls  = array_map( [ ImageService::class, 'remapHost' ], $urls );
					$value = str_replace( array_keys( $urls ), array_values( $urls ), $value );
				}
			}

			$this->mapTermsAndPostIds( $key, $value, (array) $importState );
			set_theme_mod( $key, $value );

			$this->doAction( 'themegrill:starter-templates:theme-mod-imported', $key, $value );
		}

		$this->logger->info( 'Theme mods imported...' );

		$this->doAction( 'themegrill:starter-templates:theme-mods-import-complete', $data );

		return true;
	}

	/**
	 * Map terms and post IDs.
	 *
	 * @param string $key
	 * @param mixed  $value
	 * @param string|null $siteId
	 * @param array|null  $taxMap
	 * @param array|null  $itemMap
	 */
	private function mapTermsAndPostIds( string $key, &$value, array $importState ) {
		if ( $key === 'nav_menu_locations' && is_array( $value ) ) {
			foreach ( $value as $location => $menuId ) {
				if ( isset( $importState['taxonomy_map'][ (int) $menuId ] ) ) {
					$value[ $location ] = $importState['taxonomy_map'][ (int) $menuId ];
				}
			}
		}

		if ( $key === 'custom_css_post_id' && is_numeric( $value ) ) {
			if ( isset( $importState['item_map'][ (int) $value ] ) ) {
				$value = $importState['item_map'][ (int) $value ];
			}
		}
	}

	/**
	 * Reset theme mods.
	 *
	 * @return void
	 */
	private function resetThemeMods() {
		$mods = get_theme_mods();
		TransientCache::put( 'original_theme_mods', $mods );
		remove_theme_mods();
	}
}
