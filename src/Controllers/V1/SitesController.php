<?php
/**
 * Sites controller class for ThemeGrill Starter Templates REST API.
 *
 * @package ThemeGrill\StarterTemplates\Controllers\V1
 * @since   2.0.0
 */
namespace ThemeGrill\StarterTemplates\Controllers\V1;

use ThemeGrill\StarterTemplates\Services\SitesService;
use ThemeGrill\StarterTemplates\Traits\Hooks;

defined( 'ABSPATH' ) || exit;

/**
 * SitesController class.
 */
class SitesController extends Controller {

	use Hooks;

	/** {@inheritDoc} */
	protected $rest_base = 'sites';

	/** {@inheritDoc} */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			[
				[
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_items' ],
					'permission_callback' => '__return_true',
					'args'                => $this->get_collection_params(),
				],
			]
		);
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<source>[\w-]+)/(?P<id>[\w-]+)',
			[
				[
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_item' ],
					'permission_callback' => [ $this, 'get_item_permissions_check' ],
					'args'                => [
						'args' => [
							'id'     => [
								'description' => __( 'Unique identifier for the site.', 'themegrill-demo-importer' ),
								'type'        => 'string',
								'required'    => true,
							],
							'source' => [
								'description' => __( 'Source of the site data.', 'themegrill-demo-importer' ),
								'type'        => 'string',
								'required'    => true,
								'enum'        => SitesService::SOURCES,
							],
						],
					],
				],
			]
		);
	}

	/**
	 * Get a collection of sites.
	 *
	 * @since 1.0.0
	 * @param \WP_REST_Request $request Full details about the request.
	 * @return \WP_REST_Response|\WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function get_items( $request ) {
		$forceRefresh = (bool) $request->get_param( 'refresh' );

		$this->doAction( 'themegrill:starter-templates:get-sites', $forceRefresh );

		try {
			$sources     = SitesService::getSource();
			$sourceCount = count( $sources );
			$sitesData   = [];

			foreach ( $sources as $source ) {
				$sites = SitesService::getAllSites( $source, $forceRefresh );

				foreach ( $sites as &$site ) {
					$site['source']     = $source;
					$site['_sort_time'] = strtotime( $site['lastUpdated'] );
				}
				unset( $site );

				usort(
					$sites,
					fn( $a, $b ) => ( $b['new'] <=> $a['new'] ) ?: ( $b['_sort_time'] <=> $a['_sort_time'] )
				);

				$sitesData[] = $sites;
			}

			if ( 2 === $sourceCount ) {
				$sites1 = array_filter(
					$sitesData[0],
					fn( $site ) => isset( $site['theme_slug'] ) &&
							in_array( $site['theme_slug'], [ 'colormag', 'colormag-pro' ], true )
				);
				$sites2 = $sitesData[1];

				$len1 = count( $sites1 );
				$len2 = count( $sites2 );

				if ( 0 === $len1 ) {
					$sitesData = $sites2;
				} elseif ( 0 === $len2 ) {
					$sitesData = array_values( $sites1 );
				} else {
					$result        = [];
					$sites1        = array_values( $sites1 );
					$index1        = 0;
					$index2        = 0;
					$count         = 1;
					$takeFromFirst = true;

					while ( $index1 < $len1 || $index2 < $len2 ) {
						if ( $takeFromFirst && $index1 < $len1 ) {
							$chunk   = array_slice( $sites1, $index1, $count );
							$result  = array_merge( $result, $chunk );
							$index1 += count( $chunk );
						} elseif ( ! $takeFromFirst && $index2 < $len2 ) {
							$chunk   = array_slice( $sites2, $index2, $count );
							$result  = array_merge( $result, $chunk );
							$index2 += count( $chunk );
						}

						$takeFromFirst = ! $takeFromFirst;
						++$count;
					}

					$sitesData = $result;
				}
			} else {
				$sitesData = $sitesData[0];
			}

			$sitesData = $this->applyFilters( 'themegrill:starter-templates:sites', $sitesData, $forceRefresh );

		} catch ( \Exception $e ) {
			$this->doAction( 'themegrill:starter-templates:sites-fetch-error', $e );
			return new \WP_Error(
				'sites_fetch_error',
				$e->getMessage(),
				[ 'status' => 500 ]
			);
		}

		$this->doAction( 'themegrill:starter-templates:sites-fetched', $sitesData, $forceRefresh );

		return rest_ensure_response( $sitesData );
	}

	/**
	 * Get a single site.
	 *
	 * @since 1.0.0
	 * @param \WP_REST_Request $request Full details about the request.
	 * @return \WP_REST_Response|\WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function get_item( $request ) {
		$siteId = $request->get_param( 'id' );
		$source = $request->get_param( 'source' );

		$this->doAction( 'themegrill:starter-templates:get-site', $siteId, $source );

		try {
			$siteData              = SitesService::getSiteData( $source, $siteId );
			$siteData['canImport'] = $this->checkSiteImportCriteria( $siteData );

			$siteData = $this->applyFilters( 'themegrill:starter-templates:site', $siteData, $siteId, $source );

		} catch ( \Exception $e ) {
			$this->doAction( 'themegrill:starter-templates:site-fetch-error', $e, $siteId, $source );
			return new \WP_Error(
				'site_fetch_error',
				$e->getMessage(),
				[ 'status' => 500 ]
			);
		}

		$this->doAction( 'themegrill:starter-templates:site-fetched', $siteData, $siteId, $source );

		return rest_ensure_response( $siteData );
	}

	/**
	 * Check criteria for importing a site.
	 *
	 * @param array $siteData
	 * @return boolean
	 */
	public function checkSiteImportCriteria( array $siteData ) {
		$isPremium = $siteData['premium'];

		if ( ! $isPremium ) {
			return true;
		}

		$theme = $siteData['theme_slug'];

		if ( 'zakra' === $theme ) {
			return is_plugin_active( 'zakra-pro/zakra-pro.php' ) && 'zakra' === get_template();
		}
		$theme = str_ends_with( $theme, '-pro' ) ? $theme : $theme . '-pro';

		$canImport = get_template() === $theme;

		return $this->applyFilters( 'themegrill:starter-templates:can-import-site', $canImport, $siteData );
	}

	/**
	 * Check if a given request has access to get items.
	 *
	 * @since 1.0.0
	 * @param \WP_REST_Request $request Full details about the request.
	 * @return true|\WP_Error True if the request has read access, WP_Error object otherwise.
	 */
	public function get_items_permissions_check( $request ) {
		return $this->get_item_permissions_check( $request );
	}

	/**
	 * Check if a given request has access to get a single item.
	 *
	 * @since 1.0.0
	 * @param \WP_REST_Request $request Full details about the request.
	 * @return true|\WP_Error True if the request has read access, WP_Error object otherwise.
	 */
	public function get_item_permissions_check( $request ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return new \WP_Error(
				'rest_forbidden',
				esc_html__( 'Sorry, you are not allowed to access this resource.', 'themegrill-demo-importer' ),
				[ 'status' => 401 ]
			);
		}
		return true;
	}

	/**
	 * Get the query params for collections.
	 *
	 * @since 1.0.0
	 * @return array Collection parameters.
	 */
	public function get_collection_params() {
		return [
			'refresh' => [
				'description' => __( 'Force refresh data from remote source, bypassing cache.', 'themegrill-demo-importer' ),
				'type'        => 'boolean',
				'default'     => false,
				'required'    => false,
			],
		];
	}
}
