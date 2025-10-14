<?php
/**
 * Import controller class for ThemeGrill Starter Templates REST API.
 *
 * @package ThemeGrill\StarterTemplates\Controllers\V1
 * @since   2.0.0
 */

namespace ThemeGrill\StarterTemplates\Controllers\V1;

use ThemeGrill\StarterTemplates\Cache\TransientCache;
use ThemeGrill\StarterTemplates\Import\Importers\ContentImporter;
use ThemeGrill\StarterTemplates\Import\Importers\PluginsImporter;
use ThemeGrill\StarterTemplates\Import\Importers\ThemeModsImporter;
use ThemeGrill\StarterTemplates\Import\Importers\WidgetsImporter;
use ThemeGrill\StarterTemplates\Services\FilesystemService;
use ThemeGrill\StarterTemplates\Traits\Hooks;
use ThemeGrill\StarterTemplates\XMLParser;

defined( 'ABSPATH' ) || exit;

/**
 * ImportController class.
 */
class ImportController extends Controller {

	use Hooks;

	/** {@inheritDoc} */
	protected $rest_base = 'import';

	/** {@inheritDoc} */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			$this->rest_base . '/plugins',
			[
				[
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'importPlugins' ],
					'permission_callback' => [ $this, 'importPermissionCheck' ],
					'args'                => [
						'plugins' => [
							'required'          => true,
							'type'              => 'array',
							'validate_callback' => fn( $param ) => is_array( $param ),
						],
					],
				],
			]
		);
		register_rest_route(
			$this->namespace,
			$this->rest_base . '/widgets',
			[
				[
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'importWidgets' ],
					'permission_callback' => [ $this, 'importPermissionCheck' ],
					'args'                => [
						'widgets' => [
							'required'          => true,
							'type'              => 'object',
							'validate_callback' => fn( $param ) => is_array( $param ),
						],
					],
				],
			]
		);
		register_rest_route(
			$this->namespace,
			$this->rest_base . '/theme-mods',
			[
				[
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'importThemeMods' ],
					'permission_callback' => [ $this, 'importPermissionCheck' ],
					'args'                => [
						'theme-mods' => [
							'required'          => true,
							'type'              => 'object',
							'validate_callback' => fn( $param ) => is_array( $param ),
						],
					],
				],
			]
		);
		register_rest_route(
			$this->namespace,
			$this->rest_base . '/content/initialize',
			[
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'importContentInitialization' ],
				'permission_callback' => [ $this, 'importPermissionCheck' ],
				'args'                => [
					'content' => [
						'required'          => true,
						'type'              => 'string',
						'validate_callback' => fn( $param ) => filter_var( $param, FILTER_VALIDATE_URL ),
					],
					'id'      => [
						'required'          => true,
						'validate_callback' => fn( $param ) => ! empty( $param ),
					],
				],
			]
		);
		register_rest_route(
			$this->namespace,
			$this->rest_base . '/content/process/(?P<type>terms|categories|tags|posts)',
			[
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'importContentProcessing' ],
				'permission_callback' => [ $this, 'importPermissionCheck' ],
				'args'                => [
					'type' => [
						'required' => true,
						'type'     => 'string',
						'enum'     => [ 'terms', 'categories', 'tags', 'posts' ],
					],
					'data' => [
						'required'          => true,
						'type'              => 'array',
						'validate_callback' => fn( $param ) => is_array( $param ),
					],
				],
			]
		);
		register_rest_route(
			$this->namespace,
			$this->rest_base . '/content/finalize',
			[
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'importContentFinalization' ],
				'permission_callback' => [ $this, 'importPermissionCheck' ],
			]
		);

		register_rest_route(
			$this->namespace,
			$this->rest_base . '/log',
			[
				[
					'methods'             => \WP_REST_Server::READABLE,
					'permission_callback' => [ $this, 'importPermissionCheck' ],
					'callback'            => fn() => rest_ensure_response( $this->logger->getLog() ),
				],
				[
					'methods'             => \WP_REST_Server::DELETABLE,
					'permission_callback' => [ $this, 'importPermissionCheck' ],
					'callback'            => fn() => $this->logger->truncateLog(),

				],
			]
		);
	}

	/**
	 * Import content initialization.
	 *
	 * @param \WP_REST_Request $request — Full details about the request.
	 * @return \WP_REST_Response|\WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function importContentInitialization( $request ) {
		$contentUrl = $request->get_param( 'content' );
		$id         = $request->get_param( 'id' );

		$this->doAction( 'themegrill:starter-templates:content-init', $contentUrl, $id );

		TransientCache::put( 'site_id', $id );

		$downloadResult = $this->downloadContentFile( $contentUrl );
		if ( is_wp_error( $downloadResult ) ) {
			return $downloadResult;
		}

		$saveResult = $this->saveContentFile( "content-$id.xml", $downloadResult );
		if ( is_wp_error( $saveResult ) ) {
			return $saveResult;
		}

		$parseResult = $this->parseContentFile( $saveResult );
		if ( is_wp_error( $parseResult ) ) {
			return $parseResult;
		}

		$this->doAction( 'themegrill:starter-templates:content-initialized', $parseResult, $id );

		return rest_ensure_response( $parseResult );
	}

	/**
	 * Import content finalization.
	 *
	* @param \WP_REST_Request $request — Full details about the request.
	 * @return \WP_REST_Response|\WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function importContentFinalization( $request ) {
		$data = $request->get_param( 'data' ) ?? [];

		$this->doAction( 'themegrill:starter-templates:content-finalize', $data );

		$result = ( new ContentImporter( $this->logger ) )->postprocessImport( $data );

		$this->doAction( 'themegrill:starter-templates:content-finalized', $result, $data );

		return rest_ensure_response( $result );
	}

	/**
	 * Process content import.
	 *
	 * @param \WP_REST_Request $request — Full details about the request.
	 * @return \WP_REST_Response|\WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function importContentProcessing( $request ) {
		$type = $request->get_param( 'type' );
		$data = $request->get_param( 'data' ) ?? [];

		$this->doAction( 'themegrill:starter-templates:content-process', $type, $data );

		require_once ABSPATH . 'wp-admin/includes/import.php';
		require_once ABSPATH . 'wp-admin/includes/post.php';
		require_once ABSPATH . 'wp-admin/includes/taxonomy.php';

		$importer = new ContentImporter( $this->logger );

		$response = match ( $type ) {
			'terms' => $importer->importTerms( $data ),
			'categories' => $importer->importCategories( $data ),
			'tags' => $importer->importTags( $data ),
			'posts' => $importer->importPosts( $data ),
			default => $this->createError(
				__( 'Invalid content type specified', 'themegrill-demo-importer' )
			)
		};

		$this->doAction( 'themegrill:starter-templates:content-processed', $type, $response, $data );

		return rest_ensure_response( $response );
	}

	/**
	 * Import permission check.
	 *
	 * @param \WP_REST_Request $request — Full details about the request.
	 * @return true|\WP_Error
	 */
	public function importPermissionCheck() {
		$hasPermission = current_user_can( 'manage_options' );

		$hasPermission = $this->applyFilters( 'themegrill:starter-templates:can-import', $hasPermission );

		if ( ! $hasPermission ) {
			return new \WP_Error(
				'rest_forbidden',
				esc_html__( 'Sorry, you are not allowed to import.', 'themegrill-demo-importer' ),
				[ 'status' => 401 ]
			);
		}
		return true;
	}

	/**
	 * Logger permission check.
	 *
	 * @param \WP_REST_Request $request — Full details about the request.
	 * @return true|\WP_Error
	 */
	public function loggerPermissionCheck( $request ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			if ( 'get' === strtolower( $request->get_method() ) ) {
				return new \WP_Error(
					'rest_forbidden',
					esc_html__( 'Sorry, you are not allowed to view logs.', 'themegrill-demo-importer' ),
					[ 'status' => 401 ]
				);
			}
			return new \WP_Error(
				'rest_forbidden',
				esc_html__( 'Sorry, you are not allowed to delete logs.', 'themegrill-demo-importer' ),
				[ 'status' => 401 ]
			);
		}
		return true;
	}

	/**
	 * Import plugins.
	 *
	 * @param \WP_REST_Request $request — Full details about the request.
	 * @return \WP_REST_Response|\WP_Error Response object on success, or WP_Error object on failure
	 */
	public function importPlugins( $request ) {
		$this->logger->truncateLog();
		/** @var array<int, string> */
		$plugins = $request->get_param( 'plugins' ) ?? [];

		$this->doAction( 'themegrill:starter-templates:import-plugins', $plugins );

		$importer = new PluginsImporter( $this->logger );
		$result   = $importer->import( $plugins );

		$this->doAction( 'themegrill:starter-templates:plugins-imported', $result, $plugins );

		return rest_ensure_response( $result );
	}

	/**
	 * Import widgets.
	 *
	 * @param \WP_REST_Request $request — Full details about the request.
	 * @return \WP_REST_Response|\WP_Error Response object on success, or WP_Error object on failure
	 */
	public function importWidgets( \WP_REST_Request $request ) {
		/** @var array<string, array<string,array<string,mixed>>> */
		$widgets = $request->get_param( 'widgets' ) ?? [];

		$this->doAction( 'themegrill:starter-templates:import-widgets', $widgets );

		$importer = new WidgetsImporter( $this->logger );
		$result   = $importer->import( $widgets );

		$this->doAction( 'themegrill:starter-templates:widgets-imported', $result, $widgets );

		return rest_ensure_response( $result );
	}

	/**
	 * Import theme mods.
	 *
	 * @param \WP_REST_Request $request — Full details about the request.
	 * @return \WP_REST_Response|\WP_Error Response object on success, or WP_Error object on failure
	 */
	public function importThemeMods( \WP_REST_Request $request ) {
		$themeMods = $request->get_param( 'theme-mods' ) ?? [];

		$this->doAction( 'themegrill:starter-templates:import-theme-mods', $themeMods );

		$importer = new ThemeModsImporter( $this->logger );
		$result   = $importer->import( $themeMods );

		$this->doAction( 'themegrill:starter-templates:theme-mods-imported', $result, $themeMods );

		return rest_ensure_response( $result );
	}

	/**
	 * Get content file path.
	 *
	 * @param string $filename
	 * @return string
	 */
	private function getContentFilePath( string $filename ) {
		$uploadDir = wp_upload_dir();
		$baseDir   = $uploadDir['basedir'];

		return trailingslashit( $baseDir ) . 'themegrill-starter-templates/' . $filename;
	}

	/**
	 * Create error.
	 *
	 * @param string $message
	 * @param integer $status
	 * @return \WP_Error
	 */
	private function createError( string $message, int $status = 500 ): \WP_Error {
		return new \WP_Error(
			'rest_import_failed',
			$message,
			[ 'status' => $status ]
		);
	}

	/**
	 * Ensure XML declaration for proper parsing.
	 *
	 * @param string $content
	 * @return string
	 */
	private function ensureXmlDeclaration( string $content ): string {
		return str_starts_with( $content, '<?xml' )
			? $content
			: '<?xml version="1.0" encoding="UTF-8" ?>' . $content;
	}

	/**
	 * Content content.
	 *
	 * @param string $filepath
	 * @return array|\WP_Error
	 */
	private function parseContentFile( string $filepath ): array|\WP_Error {
		$this->logger->info( "Parsing content file {$filepath}" );
		$parser = new XMLParser();
		$result = $parser->parse( $filepath );
		if ( is_wp_error( $result ) ) {
			$this->logger->error( "Failed to parse content file {$filepath}" );
			return $result;
		}
		return $result;
	}

	/**
	 * Save content file.
	 *
	 * @param string $url
	 * @param string $content
	 * @return string|\WP_Error
	 */
	private function saveContentFile( string $url, string $content ) {
		if ( ! $this->ensureUploadDirectoryExists() ) {
			$this->logger->error( 'Failed to create upload directory' );
			return $this->createError(
				__( 'Failed to create upload directory for content file.', 'themegrill-demo-importer' )
			);
		}

		$filepath = $this->getContentFilePath( $url );
		$content  = $this->ensureXmlDeclaration( $content );

		if ( ! FilesystemService::put_contents( $filepath, $content ) ) {
			$this->logger->error( "Failed to save content file to {$filepath}" );
			return $this->createError(
				sprintf(
					/* translators: %s: file path */
					__( 'Failed to save content file to %s', 'themegrill-demo-importer' ),
					esc_html( $filepath )
				)
			);
		}

		$this->logger->info( "Saved content file to {$filepath}" );
		return $filepath;
	}

	/**
	 * Ensure upload directory exists.
	 *
	 * @return boolean
	 */
	private function ensureUploadDirectoryExists(): bool {
		$uploadDir  = wp_upload_dir();
		$uploadPath = trailingslashit( $uploadDir['basedir'] ) . 'themegrill-starter-templates';

		if ( is_dir( $uploadPath ) ) {
			return true;
		}

		return wp_mkdir_p( $uploadPath );
	}

	/**
	 * Download content file.
	 *
	 * @param string $url
	 * @return string|\WP_Error
	 */
	private function downloadContentFile( string $url ) {
		$this->logger->info( "Downloading content file from {$url}" );

		$response = wp_remote_get(
			$url,
			[
				'headers' => [
					'User-Agent' => 'ThemeGrill-Starter-Templates/' . THEMEGRILL_STARTER_TEMPLATES_VERSION,
					'Origin'     => get_home_url(),
				],
			]
		);

		if ( is_wp_error( $response ) ) {
			$this->logger->error( "Failed to download content file from {$url}" );
			return $this->createError( $response->get_error_message() );
		}

		$responseCode = wp_remote_retrieve_response_code( $response );
		if ( 200 !== $responseCode ) {
			$this->logger->error(
				"Failed to download content file from {$url}. Response code: {$responseCode}"
			);
			return $this->createError(
				__( 'Failed to download content file. Please check the URL and try again.', 'themegrill-demo-importer' )
			);
		}

		return wp_remote_retrieve_body( $response );
	}
}
