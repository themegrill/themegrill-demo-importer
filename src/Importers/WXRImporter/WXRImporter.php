<?php

namespace ThemeGrill\Demo\Importer\Importers\WXRImporter;

use WP_Error;
use WP_Importer;
use XMLReader;

class WXRImporter extends WP_Importer {
	/**
	 * Maximum supported WXR version
	 */
	const MAX_WXR_VERSION = 1.2;

	/**
	 * Regular expression for checking if a post references an attachment
	 *
	 * Note: This is a quick, weak check just to exclude text-only posts. More
	 * vigorous checking is done later to verify.
	 */
	const REGEX_HAS_ATTACHMENT_REFS = '!
		(
			# Match anything with an image or attachment class
			class=[\'"].*?\b(wp-image-\d+|attachment-[\w\-]+)\b
		|
			# Match anything that looks like an upload URL
			src=[\'"][^\'"]*(
				[0-9]{4}/[0-9]{2}/[^\'"]+\.(jpg|jpeg|png|gif)
			|
				content/uploads[^\'"]+
			)[\'"]
		)!ix';

	/**
	 * Version of WXR we're importing.
	 *
	 * Defaults to 1.0 for compatibility. Typically overridden by a
	 * `<wp:wxr_version>` tag at the start of the file.
	 *
	 * @var string
	 */
	protected $version = '1.0';

	// information to import from WXR file
	protected $categories = array();
	protected $tags       = array();
	protected $base_url   = '';

	// TODO: REMOVE THESE
	protected $processed_terms      = array();
	protected $processed_posts      = array();
	protected $processed_menu_items = array();
	protected $menu_item_orphans    = array();
	protected $missing_menu_items   = array();

	// NEW STYLE
	protected $mapping            = array();
	protected $requires_remapping = array();
	protected $exists             = array();
	protected $user_slug_override = array();

	protected $url_remap       = array();
	protected $featured_images = array();

	/**
	 * Logger instance.
	 *
	 * @var WP_Importer_Logger
	 */
	protected $logger;

	/**
	 * Constructor
	 *
	 * @param array $options {
	 *     @var bool $prefill_existing_posts Should we prefill `post_exists` calls? (True prefills and uses more memory, false checks once per imported post and takes longer. Default is true.)
	 *     @var bool $prefill_existing_comments Should we prefill `comment_exists` calls? (True prefills and uses more memory, false checks once per imported comment and takes longer. Default is true.)
	 *     @var bool $prefill_existing_terms Should we prefill `term_exists` calls? (True prefills and uses more memory, false checks once per imported term and takes longer. Default is true.)
	 *     @var bool $update_attachment_guids Should attachment GUIDs be updated to the new URL? (True updates the GUID, which keeps compatibility with v1, false doesn't update, and allows deduplication and reimporting. Default is false.)
	 *     @var bool $fetch_attachments Fetch attachments from the remote server. (True fetches and creates attachment posts, false skips attachments. Default is false.)
	 *     @var bool $aggressive_url_search Should we search/replace for URLs aggressively? (True searches all posts' content for old URLs and replaces, false checks for `<img class="wp-image-*">` only. Default is false.)
	 *     @var int $default_author User ID to use if author is missing or invalid. (Default is null, which leaves posts unassigned.)
	 * }
	 */
	public function __construct( $options = array() ) {
		$empty_types = array(
			'post'    => array(),
			'comment' => array(),
			'term'    => array(),
			'user'    => array(),
		);

		$this->mapping            = $empty_types;
		$this->mapping['term_id'] = array();
		$this->requires_remapping = $empty_types;
		$this->exists             = $empty_types;

		$this->options = wp_parse_args(
			$options,
			array(
				'prefill_existing_posts'  => true,
				'prefill_existing_terms'  => true,
				'update_attachment_guids' => false,
				'fetch_attachments'       => true,
				'aggressive_url_search'   => false,
				'default_author'          => get_current_user_id(),
			)
		);
	}

	public function set_logger( $logger ) {
		$this->logger = $logger;
	}

	/**
	 * Get a stream reader for the file.
	 *
	 * @param string $file Path to the XML file.
	 * @return XMLReader|WP_Error Reader instance on success, error otherwise.
	 */
	protected function get_reader( $file ) {
		if ( ! class_exists( 'XMLReader' ) ) {
			$this->logger->critical( 'The XMLReader class is missing! Please install the XMLReader PHP extension on your server' );
			return new WP_Error( 'wxr_importer.cannot_parse', __( 'The XMLReader class is missing! Please install the XMLReader PHP extension on your server', 'wordpress-importer' ) );
		}

		$reader = new XMLReader();
		$status = $reader->open( $file );

		if ( ! $status ) {
			return new WP_Error( 'wxr_importer.cannot_parse', __( 'Could not open the file for parsing', 'themegrill-demo-importer' ) );
		}

		return $reader;
	}


	/**
	 * The main controller for the actual import stage.
	 *
	 * @param string $file Path to the WXR file for importing
	 */
	public function import( $file ) {
		add_filter( 'import_post_meta_key', array( $this, 'is_valid_meta_key' ) );
		add_filter( 'http_request_timeout', array( &$this, 'bump_request_timeout' ) );

		/*
		 * Elementor fix for excessive use of `wp_slash` after our update v3.0.2.
		 * Method in Elementor: \Elementor\Compatibility::register_actions
		 * https://wordpress.org/support/topic/version-2-6-0-breaks-every-elementor-theme/
		 *
		 * This can be removed after Elementor skips the functionality in above method if our plugin is in use.
		 */
		if ( method_exists( '\Elementor\Compatibility', 'on_wxr_importer_pre_process_post_meta' ) ) {
			remove_action( 'wxr_importer.pre_process.post_meta', array( 'Elementor\Compatibility', 'on_wxr_importer_pre_process_post_meta' ) );
		}

		$result = $this->import_start( $file );
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		// Let's run the actual importer now, woot
		$reader = $this->get_reader( $file );
		if ( is_wp_error( $reader ) ) {
			return $reader;
		}

		// Set the version to compatibility mode first
		$this->version = '1.0';

		// Reset other variables
		$this->base_url = '';
		// Start parsing!
		while ( $reader->read() ) {
			// Only deal with element opens
			if ( $reader->nodeType !== XMLReader::ELEMENT ) {
				continue;
			}

			switch ( $reader->name ) {
				case 'wp:wxr_version':
					// Upgrade to the correct version
					$this->version = $reader->readString();

					if ( version_compare( $this->version, self::MAX_WXR_VERSION, '>' ) ) {
						$this->logger->warning(
							sprintf(
								'This WXR file (version %1$s) is newer than the importer (version %2$s) and may not be supported. Please consider updating.',
								$this->version,
								self::MAX_WXR_VERSION
							)
						);
					}
					// Handled everything in this node, move on to the next
					$reader->next();
					break;

				case 'wp:base_site_url':
					$this->base_url = $reader->readString();

					// Handled everything in this node, move on to the next
					$reader->next();
					break;

				case 'item':
					$node   = $reader->expand();
					$parsed = $this->parse_post_node( $node );
					if ( is_wp_error( $parsed ) ) {
						$this->log_error( $parsed );

						// Skip the rest of this post
						$reader->next();
						break;
					}

					$this->process_post( $parsed['data'], $parsed['meta'], $parsed['comments'], $parsed['terms'] );
					// Handled everything in this node, move on to the next
					$reader->next();
					break;

				case 'wp:category':
					$node = $reader->expand();

					$parsed = $this->parse_term_node( $node, 'category' );

					if ( is_wp_error( $parsed ) ) {
						$this->log_error( $parsed );

						// Skip the rest of this post
						$reader->next();
						break;
					}

					$status = $this->process_term( $parsed['data'], $parsed['meta'] );

					// Handled everything in this node, move on to the next
					$reader->next();
					break;

				case 'wp:tag':
					$node = $reader->expand();

					$parsed = $this->parse_term_node( $node, 'tag' );

					if ( is_wp_error( $parsed ) ) {
						$this->log_error( $parsed );

						// Skip the rest of this post
						$reader->next();
						break;
					}

					$status = $this->process_term( $parsed['data'], $parsed['meta'] );

					// Handled everything in this node, move on to the next
					$reader->next();
					break;

				case 'wp:term':
					$node = $reader->expand();

					$parsed = $this->parse_term_node( $node );
					if ( is_wp_error( $parsed ) ) {
						$this->log_error( $parsed );

						// Skip the rest of this post
						$reader->next();
						break;
					}

					$status = $this->process_term( $parsed['data'], $parsed['meta'] );

					// Handled everything in this node, move on to the next
					$reader->next();
					break;

				default:
					// Skip this node, probably handled by something already
					break;
			}
		}

		// Now that we've done the main processing, do any required
		// post-processing and remapping.
		$this->logger->info(
			'Post-processing started',
			[ 'start_time' => true ]
		);
		$this->post_process();
		$this->logger->info(
			'Post-processing ended',
			[ 'end_time' => true ]
		);

		if ( $this->options['aggressive_url_search'] ) {
			$this->replace_attachment_urls_in_content();
		}
		$this->logger->info(
			'Remapping featured images',
			[ 'start_time' => true ]
		);
		$this->remap_featured_images();
		$this->logger->info(
			'Remapped featured images',
			[ 'end_time' => true ]
		);
		$this->import_end();
	}

	/**
	 * Log an error instance to the logger.
	 *
	 * @param WP_Error $error Error instance to log.
	 */
	protected function log_error( WP_Error $error ) {
		$this->logger->warning( $error->get_error_message() );

		// Log the data as debug info too
		$data = $error->get_error_data();
		if ( ! empty( $data ) ) {
			$this->logger->debug( var_export( $data, true ) );
		}
	}

	/**
	 * Parses the WXR file and prepares us for the task of processing parsed data
	 *
	 * @param string $file Path to the WXR file for importing
	 */
	protected function import_start( $file ) {

		// if ( ! is_file( $file ) ) {
		//  return new WP_Error( 'wxr_importer.file_missing', __( 'The file does not exist, please try again.', 'themegrill-demo-importer' ) );
		// }

		// Suspend bunches of stuff in WP core
		wp_defer_term_counting( true );
		wp_defer_comment_counting( true );
		wp_suspend_cache_invalidation( true );

		// Prefill exists calls if told to
		if ( $this->options['prefill_existing_posts'] ) {
			$this->prefill_existing_posts();
		}
		if ( $this->options['prefill_existing_terms'] ) {
			$this->prefill_existing_terms();
		}

		/**
		 * Begin the import.
		 *
		 * Fires before the import process has begun. If you need to suspend
		 * caching or heavy processing on hooks, do so here.
		 */
		do_action( 'import_start' );
	}

	/**
	 * Performs post-import cleanup of files and the cache
	 */
	protected function import_end() {
		// Re-enable stuff in core
		wp_suspend_cache_invalidation( false );
		wp_cache_flush();
		foreach ( get_taxonomies() as $tax ) {
			delete_option( "{$tax}_children" );
			_get_term_hierarchy( $tax );
		}

		wp_defer_term_counting( false );
		wp_defer_comment_counting( false );

		/**
		 * Complete the import.
		 *
		 * Fires after the import process has finished. If you need to update
		 * your cache or re-enable processing, do so here.
		 */
		do_action( 'import_end' );
	}

	/**
	 * Parse a post node into post data.
	 *
	 * @param DOMElement $node Parent node of post data (typically `item`).
	 * @return array|WP_Error Post data array on success, error otherwise.
	 */
	protected function parse_post_node( $node ) {
		$data     = array();
		$meta     = array();
		$comments = array();
		$terms    = array();
		foreach ( $node->childNodes as $child ) {

			// We only care about child elements
			if ( $child->nodeType !== XML_ELEMENT_NODE ) {
				continue;
			}

			switch ( $child->tagName ) {
				case 'wp:post_type':
					$data['post_type'] = $child->textContent;
					break;

				case 'title':
					$data['post_title'] = $child->textContent;
					break;

				case 'guid':
					$data['guid'] = $child->textContent;
					break;

				case 'dc:creator':
					$data['post_author'] = $child->textContent;
					break;

				case 'content:encoded':
					$data['post_content'] = $child->textContent;
					break;

				case 'excerpt:encoded':
					$data['post_excerpt'] = $child->textContent;
					break;

				case 'wp:post_id':
					$data['post_id'] = $child->textContent;
					break;

				case 'wp:post_date':
					$data['post_date'] = $child->textContent;
					break;

				case 'wp:post_date_gmt':
					$data['post_date_gmt'] = $child->textContent;
					break;

				case 'wp:comment_status':
					$data['comment_status'] = $child->textContent;
					break;

				case 'wp:ping_status':
					$data['ping_status'] = $child->textContent;
					break;

				case 'wp:post_name':
					$data['post_name'] = $child->textContent;
					break;

				case 'wp:status':
					$data['post_status'] = $child->textContent;

					if ( $data['post_status'] === 'auto-draft' ) {
						// Bail now
						return new WP_Error(
							'wxr_importer.post.cannot_import_draft',
							__( 'Cannot import auto-draft posts' ),
							$data
						);
					}
					break;

				case 'wp:post_parent':
					$data['post_parent'] = $child->textContent;
					break;

				case 'wp:menu_order':
					$data['menu_order'] = $child->textContent;
					break;

				case 'wp:post_password':
					$data['post_password'] = $child->textContent;
					break;

				case 'wp:is_sticky':
					$data['is_sticky'] = $child->textContent;
					break;

				case 'wp:attachment_url':
					$data['attachment_url'] = $child->textContent;
					break;

				case 'wp:postmeta':
					$meta_item = $this->parse_meta_node( $child );

					if ( ! empty( $meta_item ) ) {
						$meta[] = $meta_item;
					}
					break;

				case 'category':
					$term_item = $this->parse_category_node( $child );
					if ( ! empty( $term_item ) ) {
						$terms[] = $term_item;
					}
					break;
			}
		}

		return compact( 'data', 'meta', 'comments', 'terms' );
	}

	/**
	 * Create new posts based on import information
	 *
	 * Posts marked as having a parent which doesn't exist will become top level items.
	 * Doesn't create a new post if: the post type doesn't exist, the given post ID
	 * is already noted as imported or a post with the same title and date already exists.
	 * Note that new/updated terms, comments and meta are imported for the last of the above.
	 */
	protected function process_post( $data, $meta, $comments, $terms ) {
		/**
		 * Pre-process post data.
		 *
		 * @param array $data Post data. (Return empty to skip.)
		 * @param array $meta Meta data.
		 * @param array $comments Comments on the post.
		 * @param array $terms Terms on the post.
		 */
		$data = apply_filters( 'wxr_importer.pre_process.post', $data, $meta, $comments, $terms );
		if ( empty( $data ) ) {
			return false;
		}

		$original_id = isset( $data['post_id'] ) ? (int) $data['post_id'] : 0;
		$parent_id   = isset( $data['post_parent'] ) ? (int) $data['post_parent'] : 0;

		// Have we already processed this?
		if ( isset( $this->mapping['post'][ $original_id ] ) ) {
			return;
		}

		$this->logger->info( 'Importing post: ' . $data['post_title'], [ 'start_time' => true ] );

		$post_type_object = get_post_type_object( $data['post_type'] );

		// Is this type even valid?
		if ( ! $post_type_object ) {

			$this->logger->warning(
				sprintf(
					'Failed to import "%1$s": Invalid post type %2$s',
					$data['post_title'],
					$data['post_type']
				),
				[ 'end_time' => true ]
			);
			return false;
		}

		if ( 'nav_menu_item' !== $data['post_type'] ) {
			$post_exists = $this->post_exists( $data );

			if ( $post_exists ) {

				$this->logger->warning(
					sprintf(
						'%1$s "%2$s" already exists.',
						$post_type_object->labels->singular_name,
						$data['post_title']
					),
					[ 'end_time' => true ]
				);

				/**
				 * Post processing already imported.
				 *
				 * @param array $data Raw data imported for the post.
				 */
				do_action( 'wxr_importer.process_already_imported.post', $data );

				return false;
			}
		}

		// Map the parent post, or mark it as one we need to fix
		$requires_remapping = false;
		if ( $parent_id ) {
			if ( isset( $this->mapping['post'][ $parent_id ] ) ) {
				$data['post_parent'] = $this->mapping['post'][ $parent_id ];
			} else {
				$meta[]             = array(
					'key'   => '_wxr_import_parent',
					'value' => $parent_id,
				);
				$requires_remapping = true;

				$data['post_parent'] = 0;
			}
		}

		// Map the author, or mark it as one we need to fix
		$data['post_author'] = (int) get_current_user_id();

		// Does the post look like it contains attachment images?
		if ( preg_match( self::REGEX_HAS_ATTACHMENT_REFS, $data['post_content'] ) ) {
			$meta[]             = array(
				'key'   => '_wxr_import_has_attachment_refs',
				'value' => true,
			);
			$requires_remapping = true;
		}

		// Whitelist to just the keys we allow
		$postdata = array(
			'import_id' => $data['post_id'],
		);

		$allowed = array(
			'post_author'    => true,
			'post_date'      => true,
			'post_date_gmt'  => true,
			'post_content'   => true,
			'post_excerpt'   => true,
			'post_title'     => true,
			'post_status'    => true,
			'post_name'      => true,
			'comment_status' => true,
			'ping_status'    => true,
			'guid'           => true,
			'post_parent'    => true,
			'menu_order'     => true,
			'post_type'      => true,
			'post_password'  => true,
		);
		foreach ( $data as $key => $value ) {
			if ( ! isset( $allowed[ $key ] ) ) {
				continue;
			}

			$postdata[ $key ] = $data[ $key ];
		}

		$postdata = apply_filters( 'wp_import_post_data_processed', $postdata, $data );

		$postdata = apply_filters( 'themegrill_import_post_data_processed', $postdata, $this->mapping['term_id'] );

		$postdata = wp_slash( $postdata );

		if ( 'attachment' === $postdata['post_type'] ) {

			if ( ! $this->options['fetch_attachments'] ) {
				$this->logger->notice(
					sprintf(
						'Skipping attachment "%s", fetching attachments disabled',
						$data['post_title']
					),
					[ 'end_time' => true ]
				);
				/**
				 * Post processing skipped.
				 *
				 * @param array $data Raw data imported for the post.
				 * @param array $meta Raw meta data, already processed by {@see process_post_meta}.
				 */
				do_action( 'wxr_importer.process_skipped.post', $data, $meta );
				return false;
			}
			$remote_url = ! empty( $data['attachment_url'] ) ? $data['attachment_url'] : $data['guid'];
			$post_id    = $this->process_attachment( $postdata, $meta, $remote_url );
		} else {
			$post_id = wp_insert_post( $postdata, true );
			if ( $postdata['post_content'] && has_blocks( $postdata['post_content'] ) && has_block( 'everest-forms/form-selector', $postdata['post_content'] ) ) {
				$posts_with_evf   = get_option( 'themegrill_demo_importer_posts_with_evf', array() );
				$posts_with_evf[] = $post_id;
				update_option( 'themegrill_demo_importer_posts_with_evf', $posts_with_evf );
			}
			do_action( 'wp_import_insert_post', $post_id, $original_id, $postdata, $data );
		}

		if ( is_wp_error( $post_id ) ) {

			$this->logger->error(
				sprintf(
					'Failed to import "%1$s" (%2$s)',
					$data['post_title'],
					$post_type_object->labels->singular_name
				),
				[ 'end_time' => true ]
			);
			$this->logger->debug( $post_id->get_error_message() );

			/**
			 * Post processing failed.
			 *
			 * @param WP_Error $post_id Error object.
			 * @param array $data Raw data imported for the post.
			 * @param array $meta Raw meta data, already processed by {@see process_post_meta}.
			 * @param array $comments Raw comment data, already processed by {@see process_comments}.
			 * @param array $terms Raw term data, already processed.
			 */
			do_action( 'wxr_importer.process_failed.post', $post_id, $data, $meta, $comments, $terms );
			return false;
		}

		// Ensure stickiness is handled correctly too
		if ( $data['is_sticky'] === '1' ) {
			stick_post( $post_id );
		}

		$imported_posts   = get_option( 'themegrill_demo_importer_imported_posts', array() );
		$imported_posts[] = $post_id;
		$imported_posts   = array_unique( $imported_posts );
		update_option( 'themegrill_demo_importer_imported_posts', $imported_posts );

		// map pre-import ID to local ID
		$this->mapping['post'][ $original_id ] = (int) $post_id;
		if ( $requires_remapping ) {
			$this->requires_remapping['post'][ $post_id ] = true;
		}

		if ( 'nav_menu_item' !== $data['post_type'] ) {
			$this->mark_post_exists( $data, $post_id );
		}

		$this->logger->info(
			sprintf(
				'Imported "%1$s" (%2$s)',
				$data['post_title'],
				$post_type_object->labels->singular_name
			)
		);
		$this->logger->debug(
			sprintf(
				'Post %1$d remapped to %2$d',
				$original_id,
				$post_id
			)
		);

		// Handle the terms too
		$terms = apply_filters( 'wp_import_post_terms', $terms, $post_id, $data );

		if ( ! empty( $terms ) ) {
			$term_ids = array();
			foreach ( $terms as $term ) {
				$taxonomy = $term['taxonomy'];
				$key      = sha1( $taxonomy . ':' . $term['slug'] );

				if ( isset( $this->mapping['term'][ $key ] ) ) {
					$term_ids[ $taxonomy ][] = (int) $this->mapping['term'][ $key ];
				} elseif ( 'post_format' === $taxonomy ) {
						$term_exists = term_exists( $term['slug'], $taxonomy );
						$term_id     = is_array( $term_exists ) ? $term_exists['term_id'] : $term_exists;

					if ( empty( $term_id ) ) {
						$t = wp_insert_term( $term['name'], $taxonomy, array( 'slug' => $term['slug'] ) );
						if ( ! is_wp_error( $t ) ) {
							$term_id                       = $t['term_id'];
							$this->mapping['term'][ $key ] = $term_id;
						} else {
							$this->logger->warning(
								sprintf(
									esc_html( 'Failed to import term: %1$s - %2$s' ),
									esc_html( $taxonomy ),
									esc_html( $term['name'] )
								)
							);
							continue;
						}
					}

					if ( ! empty( $term_id ) ) {
						$term_ids[ $taxonomy ][] = intval( $term_id );
					}
				} else {
					$meta[]             = array(
						'key'   => '_wxr_import_term',
						'value' => $term,
					);
					$requires_remapping = true;
				}
			}

			foreach ( $term_ids as $tax => $ids ) {
				$tt_ids = wp_set_post_terms( $post_id, $ids, $tax );
				do_action( 'wp_import_set_post_terms', $tt_ids, $ids, $tax, $post_id, $data );
			}
		}

		$this->process_post_meta( $meta, $post_id, $data );

		if ( 'nav_menu_item' === $data['post_type'] ) {
			$this->process_menu_item_meta( $post_id, $data, $meta );
		}

		/**
		 * Post processing completed.
		 *
		 * @param int $post_id New post ID.
		 * @param array $data Raw data imported for the post.
		 * @param array $meta Raw meta data, already processed by {@see process_post_meta}.
		 * @param array $comments Raw comment data, already processed by {@see process_comments}.
		 * @param array $terms Raw term data, already processed.
		 */
		do_action( 'wxr_importer.processed.post', $post_id, $data, $meta, $comments, $terms );

		$this->logger->info(
			sprintf(
				'Complete import "%1$s" (%2$s)',
				$data['post_title'],
				$post_type_object->labels->singular_name
			),
			[ 'end_time' => true ]
		);
	}

	/**
	 * Attempt to create a new menu item from import data
	 *
	 * Fails for draft, orphaned menu items and those without an associated nav_menu
	 * or an invalid nav_menu term. If the post type or term object which the menu item
	 * represents doesn't exist then the menu item will not be imported (waits until the
	 * end of the import to retry again before discarding).
	 *
	 * @param array $item Menu item details from WXR file
	 */
	protected function process_menu_item_meta( $post_id, $data, $meta ) {
		$item_type          = get_post_meta( $post_id, '_menu_item_type', true );
		$original_object_id = get_post_meta( $post_id, '_menu_item_object_id', true );
		$object_id          = null;

		$this->logger->debug( sprintf( 'Processing menu item %s', $item_type ) );

		$requires_remapping = false;
		switch ( $item_type ) {
			case 'taxonomy':
				if ( isset( $this->mapping['term_id'][ $original_object_id ] ) ) {
					$object_id = $this->mapping['term_id'][ $original_object_id ];
				} else {
					add_post_meta( $post_id, '_wxr_import_menu_item', wp_slash( $original_object_id ) );
					$requires_remapping = true;
				}
				break;

			case 'post_type':
				if ( isset( $this->mapping['post'][ $original_object_id ] ) ) {
					$object_id = $this->mapping['post'][ $original_object_id ];
				} else {
					add_post_meta( $post_id, '_wxr_import_menu_item', wp_slash( $original_object_id ) );
					$requires_remapping = true;
				}
				break;

			case 'custom':
				// Custom refers to itself, wonderfully easy.
				$object_id = $post_id;
				break;

			default:
				// associated object is missing or not imported yet, we'll retry later
				$this->missing_menu_items[] = $item;
				$this->logger->debug( 'Unknown menu item type' );
				break;
		}

		if ( $requires_remapping ) {
			$this->requires_remapping['post'][ $post_id ] = true;
		}

		if ( empty( $object_id ) ) {
			// Nothing needed here.
			return;
		}

		$this->logger->debug( sprintf( 'Menu item %d mapped to %d', $original_object_id, $object_id ) );
		update_post_meta( $post_id, '_menu_item_object_id', wp_slash( $object_id ) );
	}

	/**
	 * If fetching attachments is enabled then attempt to create a new attachment
	 *
	 * @param array $post Attachment post details from WXR
	 * @param string $url URL to fetch attachment from
	 * @return int|WP_Error Post ID on success, WP_Error otherwise
	 */
	protected function process_attachment( $post, $meta, $remote_url ) {
		// try to use _wp_attached file for upload folder placement to ensure the same location as the export site
		// e.g. location is 2003/05/image.jpg but the attachment post_date is 2010/09, see media_handle_upload()
		$post['upload_date'] = $post['post_date'];
		foreach ( $meta as $meta_item ) {
			if ( $meta_item['key'] !== '_wp_attached_file' ) {
				continue;
			}

			if ( preg_match( '%^[0-9]{4}/[0-9]{2}%', $meta_item['value'], $matches ) ) {
				$post['upload_date'] = $matches[0];
			}
			break;
		}

		// if the URL is absolute, but does not contain address, then upload it assuming base_site_url
		if ( preg_match( '|^/[\w\W]+$|', $remote_url ) ) {
			$remote_url = rtrim( $this->base_url, '/' ) . $remote_url;
		}

		$this->logger->info(
			sprintf(
				'Fetching remote file for "%s"',
				$post['post_title']
			),
			[ 'fetch_start_time' => true ]
		);
		$upload = $this->fetch_remote_file( $remote_url, $post );
		if ( is_wp_error( $upload ) ) {
			$this->logger->warning(
				sprintf(
					'Failed to fetch remote file for "%s"',
					$post['post_title']
				),
				[ 'fetch_end_time' => true ]
			);
			return $upload;
		}

		$info = wp_check_filetype( $upload['file'] );
		if ( ! $info ) {
			$this->logger->warning(
				sprintf(
					'Invalid file type for "%s"',
					$post['post_title']
				),
				[ 'fetch_end_time' => true ]
			);
			return new WP_Error( 'attachment_processing_error', __( 'Invalid file type', 'themegrill-demo-importer' ) );
		}

		$this->logger->info(
			sprintf(
				'Fetched remote file for "%s"',
				$post['post_title']
			),
			[ 'fetch_end_time' => true ]
		);

		$post['post_mime_type'] = $info['type'];

		// WP really likes using the GUID for display. Allow updating it.
		// See https://core.trac.wordpress.org/ticket/33386
		if ( $this->options['update_attachment_guids'] ) {
			$post['guid'] = $upload['url'];
		}

		// as per wp-admin/includes/upload.php
		$post_id = wp_insert_attachment( $post, $upload['file'] );
		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}

		if ( ! function_exists( 'wp_generate_attachment_metadata' ) ) {
			include ABSPATH . 'wp-admin/includes/image.php';
		}

		if ( ! function_exists( 'wp_read_video_metadata' ) ) {
			require_once ABSPATH . 'wp-admin/includes/media.php';
		}

		$attachment_metadata = wp_generate_attachment_metadata( $post_id, $upload['file'] );
		wp_update_attachment_metadata( $post_id, $attachment_metadata );

		// Map this image URL later if we need to
		$this->url_remap[ $remote_url ] = $upload['url'];

		// If we have a HTTPS URL, ensure the HTTP URL gets replaced too
		if ( substr( $remote_url, 0, 8 ) === 'https://' ) {
			$insecure_url                     = 'http' . substr( $remote_url, 5 );
			$this->url_remap[ $insecure_url ] = $upload['url'];
		}

		if ( $this->options['aggressive_url_search'] ) {
			// remap resized image URLs, works by stripping the extension and remapping the URL stub.
			/*if ( preg_match( '!^image/!', $info['type'] ) ) {
				$parts = pathinfo( $remote_url );
				$name = basename( $parts['basename'], ".{$parts['extension']}" ); // PATHINFO_FILENAME in PHP 5.2

				$parts_new = pathinfo( $upload['url'] );
				$name_new = basename( $parts_new['basename'], ".{$parts_new['extension']}" );

				$this->url_remap[$parts['dirname'] . '/' . $name] = $parts_new['dirname'] . '/' . $name_new;
			}*/
		}

		return $post_id;
	}

	/**
	 * Parse a meta node into meta data.
	 *
	 * @param DOMElement $node Parent node of meta data (typically `wp:postmeta` or `wp:commentmeta`).
	 * @return array|null Meta data array on success, or null on error.
	 */
	protected function parse_meta_node( $node ) {
		foreach ( $node->childNodes as $child ) {
			// We only care about child elements

			if ( $child->nodeType !== XML_ELEMENT_NODE ) {
				continue;
			}

			switch ( $child->tagName ) {
				case 'wp:meta_key':
					$key = $child->textContent;
					break;

				case 'wp:meta_value':
					$value = $child->textContent;
					break;
			}
		}

		// if ( empty( $key ) || empty( $value ) ) {
		//  return null;
		// }

		return compact( 'key', 'value' );
	}

	/**
	 * Process and import post meta items.
	 *
	 * @param array $meta List of meta data arrays
	 * @param int $post_id Post to associate with
	 * @param array $post Post data
	 * @return int|WP_Error Number of meta items imported on success, error otherwise.
	 */
	protected function process_post_meta( $meta, $post_id, $post ) {
		if ( empty( $meta ) ) {
			return true;
		}

		foreach ( $meta as $meta_item ) {
			/**
			 * Pre-process post meta data.
			 *
			 * @param array $meta_item Meta data. (Return empty to skip.)
			 * @param int $post_id Post the meta is attached to.
			 */
			$meta_item = apply_filters( 'wxr_importer.pre_process.post_meta', $meta_item, $post_id );
			if ( empty( $meta_item ) ) {
				return false;
			}

			$key   = apply_filters( 'import_post_meta_key', $meta_item['key'], $post_id, $post );
			$value = false;

			if ( '_edit_last' === $key ) {
				$value = intval( $meta_item['value'] );
				if ( ! isset( $this->mapping['user'][ $value ] ) ) {
					// Skip!
					continue;
				}

				$value = $this->mapping['user'][ $value ];
			}

			if ( $key ) {
				// export gets meta straight from the DB so could have a serialized string
				if ( ! $value ) {
					$value = maybe_unserialize( $meta_item['value'] );
				}

				if ( '_elementor_data' === $key ) {
					if ( is_string( $value ) ) {
						$value = json_decode( $value, true );
					} else {
						$value = $value;
					}
					$this->replace_elementor_categories_ids( $value, $this->mapping['term_id'] );
				}

				add_post_meta( $post_id, wp_slash( $key ), wp_slash_strings_only( $value ) );

				do_action( 'import_post_meta', $post_id, $key, $value );

				// if the post has a featured image, take note of this in case of remap
				if ( '_thumbnail_id' === $key ) {
					$this->featured_images[ $post_id ] = (int) $value;
				}
			}
		}

		return true;
	}

	private function replace_elementor_categories_ids( &$elements, $category_mapping ) {
		foreach ( $elements as &$element ) {
			$fields_to_replace = array( 'categories_selected', 'authors_selected', 'tags_selected' );

			foreach ( $fields_to_replace as $field ) {
				if ( isset( $element['settings'][ $field ] ) ) {
					if ( 'authors_selected' === $field ) {
						$current_user_id                         = get_current_user_id();
						$element['settings']['authors_selected'] = array( $current_user_id );
					} else {
						$old_ids = $element['settings'][ $field ];
						$new_ids = array();

						foreach ( $old_ids as $old_id ) {
							if ( isset( $category_mapping[ $old_id ] ) ) {
								$new_ids[] = $category_mapping[ $old_id ];
							} else {
								$new_ids[] = $old_id;
							}
						}

						$element['settings'][ $field ] = $new_ids;
					}
				}
			}

			if ( isset( $element['elements'] ) && ! empty( $element['elements'] ) ) {
				$this->replace_elementor_categories_ids( $element['elements'], $category_mapping );
			}
		}
	}



	protected function parse_category_node( $node ) {
		$data = array(
			// Default taxonomy to "category", since this is a `<category>` tag
			'taxonomy' => 'category',
		);
		$meta = array();

		if ( $node->hasAttribute( 'domain' ) ) {
			$data['taxonomy'] = $node->getAttribute( 'domain' );
		}
		if ( $node->hasAttribute( 'nicename' ) ) {
			$data['slug'] = $node->getAttribute( 'nicename' );
		}

		$data['name'] = $node->textContent;

		if ( empty( $data['slug'] ) ) {
			return null;
		}

		// Just for extra compatibility
		if ( $data['taxonomy'] === 'tag' ) {
			$data['taxonomy'] = 'post_tag';
		}

		return $data;
	}


	protected function parse_term_node( $node, $type = 'term' ) {
		$data = array();
		$meta = array();

		$tag_name = array(
			'id'          => 'wp:term_id',
			'taxonomy'    => 'wp:term_taxonomy',
			'slug'        => 'wp:term_slug',
			'parent'      => 'wp:term_parent',
			'name'        => 'wp:term_name',
			'description' => 'wp:term_description',
		);
		$taxonomy = null;

		// Special casing!
		switch ( $type ) {
			case 'category':
				$tag_name['slug']        = 'wp:category_nicename';
				$tag_name['parent']      = 'wp:category_parent';
				$tag_name['name']        = 'wp:cat_name';
				$tag_name['description'] = 'wp:category_description';
				$tag_name['taxonomy']    = null;

				$data['taxonomy'] = 'category';
				break;

			case 'tag':
				$tag_name['slug']        = 'wp:tag_slug';
				$tag_name['parent']      = null;
				$tag_name['name']        = 'wp:tag_name';
				$tag_name['description'] = 'wp:tag_description';
				$tag_name['taxonomy']    = null;

				$data['taxonomy'] = 'post_tag';
				break;
		}

		foreach ( $node->childNodes as $child ) {
			// We only care about child elements
			if ( $child->nodeType !== XML_ELEMENT_NODE ) {
				continue;
			}

			$key = array_search( $child->tagName, $tag_name );
			if ( $key ) {
				$data[ $key ] = $child->textContent;
			} elseif ( $child->tagName === 'wp:termmeta' ) {
				$meta_item = $this->parse_meta_node( $child );
				if ( ! empty( $meta_item ) ) {
					$meta[] = $meta_item;
				}
			}
		}

		if ( empty( $data['taxonomy'] ) ) {
			return null;
		}

		// Compatibility with WXR 1.0
		if ( $data['taxonomy'] === 'tag' ) {
			$data['taxonomy'] = 'post_tag';
		}

		return compact( 'data', 'meta' );
	}

	protected function process_term( $data, $meta ) {
		/**
		 * Pre-process term data.
		 *
		 * @param array $data Term data. (Return empty to skip.)
		 * @param array $meta Meta data.
		 */
		$data = apply_filters( 'wxr_importer.pre_process.term', $data, $meta );
		if ( empty( $data ) ) {
			return false;
		}

		$original_id = isset( $data['id'] ) ? (int) $data['id'] : 0;
		$parent_id   = isset( $data['parent'] ) ? (int) $data['parent'] : 0;

		$mapping_key = sha1( $data['taxonomy'] . ':' . $data['slug'] );
		$existing    = $this->term_exists( $data );

		if ( $existing ) {

			/**
			 * Term processing already imported.
			 *
			 * @param array $data Raw data imported for the term.
			 */
			do_action( 'wxr_importer.process_already_imported.term', $data );

			$this->mapping['term'][ $mapping_key ]    = $existing;
			$this->mapping['term_id'][ $original_id ] = $existing;
			return false;
		}
		// WP really likes to repeat itself in export files
		if ( isset( $this->mapping['term'][ $mapping_key ] ) ) {
			return false;
		}

		$this->logger->info(
			sprintf(
				'Importing "%1$s" (%2$s)',
				$data['name'],
				$data['taxonomy']
			),
			[ 'start_time' => true ]
		);

		$termdata = array();
		$allowed  = array(
			'slug'        => true,
			'description' => true,
		);

		// Map the parent comment, or mark it as one we need to fix
		// TODO: add parent mapping and remapping
		/*$requires_remapping = false;
		if ( $parent_id ) {
			if ( isset( $this->mapping['term'][ $parent_id ] ) ) {
				$data['parent'] = $this->mapping['term'][ $parent_id ];
			} else {
				// Prepare for remapping later
				$meta[] = array( 'key' => '_wxr_import_parent', 'value' => $parent_id );
				$requires_remapping = true;

				// Wipe the parent for now
				$data['parent'] = 0;
			}
		}*/

		foreach ( $data as $key => $value ) {
			if ( ! isset( $allowed[ $key ] ) ) {
				continue;
			}

			$termdata[ $key ] = $data[ $key ];
		}

		$result = wp_insert_term( $data['name'], $data['taxonomy'], $termdata );

		if ( is_wp_error( $result ) ) {
			$this->logger->warning(
				sprintf(
					'Failed to import %1$s %2$s',
					$data['taxonomy'],
					$data['name']
				),
				[ 'end_time' => true ]
			);
			$this->logger->debug( $result->get_error_message() );
			do_action( 'wp_import_insert_term_failed', $result, $data );

			/**
			 * Term processing failed.
			 *
			 * @param WP_Error $result Error object.
			 * @param array $data Raw data imported for the term.
			 * @param array $meta Meta data supplied for the term.
			 */
			do_action( 'wxr_importer.process_failed.term', $result, $data, $meta );
			return false;
		}

		$term_id = $result['term_id'];

		$imported_terms   = get_option( 'themegrill_demo_importer_imported_terms', array() );
		$imported_terms[] = $term_id;
		$imported_terms   = array_unique( $imported_terms );
		update_option( 'themegrill_demo_importer_imported_terms', $imported_terms );

		$this->mapping['term'][ $mapping_key ]    = $term_id;
		$this->mapping['term_id'][ $original_id ] = $term_id;

		$this->logger->info(
			sprintf(
				'Imported "%1$s" (%2$s)',
				$data['name'],
				$data['taxonomy']
			)
		);
		$this->logger->debug(
			sprintf(
				'Term %1$d remapped to %2$d',
				$original_id,
				$term_id
			)
		);

		$this->process_term_meta( $meta, $term_id, $data );
		do_action( 'wp_import_insert_term', $term_id, $data );

		/**
		 * Term processing completed.
		 *
		 * @param int $term_id New term ID.
		 * @param array $data Raw data imported for the term.
		 */
		do_action( 'wxr_importer.processed.term', $term_id, $data );

		$this->logger->info(
			sprintf(
				'Complete import "%1$s" (%2$s)',
				$data['name'],
				$data['taxonomy']
			),
			[ 'end_time' => true ]
		);
	}

		/**
	 * Process and import term meta items.
	 *
	 * @param array $meta List of meta data arrays
	 * @param int $term_id Term to associate with
	 * @param array $term Term data
	 * @return int|WP_Error Number of meta items imported on success, error otherwise.
	 */
	protected function process_term_meta( $meta, $term_id, $term ) {
		if ( empty( $meta ) ) {
			return true;
		}

		foreach ( $meta as $meta_item ) {
			/**
			 * Pre-process term meta data.
			 *
			 * @param array $meta_item Meta data. (Return empty to skip.)
			 * @param int $term_id Term the meta is attached to.
			 */
			$meta_item = apply_filters( 'wxr_importer.pre_process.term_meta', $meta_item, $term_id );
			if ( empty( $meta_item ) ) {
				return false;
			}

			$key   = apply_filters( 'import_term_meta_key', $meta_item['key'], $term_id, $term );
			$value = false;

			if ( $key ) {
				// export gets meta straight from the DB so could have a serialized string
				if ( ! $value ) {
					$value = maybe_unserialize( $meta_item['value'] );
				}

				add_term_meta( $term_id, wp_slash( $key ), wp_slash_strings_only( $value ) );

				do_action( 'import_term_meta', $term_id, $key, $value );
			}
		}

		return true;
	}

	/**
	 * Attempt to download a remote file attachment
	 *
	 * @param string $url URL of item to fetch
	 * @param array $post Attachment details
	 * @return array|WP_Error Local file location details on success, WP_Error otherwise
	 */
	protected function fetch_remote_file( $url, $post ) {
		// extract the file name and extension from the url
		$file_name = basename( $url );

		// get placeholder file in the upload dir with a unique, sanitized filename
		$upload = wp_upload_bits( $file_name, 0, '', $post['upload_date'] );
		if ( $upload['error'] ) {
			return new WP_Error( 'upload_dir_error', $upload['error'] );
		}

		// fetch the remote url and write it to the placeholder file
		$response = wp_remote_get(
			$url,
			array(
				'stream'    => true,
				'filename'  => $upload['file'],
				'headers'   => array(
					'User-Agent' => 'ThemeGrill/1.0',
				),
				'sslverify' => true,
				'timeout'   => 30,
			)
		);

		// request failed
		if ( is_wp_error( $response ) ) {
			unlink( $upload['file'] );
			return $response;
		}

		$code = (int) wp_remote_retrieve_response_code( $response );

		// make sure the fetch was successful
		if ( $code !== 200 ) {
			unlink( $upload['file'] );
			return new WP_Error(
				'import_file_error',
				sprintf(
					__( 'Remote server returned %1$d %2$s for %3$s', 'themegrill-demo-importer' ),
					$code,
					get_status_header_desc( $code ),
					$url
				)
			);
		}

		$filesize = filesize( $upload['file'] );
		$headers  = wp_remote_retrieve_headers( $response );

		if ( isset( $headers['content-length'] ) && $filesize !== (int) $headers['content-length'] ) {
			unlink( $upload['file'] );
			return new WP_Error( 'import_file_error', __( 'Remote file is incorrect size', 'themegrill-demo-importer' ) );
		}

		if ( 0 === $filesize ) {
			unlink( $upload['file'] );
			return new WP_Error( 'import_file_error', __( 'Zero size file downloaded', 'themegrill-demo-importer' ) );
		}

		$max_size = (int) $this->max_attachment_size();
		if ( ! empty( $max_size ) && $filesize > $max_size ) {
			unlink( $upload['file'] );
			$message = sprintf( __( 'Remote file is too large, limit is %s', 'themegrill-demo-importer' ), size_format( $max_size ) );
			return new WP_Error( 'import_file_error', $message );
		}

		return $upload;
	}

	protected function post_process() {
		// Time to tackle any left-over bits
		if ( ! empty( $this->requires_remapping['post'] ) ) {
			$this->post_process_posts( $this->requires_remapping['post'] );
		}
	}

	protected function post_process_posts( $todo ) {
		foreach ( $todo as $post_id => $_ ) {
			$this->logger->debug(
				sprintf(
				// Note: title intentionally not used to skip extra processing
				// for when debug logging is off
					'Running post-processing for post %d',
					$post_id
				)
			);

			$data = array();

			$parent_id = get_post_meta( $post_id, '_wxr_import_parent', true );
			if ( ! empty( $parent_id ) ) {
				// Have we imported the parent now?
				if ( isset( $this->mapping['post'][ $parent_id ] ) ) {
					$data['post_parent'] = $this->mapping['post'][ $parent_id ];
				} else {
					$this->logger->warning(
						sprintf(
							'Could not find the post parent for "%1$s" (post #%2$d)',
							get_the_title( $post_id ),
							$post_id
						)
					);
					$this->logger->debug(
						sprintf(
							'Post %1$d was imported with parent %2$d, but could not be found',
							$post_id,
							$parent_id
						)
					);
				}
			}

			$author_slug = get_post_meta( $post_id, '_wxr_import_user_slug', true );
			if ( ! empty( $author_slug ) ) {
				$data['post_author'] = (int) get_current_user_id();
			}

			$has_attachments = get_post_meta( $post_id, '_wxr_import_has_attachment_refs', true );
			if ( ! empty( $has_attachments ) ) {
				$post    = get_post( $post_id );
				$content = $post->post_content;

				// Replace all the URLs we've got
				$new_content = str_replace( array_keys( $this->url_remap ), $this->url_remap, $content );
				if ( $new_content !== $content ) {
					$data['post_content'] = $new_content;
				}
			}

			if ( get_post_type( $post_id ) === 'nav_menu_item' ) {
				$this->post_process_menu_item( $post_id );
			}

			// Do we have updates to make?
			if ( empty( $data ) ) {
				$this->logger->debug(
					sprintf(
						'Post %d was marked for post-processing, but none was required.',
						$post_id
					)
				);
				continue;
			}

			// Run the update
			$data['ID'] = $post_id;
			$result     = wp_update_post( $data, true );
			if ( is_wp_error( $result ) ) {
				$this->logger->warning(
					sprintf(
						'Could not update "%1$s" (post #%2$d) with mapped data',
						get_the_title( $post_id ),
						$post_id
					)
				);
				$this->logger->debug( $result->get_error_message() );
				continue;
			}

			// Clear out our temporary meta keys
			delete_post_meta( $post_id, '_wxr_import_parent' );
			delete_post_meta( $post_id, '_wxr_import_user_slug' );
			delete_post_meta( $post_id, '_wxr_import_has_attachment_refs' );
		}
	}

	protected function post_process_menu_item( $post_id ) {
		$menu_object_id = get_post_meta( $post_id, '_wxr_import_menu_item', true );
		if ( empty( $menu_object_id ) ) {
			// No processing needed!
			return;
		}

		$menu_item_type = get_post_meta( $post_id, '_menu_item_type', true );
		switch ( $menu_item_type ) {
			case 'taxonomy':
				if ( isset( $this->mapping['term_id'][ $menu_object_id ] ) ) {
					$menu_object = $this->mapping['term_id'][ $menu_object_id ];
				}
				break;

			case 'post_type':
				if ( isset( $this->mapping['post'][ $menu_object_id ] ) ) {
					$menu_object = $this->mapping['post'][ $menu_object_id ];
				}
				break;

			default:
				// Cannot handle this.
				return;
		}

		if ( ! empty( $menu_object ) ) {
			update_post_meta( $post_id, '_menu_item_object_id', wp_slash( $menu_object ) );
		} else {
			$this->logger->warning(
				sprintf(
					'Could not find the menu object for "%1$s" (post #%2$d)',
					get_the_title( $post_id ),
					$post_id
				)
			);
			$this->logger->debug(
				sprintf(
					'Post %1$d was imported with object "%2$d" of type "%3$s", but could not be found',
					$post_id,
					$menu_object_id,
					$menu_item_type
				)
			);
		}

		delete_post_meta( $post_id, '_wxr_import_menu_item' );
	}


	/**
	 * Use stored mapping information to update old attachment URLs
	 */
	protected function replace_attachment_urls_in_content() {
		global $wpdb;
		// make sure we do the longest urls first, in case one is a substring of another
		uksort( $this->url_remap, array( $this, 'cmpr_strlen' ) );

		foreach ( $this->url_remap as $from_url => $to_url ) {
			// remap urls in post_content
			$query = $wpdb->prepare( "UPDATE {$wpdb->posts} SET post_content = REPLACE(post_content, %s, %s)", $from_url, $to_url );
			$wpdb->query( $query );

			// remap enclosure urls
			$query  = $wpdb->prepare( "UPDATE {$wpdb->postmeta} SET meta_value = REPLACE(meta_value, %s, %s) WHERE meta_key='enclosure'", $from_url, $to_url );
			$result = $wpdb->query( $query );
		}
	}

	/**
	 * Update _thumbnail_id meta to new, imported attachment IDs
	 */
	protected function remap_featured_images() {
		// cycle through posts that have a featured image
		foreach ( $this->featured_images as $post_id => $value ) {
			if ( isset( $this->mapping['post'][ $value ] ) ) {
				$new_id = $this->mapping['post'][ $value ];
				// only update if there's a difference
				if ( $new_id !== $value ) {
					update_post_meta( $post_id, '_thumbnail_id', $new_id );
				}
			}
		}
	}

	/**
	 * Decide if the given meta key maps to information we will want to import
	 *
	 * @param string $key The meta key to check
	 * @return string|bool The key if we do want to import, false if not
	 */
	public function is_valid_meta_key( $key ) {
		// skip attachment metadata since we'll regenerate it from scratch
		// skip _edit_lock as not relevant for import
		if ( in_array( $key, array( '_wp_attached_file', '_wp_attachment_metadata', '_edit_lock' ) ) ) {
			return false;
		}

		return $key;
	}

	/**
	 * Decide what the maximum file size for downloaded attachments is.
	 * Default is 0 (unlimited), can be filtered via import_attachment_size_limit
	 *
	 * @return int Maximum attachment file size to import
	 */
	protected function max_attachment_size() {
		return apply_filters( 'import_attachment_size_limit', 0 );
	}

	/**
	 * Added to http_request_timeout filter to force timeout at 60 seconds during import
	 *
	 * @access protected
	 * @return int 60
	 */
	function bump_request_timeout( $val ) {
		return 60;
	}

	// return the difference in length between two strings
	function cmpr_strlen( $a, $b ) {
		return strlen( $b ) - strlen( $a );
	}

	/**
	 * Prefill existing post data.
	 *
	 * This preloads all post names into memory, allowing us to avoid hitting the
	 * database when we need to check for existence. With larger imports, this
	 * becomes prohibitively slow to perform SELECT queries on each.
	 *
	 * By preloading all this data into memory, it's a constant-time lookup in
	 * PHP instead. However, this does use a lot more memory, so for sites doing
	 * small imports onto a large site, it may be a better tradeoff to use
	 * on-the-fly checking instead.
	 */
	protected function prefill_existing_posts() {
		global $wpdb;
		$posts = $wpdb->get_results( "SELECT ID, guid FROM {$wpdb->posts}" );

		foreach ( $posts as $item ) {
			$this->exists['post'][ $item->guid ] = $item->ID;
		}
	}

	/**
	 * Does the post exist?
	 *
	 * @param array $data Post data to check against.
	 * @return int|bool Existing post ID if it exists, false otherwise.
	 */
	protected function post_exists( $data ) {
		// Constant-time lookup if we prefilled
		$exists_key = $data['guid'];

		if ( $this->options['prefill_existing_posts'] ) {
			return isset( $this->exists['post'][ $exists_key ] ) ? $this->exists['post'][ $exists_key ] : false;
		}

		// No prefilling, but might have already handled it
		if ( isset( $this->exists['post'][ $exists_key ] ) ) {
			return $this->exists['post'][ $exists_key ];
		}

		// Still nothing, try post_exists, and cache it
		$exists                              = post_exists( $data['post_title'], $data['post_content'], $data['post_date'] );
		$this->exists['post'][ $exists_key ] = $exists;

		return $exists;
	}

	/**
	 * Mark the post as existing.
	 *
	 * @param array $data Post data to mark as existing.
	 * @param int $post_id Post ID.
	 */
	protected function mark_post_exists( $data, $post_id ) {
		$exists_key                          = $data['guid'];
		$this->exists['post'][ $exists_key ] = $post_id;
	}


	/**
	 * Prefill existing term data.
	 *
	 * @see self::prefill_existing_posts() for justification of why this exists.
	 */
	protected function prefill_existing_terms() {
		global $wpdb;
		$query  = "SELECT t.term_id, tt.taxonomy, t.slug FROM {$wpdb->terms} AS t";
		$query .= " JOIN {$wpdb->term_taxonomy} AS tt ON t.term_id = tt.term_id";
		$terms  = $wpdb->get_results( $query );

		foreach ( $terms as $item ) {
			$exists_key                          = sha1( $item->taxonomy . ':' . $item->slug );
			$this->exists['term'][ $exists_key ] = $item->term_id;
		}
	}

	/**
	 * Does the term exist?
	 *
	 * @param array $data Term data to check against.
	 * @return int|bool Existing term ID if it exists, false otherwise.
	 */
	protected function term_exists( $data ) {
		$exists_key = sha1( $data['taxonomy'] . ':' . $data['slug'] );

		// Constant-time lookup if we prefilled
		if ( $this->options['prefill_existing_terms'] ) {
			return isset( $this->exists['term'][ $exists_key ] ) ? $this->exists['term'][ $exists_key ] : false;
		}

		// No prefilling, but might have already handled it
		if ( isset( $this->exists['term'][ $exists_key ] ) ) {
			return $this->exists['term'][ $exists_key ];
		}

		// Still nothing, try term_exists, and cache it
		$exists = term_exists( $data['slug'], $data['taxonomy'] );
		if ( is_array( $exists ) ) {
			$exists = $exists['term_id'];
		}

		$this->exists['term'][ $exists_key ] = $exists;

		return $exists;
	}

	/**
	 * Mark the term as existing.
	 *
	 * @param array $data Term data to mark as existing.
	 * @param int $term_id Term ID.
	 */
	protected function mark_term_exists( $data, $term_id ) {
		$exists_key                          = sha1( $data['taxonomy'] . ':' . $data['slug'] );
		$this->exists['term'][ $exists_key ] = $term_id;
	}


	public function get_term_new_id( $nav_menu ) {
		return $this->mapping['term_id'][ $nav_menu ] ?? null;
	}

	public function get_mapping_data() {
		return $this->mapping;
	}
}
