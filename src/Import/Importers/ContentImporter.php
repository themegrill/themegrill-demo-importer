<?php
/**
 * Content importer class for ThemeGrill Starter Templates.
 *
 * @package ThemeGrill\StarterTemplates\Import\Importers
 * @since   2.0.0
 */

namespace ThemeGrill\StarterTemplates\Import\Importers;

use ThemeGrill\StarterTemplates\Cache\TransientCache;
use ThemeGrill\StarterTemplates\Logger;
use ThemeGrill\StarterTemplates\Services\FilesystemService;
use ThemeGrill\StarterTemplates\Traits\Hooks;

/**
 * ContentImporter class.
 */
class ContentImporter {

	use Hooks;

	private const ATTACHMENT_PATTERN = '!
		(
			class=[\'"].*?\b(wp-image-\d+|attachment-[\w\-]+)\b
		|
			src=[\'"][^\'"]*(
				[0-9]{4}/[0-9]{2}/[^\'"]+\.(jpg|jpeg|png|gif)
			|
				content/uploads[^\'"]+
			)[\'"]
		)!ix';

	private string $siteUrl;
	private Logger $logger;

	private array $itemMap               = [];
	private array $taxonomyMap           = [];
	private array $orphanedItems         = [];
	private array $thumbnailMap          = [];
	private array $urlMap                = [];
	private array $menuItems             = [];
	private array $contentItems          = [];
	private array $orphanedTaxonomyItems = [];

	/**
	 * Constructor.
	 *
	 * @since 2.0.0
	 * @param Logger $logger The logger instance.
	 */
	public function __construct( Logger $logger ) {
		$this->logger  = $logger;
		$this->siteUrl = untrailingslashit( home_url() );
		$this->loadState();
	}

	/**
	 * Load state from transients.
	 *
	 * @return void
	 */
	private function loadState() {
		$this->doAction( 'themegrill:starter-templates:load-import-state' );

		$key   = TransientCache::get( 'site_id', '' );
		$state = TransientCache::get(
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

		$state = $this->applyFilters( 'themegrill:starter-templates:import-state-loaded', $state );

		$this->taxonomyMap           = $state['taxonomy_map'] ?? [];
		$this->itemMap               = $state['item_map'] ?? [];
		$this->orphanedItems         = $state['orphaned_items'] ?? [];
		$this->thumbnailMap          = $state['thumbnail_map'] ?? [];
		$this->urlMap                = $state['url_map'] ?? [];
		$this->menuItems             = $state['menu_items'] ?? [];
		$this->contentItems          = $state['content_items'] ?? [];
		$this->orphanedTaxonomyItems = $state['orphaned_taxonomy_items'] ?? [];

		$this->doAction( 'themegrill:starter-templates:import-state-loaded', $this );
	}

	/**
	 * Save state to transients.
	 *
	 * @return void
	 */
	private function saveState(): void {
		$this->doAction( 'themegrill:starter-templates:save-import-state' );

		$key       = TransientCache::get( 'site_id', '' );
		$nextState = [
			'taxonomy_map'   => $this->taxonomyMap,
			'item_map'       => $this->itemMap,
			'orphaned_items' => $this->orphanedItems,
			'thumbnail_map'  => $this->thumbnailMap,
			'url_map'        => $this->urlMap,
			'menu_items'     => $this->menuItems,
			'content_items'  => $this->contentItems,
		];

		$nextState = $this->applyFilters( 'themegrill:starter-templates:import-state-save', $nextState );

		TransientCache::put(
			'import_state_' . $key,
			$nextState
		);

		$this->doAction( 'themegrill:starter-templates:import-state-saved', $nextState );
	}

	/**
	 * Import categories.
	 *
	 * @param array $categories
	 * @return boolean|array
	 */
	public function importCategories( array $categories ) {
		$this->doAction( 'themegrill:starter-templates:import-categories-start', $categories );

		$this->logger->info( 'Processing categories' );

		$categories = $this->applyFilters(
			'themegrill:starter-templates:import-categories',
			$categories
		);

		if ( empty( $categories ) ) {
			$this->logger->info( 'No categories to process' );
			$this->doAction( 'themegrill:starter-templates:import-categories-empty' );
			return true;
		}

		$this->doAction(
			'themegrill:starter-templates:categories-pre-process',
			$categories
		);

		foreach ( $categories as $category ) {
			$this->processCategory( $category );
		}

		$this->saveState();
		$this->logger->info( 'Finished processing categories' );

		$this->doAction(
			'themegrill:starter-templates:categories-post-process',
			$this->taxonomyMap
		);

		$this->doAction( 'themegrill:starter-templates:import-categories-complete', $this->taxonomyMap );

		return $this->taxonomyMap;
	}

	/**
	 * Process a single category
	 *
	 * @param array $category
	 * @return void
	 */
	private function processCategory( array $category ) {
		$this->doAction( 'themegrill:starter-templates:process-category', $category );

		$this->logger->info( 'Processing category: ' . $category['cat_name'] ?? '' );

		$category = $this->applyFilters( 'themegrill:starter-templates:process-category-args', $category );
		if ( ! empty( $category['term_id'] ) && isset( $this->taxonomyMap[ (int) $category['term_id'] ] ) ) {
			$this->doAction( 'themegrill:starter-templates:category-already-mapped', $category );
			return;
		}

		$existingId = term_exists( $category['category_nicename'], 'category' );

		if ( $existingId ) {
			$termId = is_array( $existingId ) ? $existingId['term_id'] : $existingId;
			$this->mapTaxonomy( $category, $termId );
			$this->doAction( 'themegrill:starter-templates:category-mapped-to-existing', $category, $termId );
			return;
		}

		$categoryId = wp_insert_category(
			[
				'category_nicename'    => $category['category_nicename'],
				'cat_name'             => $category['cat_name'],
				'category_description' => $category['category_description'] ?? '',
			]
		);

		if ( is_wp_error( $categoryId ) ) {
			$this->logError( 'category', $category['cat_name'], $categoryId );
			$this->doAction( 'themegrill:starter-templates:category-insert-error', $category, $categoryId );
			return;
		}

		$this->mapTaxonomy( $category, $categoryId );
		$this->processMetadata( $category, $categoryId, 'term' );

		$this->logger->info( 'Finished processing category: ' . $category['cat_name'] ?? '' );
		$this->doAction( 'themegrill:starter-templates:category-processed', $category, $categoryId );
	}

	/**
	 * Import tags.
	 *
	 * @param array $tags
	 * @return boolean|array
	 */
	public function importTags( array $tags ) {
		$this->doAction( 'themegrill:starter-templates:import-tags-start', $tags );

		$this->logger->info( 'Processing tags' );

		$tags = $this->applyFilters( 'themegrill:starter-templates:import-tags', $tags );

		if ( empty( $tags ) ) {
			$this->logger->warning( 'No tags to process' );
			$this->doAction( 'themegrill:starter-templates:import-tags-empty' );
			return true;
		}

		$this->doAction( 'themegrill:starter-templates:tags-pre-process', $tags );

		foreach ( $tags as $tag ) {
			$this->processTag( $tag );
		}

		$this->saveState();
		$this->logger->info( 'Finished processing tags' );

		$this->doAction( 'themegrill:starter-templates:tags-post-process', $this->taxonomyMap );
		$this->doAction( 'themegrill:starter-templates:import-tags-complete', $this->taxonomyMap );

		return $this->taxonomyMap;
	}

	/**
	 * Process a single tab.
	 *
	 * @param array $tag
	 * @return void
	 */
	private function processTag( array $tag ) {
		$this->doAction( 'themegrill:starter-templates:process-tag', $tag );

		$this->logger->info( 'Processing tag: ' . $tag['tag_name'] ?? '' );

		$tag = $this->applyFilters( 'themegrill:starter-templates:process-tag-args', $tag );

		if ( ! empty( $tag['term_id'] ) && isset( $this->taxonomyMap[ (int) $tag['term_id'] ] ) ) {
			$this->doAction( 'themegrill:starter-templates:tag-already-mapped', $tag );
			return;
		}

		$existingId = term_exists( $tag['tag_slug'], 'post_tag' );

		if ( $existingId ) {
			$termId = is_array( $existingId ) ? $existingId['term_id'] : $existingId;
			$this->mapTaxonomy( $tag, $termId );
			$this->doAction( 'themegrill:starter-templates:tag-mapped-to-existing', $tag, $termId );
			return;
		}

		$result = wp_insert_term(
			$tag['tag_name'],
			'post_tag',
			[
				'slug'        => $tag['tag_slug'],
				'description' => $tag['tag_description'] ?? '',
			]
		);

		if ( is_wp_error( $result ) ) {
			$this->logError( 'tag', $tag['tag_name'], $result );
			$this->doAction( 'themegrill:starter-templates:tag-insert-error', $tag, $result );
			return;
		}

		$this->mapTaxonomy( $tag, $result['term_id'] );
		$this->processMetadata( $tag, $result['term_id'], 'term' );

		$this->logger->info( 'Finished processing tag: ' . $tag['tag_name'] ?? '' );
		$this->doAction( 'themegrill:starter-templates:tag-processed', $tag, $result['term_id'] );
	}

	/**
	 * Import terms.
	 *
	 * @param array $terms
	 * @return boolean|array
	 */
	public function importTerms( array $terms ) {
		$this->doAction( 'themegrill:starter-templates:import-terms-start', $terms );

		$this->logger->info( 'Processing terms' );

		$terms = $this->applyFilters( 'themegrill:starter-templates:import-terms', $terms );

		if ( empty( $terms ) ) {
			$this->logger->warning( 'No terms to process' );
			$this->doAction( 'themegrill:starter-templates:import-terms-empty' );
			return true;
		}

		$this->doAction( 'themegrill:starter-templates:terms-pre-process', $terms );

		foreach ( $terms as $term ) {
			$this->processTerm( $term );
		}

		$this->saveState();
		$this->logger->info( 'Finished processing terms' );

		$this->doAction( 'themegrill:starter-templates:terms-post-process', $this->taxonomyMap );
		$this->doAction( 'themegrill:starter-templates:import-terms-complete', $this->taxonomyMap );

		return $this->taxonomyMap;
	}

	/**
	 * Process a single term.
	 *
	 * @param array $term
	 * @return void
	 */
	private function processTerm( array $term ) {
		$this->doAction( 'themegrill:starter-templates:process-term', $term );

		$this->logger->info( 'Processing term: ' . $term['term_name'] ?? '' );

		$term = $this->applyFilters( 'themegrill:starter-templates:process-term-args', $term );

		if ( ! empty( $term['term_id'] ) && isset( $this->taxonomyMap[ (int) $term['term_id'] ] ) ) {
			$this->doAction( 'themegrill:starter-templates:term-already-mapped', $term );
			return;
		}

		$existingId = term_exists( $term['slug'], $term['term_taxonomy'] );

		if ( $existingId ) {
			$termId = is_array( $existingId ) ? $existingId['term_id'] : $existingId;
			$this->mapTaxonomy( $term, $termId );
			$this->doAction( 'themegrill:starter-templates:term-mapped-to-existing', $term, $termId );
			return;
		}

		$taxonomy = $term['term_taxonomy'];

		$result = wp_insert_term(
			$term['term_name'],
			$taxonomy,
			[
				'slug'        => $term['slug'],
				'description' => $term['description'] ?? '',
				'parent'      => 0,
			]
		);

		if ( is_wp_error( $result ) ) {
			$this->logError( 'term', $term['term_name'], $result );
			$this->doAction( 'themegrill:starter-templates:term-insert-error', $term, $result );
			return;
		}

		$this->mapTaxonomy( $term, $result['term_id'] );
		$this->processMetadata( $term, $result['term_id'], 'term' );

		$this->logger->info( 'Finished processing term: ' . $term['term_name'] ?? '' );
		$this->doAction( 'themegrill:starter-templates:term-processed', $term, $result['term_id'] );
	}

	/**
	 * Map taxonomy for later use.
	 *
	 * @param array $item
	 * @param integer $newId
	 * @return void
	 */
	private function mapTaxonomy( array $item, int $newId ) {
		if ( isset( $item['term_id'] ) ) {
			$this->taxonomyMap[ intval( $item['term_id'] ) ] = $newId;
		}

		$parentId = absint( $item['parent_id'] ?? $item['category_parent'] ?? 0 );
		if ( ! $parentId && ! isset( $this->taxonomyMap[ $newId ] ) ) {
			$this->orphanedTaxonomyItems[ $newId ] = $parentId;
		}
	}

	/**
	 * Process meta data.
	 *
	 * @param array $item
	 * @param integer $itemId
	 * @param string $type
	 * @return void
	 */
	private function processMetadata( array $item, int $itemId, string $type ) {
		$metaKey = $type === 'term' ? 'termmeta' : 'postmeta';

		$this->logger->info( 'Processing metadata for ' . $type . ': ' . $itemId );

		$metadata = $item[ $metaKey ] ?? [];

		if ( ! empty( $metadata ) ) {
			foreach ( $metadata as $meta ) {
				$key = $meta['key'] ?? false;

				if ( ! $key || $key === '_edit_last' ) {
					continue;
				}

				$value = maybe_unserialize( $meta['value'] );

				if ( $type === 'term' ) {
					add_term_meta( $itemId, $key, $value );
				} else {
					update_post_meta( $itemId, $key, $value );

					if ( $key === '_thumbnail_id' ) {
						$this->thumbnailMap[ $itemId ] = intval( $value );
					}
				}
			}
		}

		$this->logger->info( 'Finished processing metadata for ' . $type . ': ' . $itemId );
	}

	/**
	 * Import posts.
	 *
	 * @param array $posts
	 * @return array
	 */
	public function importPosts( array $posts ) {
		$this->doAction( 'themegrill:starter-templates:import-posts-start', $posts );

		$this->logger->info( 'Processing posts' );

		$posts = $this->applyFilters( 'themegrill:starter-templates:import-posts', $posts );

		$this->doAction( 'themegrill:starter-templates:posts-pre-process', $posts );

		foreach ( $posts as $post ) {
			$this->processPost( $post );
		}

		$this->saveState();
		$this->logger->info( 'Finished processing posts' );

		$this->doAction( 'themegrill:starter-templates:posts-post-process', $this->itemMap );
		$this->doAction( 'themegrill:starter-templates:import-posts-complete', $this->itemMap );

		return $this->itemMap;
	}

	/**
	 * Process a single post.
	 *
	 * @param array $post
	 * @return void
	 */
	private function processPost( array $post ) {
		if ( ! $this->validatePost( $post ) ) {
			return;
		}

		if ( 'nav_menu_item' !== $post['post_type'] ) {
			$existingId = post_exists( $post['post_title'], '', '', $post['post_type'] );
			if ( $existingId && get_post_type( $existingId ) === $post['post_type'] ) {
				$this->handleExistingPost( $post, $existingId );
				return;
			}
		}

		$postId = $this->createPost( $post );

		if ( is_wp_error( $postId ) ) {
			return;
		}

		$this->finalizePost( $post, $postId );
	}

	/**
	 * Validate a post.
	 *
	 * @param array $post
	 * @return boolean
	 */
	private function validatePost( array $post ) {
		if ( ! post_type_exists( $post['post_type'] ) ) {
			$this->logger->warning( sprintf( 'Post type %s does not exist', $post['post_type'] ) );
			return false;
		}

		if ( ! empty( $post['post_id'] ) && isset( $this->itemMap[ $post['post_id'] ] ) ) {
			return false;
		}

		if ( $post['status'] === $post['post_type'] ) {
			return false;
		}

		return true;
	}

	/**
	 * Handle existing post.
	 *
	 * @param array $post
	 * @param integer $existingId
	 * @return void
	 */
	private function handleExistingPost( array $post, int $existingId ) {
		$this->logger->warning( sprintf( 'Post %s already exists', $post['post_title'] ) );
		$this->itemMap[ intval( $post['post_id'] ) ] = $existingId;
		$this->finalizePost( $post, $existingId );
	}

	/**
	 * Create post.
	 *
	 * @param array $post
	 * @return integer|\WP_Error Post ID on success or WP_Error on failure.
	 */
	private function createPost( array $post ) {
		$postData = $this->preparePostData( $post );

		if ( $postData['post_type'] === 'attachment' ) {
			$url = $post['attachment_url'] ?? $postData['guid'];
			return $this->createAttachment( $postData, $url );
		}

		$this->logger->info( sprintf( 'Inserting post %s', $postData['post_title'] ) );
		$postId = wp_insert_post( wp_slash( $postData ), true );

		if ( is_wp_error( $postId ) ) {
			$this->logError( 'post', $postData['post_title'], $postId );
			return $postId;
		}

		if ( ! empty( $post['is_sticky'] ) && 1 === (int) $post['is_sticky'] ) {
			stick_post( $postId );
		}

		return $postId;
	}

	/**
	 * Prepare post data for insertion.
	 *
	 * @param array $post
	 * @return array
	 */
	private function preparePostData( array $post ) {
		$parentId = intval( $post['post_parent'] );

		if ( $parentId ) {
			if ( isset( $this->itemMap[ $parentId ] ) ) {
				$parentId = $this->itemMap[ $parentId ];
			} else {
				$this->orphanedItems[ intval( $post['post_id'] ) ] = $parentId;
				$parentId = 0;
			}
		}

		$data = [
			'import_id'      => $post['post_id'],
			'post_author'    => get_current_user_id(),
			'post_date'      => $post['post_date'],
			'post_date_gmt'  => $post['post_date_gmt'],
			'post_content'   => $post['post_content'],
			'post_excerpt'   => $post['post_excerpt'],
			'post_title'     => $post['post_title'],
			'post_status'    => $post['status'],
			'post_name'      => $post['post_name'],
			'comment_status' => $post['comment_status'],
			'ping_status'    => $post['ping_status'],
			'guid'           => $post['guid'],
			'post_parent'    => $parentId,
			'menu_order'     => $post['menu_order'],
			'post_type'      => $post['post_type'],
			'post_password'  => $post['post_password'],
		];

		if ( $data['post_type'] === 'attachment' ) {
			$data['upload_date'] = $post['post_date'];

			if ( ! empty( $post['postmeta'] ) ) {
				foreach ( $post['postmeta'] as $meta ) {
					if ( $meta['key'] === '_wp_attached_file'
						&& preg_match( '%^[0-9]{4}/[0-9]{2}%', $meta['value'], $matches ) ) {
						$data['upload_date'] = $matches[0];
						break;
					}
				}
			}
		}

		return $data;
	}

	/**
	 * Finalize post.
	 *
	 * @param array $post
	 * @param integer $postId
	 * @return void
	 */
	private function finalizePost( array $post, int $postId ) {
		$originalId                   = intval( $post['post_id'] );
		$this->itemMap[ $originalId ] = $postId;

		if ( $post['post_type'] === 'nav_menu_item' ) {
			$this->menuItems[ $originalId ] = $postId;
		}

		if ( preg_match( self::ATTACHMENT_PATTERN, $post['post_content'] ) ) {
			$this->contentItems[ $originalId ] = $postId;
		}

		if ( isset( $post['is_sticky'] ) && (bool) $post['is_sticky'] ) {
			stick_post( $postId );
		}

		$this->assignTerms( $post, $postId );
		$this->processMetadata( $post, $postId, 'post' );
	}

	/**
	 * Assign terms to a post.
	 *
	 * @param array $post
	 * @param integer $postId
	 * @return void
	 */
	private function assignTerms( array $post, int $postId ) {
		$terms = $post['terms'] ?? [];

		if ( empty( $terms ) ) {
			return;
		}

		$taxonomyTerms = [];

		$this->logger->info( sprintf( 'Assigning terms to post %s', $postId ) );

		foreach ( $terms as $term ) {
			$taxonomy = $term['domain'] === 'tag' ? 'post_tag' : $term['domain'];
			/** @var \WP_Term */
			$termObj = get_term_by( 'slug', $term['slug'], $taxonomy );
			if ( ! $termObj || is_wp_error( $termObj ) ) {
				$this->logger->warning( sprintf( 'Term %s does not exist', $term['slug'] ) );
				continue;
			}
			$taxonomyTerms[ $taxonomy ][] = $termObj->term_id;
		}

		foreach ( $taxonomyTerms as $taxonomy => $termIds ) {
			$result = wp_set_post_terms( $postId, $termIds, $taxonomy );
			if ( ! $result || is_wp_error( $result ) ) {
				$this->logger->error( sprintf( 'Error assigning terms to post %s', $postId ) );
				is_wp_error( $result ) && $this->logger->error( $result->get_error_message() );
			}
		}

		$this->logger->info( sprintf( 'Finished assigning terms to post %s', $postId ) );
	}

	/**
	 * Create attachment.
	 *
	 * @param array $data
	 * @param string $url
	 * @return integer|\WP_Error
	 */
	private function createAttachment( array $data, string $url ) {
		$this->logger->info( sprintf( 'Processing attachment %s', $url ) );

		if ( preg_match( '|^/[\w\W]+$|', $url ) ) {
			$url = rtrim( $this->siteUrl, '/' ) . $url;
		}

		$upload = $this->fetchFile( $url, $data );

		if ( is_wp_error( $upload ) ) {
			$this->logger->error( sprintf( 'Error fetching remote file %s', $url ) );
			$this->logger->error( $upload->get_error_message() );
			return $upload;
		}

		$fileInfo = wp_check_filetype( $upload['file'] );

		if ( ! $fileInfo ) {
			$this->logger->warning( sprintf( 'Invalid file type %s', $upload['file'] ) );
			return new \WP_Error( 'attachment_processing_error', 'Invalid file type' );
		}

		$data['post_mime_type'] = $fileInfo['type'];
		$data['guid']           = $upload['url'];

		$this->logger->info( sprintf( 'Inserting attachment: %s', $data['guid'] ) );

		$this->loadMediaFunctions();

		$attachmentId = wp_insert_attachment( $data, $upload['file'] );
		$this->logger->info( sprintf( 'Attachment inserted: %s', $attachmentId ) );

		wp_update_attachment_metadata(
			$attachmentId,
			wp_generate_attachment_metadata( $attachmentId, $upload['file'] )
		);

		if ( preg_match( '!^image/!', $fileInfo['type'] ) ) {
			$this->mapImageUrls( $url, $upload['url'] );
		}

		$this->saveState();
		$this->logger->info( 'Attachment processing completed' );

		return $attachmentId;
	}

	/**
	 * Fetch a remote file.
	 *
	 * @param string $url
	 * @param array $data
	 * @return array|\WP_Error
	 */
	private function fetchFile( string $url, array $data ) {
		$this->logger->info( sprintf( 'Fetching remote file %s', $url ) );

		$filename = basename( $url );
		$upload   = wp_upload_bits( $filename, null, '', $data['upload_date'] );

		if ( $upload['error'] ) {
			return new \WP_Error( 'attachment_fetch_error', $upload['error'] );
		}

		$response = wp_safe_remote_get(
			$url,
			[
				'timeout'  => 300,
				'stream'   => true,
				'filename' => $upload['file'],
				'headers'  => [
					'User-Agent' => 'ThemeGrill-Starter-Templates/' . THEMEGRILL_STARTER_TEMPLATES_VERSION,
					'Origin'     => get_home_url(),
				],
			]
		);

		$validation = $this->validateResponse( $response, $upload['file'] );

		if ( is_wp_error( $validation ) ) {
			return $validation;
		}

		$this->mapUrls( $url, $upload['url'], $data, $response );

		$this->logger->info( sprintf( 'Fetched remote file %s', $url ) );
		return $upload;
	}

	/**
	 * Validate response.
	 *
	 * @param mixed $response
	 * @param string $file
	 * @return boolean|\WP_Error
	 */
	private function validateResponse( $response, string $file ) {
		$headers = wp_remote_retrieve_headers( $response );

		if ( ! $headers ) {
			FilesystemService::delete( $file, false, 'f' );
			return new \WP_Error( 'attachment_fetch_error', 'No headers returned from remote file' );
		}

		$statusCode = wp_remote_retrieve_response_code( $response );

		if ( $statusCode !== 200 ) {
			return new \WP_Error( 'attachment_fetch_error', 'Bad HTTP status code: ' . $statusCode );
		}

		$filesize = filesize( $file );

		if ( $filesize === 0 ) {
			FilesystemService::delete( $file, false, 'f' );
			return new \WP_Error( 'attachment_fetch_error', 'Zero size file' );
		}

		$maxSize = (int) apply_filters( 'import_attachment_size_limit', 0 );

		if ( ! empty( $maxSize ) && $filesize > $maxSize ) {
			FilesystemService::delete( $file, false, 'f' );
			return new \WP_Error( 'attachment_fetch_error', 'File is too big' );
		}

		return true;
	}

	/**
	 * Map urls.
	 *
	 * @param string $sourceUrl
	 * @param string $targetUrl
	 * @param array $data
	 * @param mixed $response
	 * @return void
	 */
	private function mapUrls( string $sourceUrl, string $targetUrl, array $data, $response ) {
		$this->urlMap[ $sourceUrl ]    = $targetUrl;
		$this->urlMap[ $data['guid'] ] = $targetUrl;

		$headers = wp_remote_retrieve_headers( $response );

		if ( isset( $headers['x-final-location'] ) && $headers['x-final-location'] !== $sourceUrl ) {
			$this->urlMap[ $headers['x-final-location'] ] = $targetUrl;
		}
	}

	private function mapImageUrls( string $sourceUrl, string $targetUrl ): void {
		$sourceParts = pathinfo( $sourceUrl );
		$sourceName  = basename( $sourceParts['basename'], ".{$sourceParts['extension']}" );

		$targetParts = pathinfo( $targetUrl );
		$targetName  = basename( $targetParts['basename'], ".{$targetParts['extension']}" );

		$this->urlMap[ $sourceParts['dirname'] . '/' . $sourceName ] =
			$targetParts['dirname'] . '/' . $targetName;
	}

	/**
	 * Post process import.
	 *
	 * @param array  $data
	 * @return boolean
	 */
	public function postprocessImport( array $data ) {
		$this->logger->info( 'Running postprocess import...' );

		$this->processTaxonomyOrphans();
		$this->processOrphans();
		$this->processThumbnails();
		$this->processContentUrls();
		$this->processMenuItems();

		if ( isset( $data['show_on_front'] ) ) {
			update_option( 'show_on_front', $data['show_on_front'] );
			$this->logger->info( 'Updated show_on_front option' );
		}
		if ( isset( $data['page_on_front'], $this->itemMap[ (int) $data['page_on_front'] ] ) ) {
			update_option( 'page_on_front', $this->itemMap[ (int) $data['page_on_front'] ] );
			$this->logger->info( 'Updated page_on_front option' );
		}
		if ( isset( $data['page_for_posts'], $this->itemMap[ (int) $data['page_for_posts'] ] ) ) {
			update_option( 'page_for_posts', $this->itemMap[ (int) $data['page_for_posts'] ] );
			$this->logger->info( 'Updated page_for_posts option' );
		}

		$this->logger->info( 'Postprocess import completed' );

		wp_cache_flush();

		return true;
	}

	private function processTaxonomyOrphans() {
		$this->logger->info( 'Processing taxonomy orphans...' );
		foreach ( $this->orphanedTaxonomyItems as $termId => $parentId ) {
			if ( ! isset( $this->taxonomyMap[ (int) $parentId ] ) ) {
				continue;
			}

			$this->logger->info( sprintf( 'Setting taxonomy %d parent to %d', $termId, $this->taxonomyMap[ (int) $parentId ] ) );

			$term = get_term( $termId );

			if ( ! $term || is_wp_error( $term ) ) {
				$this->logger->warning( sprintf( 'Failed to set taxonomy %d parent to %d', $termId, $this->taxonomyMap[ (int) $parentId ] ) );
				continue;
			}

			$update = wp_update_term(
				$termId,
				$term->taxonomy,
				[
					'parent' => $this->taxonomyMap[ (int) $parentId ],
				]
			);

			if ( is_wp_error( $update ) ) {
				$this->logger->warning( sprintf( 'Failed to set taxonomy %d parent to %d', $termId, $this->taxonomyMap[ (int) $parentId ] ) );
				continue;
			}

			$this->logger->info( sprintf( 'Set taxonomy %d parent to %d', $termId, $this->taxonomyMap[ (int) $parentId ] ) );
		}

		$this->logger->info( 'Finished processing taxonomy orphans' );
	}

	/**
	 * Process orphans.
	 *
	 * @return void
	 */
	private function processOrphans() {
		$this->logger->info( 'Processing orphan posts...' );

		foreach ( $this->orphanedItems as $itemId => $parentId ) {
			if ( ! isset( $this->itemMap[ $itemId ], $this->itemMap[ $parentId ] ) ) {
				$this->logger->warning( sprintf( 'Failed to set post %d parent to %d', $itemId, $parentId ) );
				continue;
			}

			$newItemId   = $this->itemMap[ $itemId ];
			$newParentId = $this->itemMap[ $parentId ];

			$this->logger->info( sprintf( 'Setting post %d parent to %d', $newItemId, $newParentId ) );

			$update = wp_update_post(
				[
					'ID'          => $newItemId,
					'post_parent' => $newParentId,
				]
			);

			if ( is_wp_error( $update ) ) {
				$this->logger->warning( sprintf( 'Failed to set post %d parent to %d', $newItemId, $newParentId ) );
				continue;
			}

			$this->logger->info( sprintf( 'Set post %d parent to %d', $newItemId, $newParentId ) );
		}

		$this->logger->info( 'Finished processing orphan posts' );
	}

	/**
	 * Process thumbnails.
	 *
	 * @return void
	 */
	private function processThumbnails() {
		$this->logger->info( 'Processing featured images...' );

		foreach ( $this->thumbnailMap as $itemId => $attachmentId ) {
			if ( ! isset( $this->itemMap[ $attachmentId ] ) ) {
				$this->logger->warning(
					sprintf( 'Failed to set featured image %d for post %d', $attachmentId, $itemId )
				);
				continue;
			}

			$newAttachmentId = $this->itemMap[ $attachmentId ];
			$this->logger->info( sprintf( 'Setting featured image %d for post %d', $newAttachmentId, $itemId ) );
			set_post_thumbnail( $itemId, $newAttachmentId );
		}

		$this->logger->info( 'Finished processing featured images' );
	}

	/**
	 * Process menu items.
	 *
	 * @return void
	 */
	private function processMenuItems() {
		$this->logger->info( 'Processing nav menu items...' );

		foreach ( $this->menuItems as $originalId => $menuItemId ) {
			$itemType = get_post_meta( $menuItemId, '_menu_item_type', true );
			$objectId = (int) get_post_meta( $menuItemId, '_menu_item_object_id', true );
			$parentId = (int) get_post_meta( $menuItemId, '_menu_item_menu_item_parent', true );

			if ( ! $objectId ) {
				continue;
			}

			$newObjectId = (int) match ( $itemType ) {
				'post_type' => $this->itemMap[ $objectId ] ?? 0,
				'taxonomy' => $this->taxonomyMap[ $objectId ] ?? 0,
				'custom' => $menuItemId,
				default => 0,
			};

			if ( ! $newObjectId ) {
				$this->logger->warning(
					sprintf( 'Failed to set nav menu item %d object id to %d', $menuItemId, $objectId )
				);
				continue;
			}

			update_post_meta( $menuItemId, '_menu_item_object_id', $newObjectId );

			if ( $parentId && isset( $this->itemMap[ $parentId ] ) ) {
				update_post_meta( $menuItemId, '_menu_item_menu_item_parent', $this->itemMap[ $parentId ] );
			}
		}

		$this->logger->info( 'Finished processing nav menu items' );
	}

	/**
	 * Process content urls.
	 *
	 * @return void
	 */
	private function processContentUrls() {
		$this->logger->info( 'Processing content attachment remap...' );

		foreach ( $this->contentItems as $originalId => $newItemId ) {
			$this->logger->info( sprintf( 'Processing content attachment remap for post %d', $newItemId ) );

			$post = get_post( $newItemId );

			if ( ! $post ) {
				continue;
			}

			$post->post_content = str_replace(
				array_keys( $this->urlMap ),
				array_values( $this->urlMap ),
				$post->post_content
			);

			$this->logger->info( sprintf( 'Updating post %d content', $newItemId ) );
			wp_update_post( $post );
		}

		$this->logger->info( 'Finished processing content attachment remap' );
	}

	/**
	 * Load media functions.
	 *
	 * @return void
	 */
	private function loadMediaFunctions() {
		if ( ! function_exists( 'wp_generate_attachment_metadata' ) ) {
			require_once ABSPATH . 'wp-admin/includes/image.php';
		}

		if ( ! function_exists( 'wp_read_video_metadata' ) ) {
			require_once ABSPATH . 'wp-admin/includes/media.php';
		}
	}

	/**
	 * Log error.
	 *
	 * @param string $type
	 * @param string $name
	 * @param \WP_Error $error
	 * @return void
	 */
	private function logError( string $type, string $name, \WP_Error $error ) {
		$this->logger->error( sprintf( 'Failed to insert %s %s', $type, $name ) );
		$this->logger->error( $error->get_error_message() );
	}
}
