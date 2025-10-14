<?php
/**
 * XML Parser class for WordPress export files (WXR format).
 *
 * @package ThemeGrill\StarterTemplates
 * @since   2.0.0
 */
namespace ThemeGrill\StarterTemplates;

use Sabre\Xml\Service;
use ThemeGrill\StarterTemplates\Services\FilesystemService;

defined( 'ABSPATH' ) || exit;

/**
 * XMLParser class.
 */
class XMLParser {

	private const WXR_NAMESPACE     = 'http://wordpress.org/export/1.2/';
	private const EXCERPT_NAMESPACE = 'http://wordpress.org/export/1.2/excerpt/';
	private const DC_NAMESPACE      = 'http://purl.org/dc/elements/1.1/';
	private const CONTENT_NAMESPACE = 'http://purl.org/rss/1.0/modules/content/';

	private ?string $wxrVersion  = null;
	private ?string $baseUrl     = null;
	private ?string $baseBlogUrl = null;
	private array $authors       = [];
	private array $posts         = [];
	private array $categories    = [];
	private array $tags          = [];
	private array $terms         = [];

	private Service $xmlService;

	/**
	 * Constructor.
	 *
	 * @since 2.0.0
	 */
	public function __construct() {
		$this->xmlService = new Service();
		$this->setupNamespaceMap();
	}

	/**
	 * Parse an XML file and extract WordPress export data.
	 *
	 * @since 2.0.0
	 * @param string $file The path to the XML file to parse.
	 * @return array|\WP_Error Parsed data array or WP_Error on failure.
	 */
	public function parse( string $file ) {
		$this->reset();

		$content = FilesystemService::get_contents( $file );

		try {
			$parsed = $this->xmlService->parse( $content );
			$this->processRssChannel( $parsed );
		} catch ( \Exception $e ) {
			return new \WP_Error( 'xml_parse_error', $e->getMessage() );
		}

		return [
			'authors'     => $this->authors,
			'posts'       => $this->posts,
			'categories'  => $this->categories,
			'tags'        => $this->tags,
			'terms'       => $this->terms,
			'baseUrl'     => $this->baseUrl,
			'baseBlogUrl' => $this->baseBlogUrl,
			'version'     => $this->wxrVersion,
		];
	}

	/**
	 * Setup namespace map.
	 *
	 * @return void
	 */
	private function setupNamespaceMap() {
		$this->xmlService->namespaceMap = [
			self::WXR_NAMESPACE     => 'wp',
			self::EXCERPT_NAMESPACE => 'excerpt',
			self::DC_NAMESPACE      => 'dc',
			self::CONTENT_NAMESPACE => 'content',
		];
	}

	/**
	 * Reset props.
	 *
	 * @return void
	 */
	/**
	 * Reset all parser properties to their initial state.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	private function reset() {
		$this->wxrVersion  = null;
		$this->baseUrl     = null;
		$this->baseBlogUrl = null;
		$this->authors     = [];
		$this->posts       = [];
		$this->categories  = [];
		$this->tags        = [];
		$this->terms       = [];
	}

	/**
	 * Process RSS channel.
	 *
	 * @param array $parsed
	 * @return void
	 */
	/**
	 * Process the RSS channel element from parsed XML.
	 *
	 * @since 2.0.0
	 * @param array $parsed The parsed XML data.
	 * @return void
	 * @throws \Exception If channel element is missing.
	 */
	private function processRssChannel( array $parsed ) {
		$channel = $this->findElement( $parsed, '{}channel' );
		if ( ! $channel ) {
			throw new \Exception( 'Missing channel element.' );
		}
		$this->parseChannelData( $channel['value'] );
	}

	/**
	 * Parse channel data.
	 *
	 * @param array $channelElements
	 * @return void
	 */
	/**
	 * Parse channel data elements.
	 *
	 * @since 2.0.0
	 * @param array $channelElements The channel elements to parse.
	 * @return void
	 */
	private function parseChannelData( array $channelElements ) {
		foreach ( $channelElements as $element ) {
			$name = $element['name'];

			switch ( $name ) {
				case '{' . self::WXR_NAMESPACE . '}wxr_version':
					$this->parseVersion( $element['value'] );
					break;
				case '{' . self::WXR_NAMESPACE . '}base_site_url':
					$this->baseUrl = trim( $element['value'] );
					break;
				case '{' . self::WXR_NAMESPACE . '}base_blog_url':
					$this->baseBlogUrl = trim( $element['value'] );
					break;
				case '{' . self::WXR_NAMESPACE . '}author':
					$this->parseAuthor( $element['value'] );
					break;
				case '{' . self::WXR_NAMESPACE . '}category':
					$this->parseCategory( $element['value'] );
					break;
				case '{' . self::WXR_NAMESPACE . '}tag':
					$this->parseTag( $element['value'] );
					break;
				case '{' . self::WXR_NAMESPACE . '}term':
					$this->parseTerm( $element['value'] );
					break;
				case '{}item':
					$this->parsePost( $element['value'] );
					break;
			}
		}

		// Set baseBlogUrl to baseUrl if not set
		if ( ! $this->baseBlogUrl && $this->baseUrl ) {
			$this->baseBlogUrl = $this->baseUrl;
		}
	}

	/**
	 * Parse version.
	 *
	 * @param string $version
	 * @return void
	 */
	/**
	 * Parse and validate the WXR version.
	 *
	 * @since 2.0.0
	 * @param string $version The version string to parse.
	 * @return void
	 * @throws \Exception If version is invalid.
	 */
	private function parseVersion( string $version ) {
		$this->wxrVersion = trim( $version );

		if ( empty( $this->wxrVersion ) ) {
			throw new \Exception( 'Missing WXR version.' );
		}

		if ( ! preg_match( '/^\d+\.\d+$/', $this->wxrVersion ) ) {
			throw new \Exception( esc_html( "Invalid WXR version format: {$this->wxrVersion}" ) );
		}
	}

	/**
	 * Parse author.
	 *
	 * @param array $elements
	 * @return void
	 */
	/**
	 * Parse author data from XML elements.
	 *
	 * @since 2.0.0
	 * @param array $elements The author elements to parse.
	 * @return void
	 */
	private function parseAuthor( array $elements ) {
		$author = [];

		foreach ( $elements as $element ) {
			$name            = str_replace( '{' . self::WXR_NAMESPACE . '}', '', $element['name'] );
			$author[ $name ] = $element['value'];
		}

		if ( isset( $author['author_login'] ) ) {
			$this->authors[ $author['author_login'] ] = [
				'author_id'           => (int) ( $author['author_id'] ?? 0 ),
				'author_login'        => $author['author_login'],
				'author_email'        => $author['author_email'] ?? '',
				'author_display_name' => $author['author_display_name'] ?? '',
				'author_first_name'   => $author['author_first_name'] ?? '',
				'author_last_name'    => $author['author_last_name'] ?? '',
			];
		}
	}

	/**
	 * Parse category.
	 *
	 * @param array $elements
	 * @return void
	 */
	/**
	 * Parse category data from XML elements.
	 *
	 * @since 2.0.0
	 * @param array $elements The category elements to parse.
	 * @return void
	 */
	private function parseCategory( array $elements ) {
		$category = [
			'term_id'              => 0,
			'category_nicename'    => '',
			'category_parent'      => '',
			'cat_name'             => '',
			'category_description' => '',
			'termmeta'             => [],
		];

		foreach ( $elements as $element ) {
			$name = str_replace( '{' . self::WXR_NAMESPACE . '}', '', $element['name'] );

			if ( $name === 'termmeta' ) {
				$category['termmeta'][] = $this->parseTermMeta( $element['value'] );
			} else {
				$category[ $name ] = $element['value'];
			}
		}

		$category['term_id'] = (int) $category['term_id'];
		$this->categories[]  = $category;
	}

	/**
	 * Parse tag.
	 *
	 * @param array $elements
	 * @return void
	 */
	/**
	 * Parse tag data from XML elements.
	 *
	 * @since 2.0.0
	 * @param array $elements The tag elements to parse.
	 * @return void
	 */
	private function parseTag( array $elements ) {
		$tag = [
			'term_id'         => 0,
			'tag_slug'        => '',
			'tag_name'        => '',
			'tag_description' => '',
			'termmeta'        => [],
		];

		foreach ( $elements as $element ) {
			$name = str_replace( '{' . self::WXR_NAMESPACE . '}', '', $element['name'] );

			if ( $name === 'termmeta' ) {
				$tag['termmeta'][] = $this->parseTermMeta( $element['value'] );
			} else {
				$tag[ $name ] = $element['value'];
			}
		}

		$tag['term_id'] = (int) $tag['term_id'];
		$this->tags[]   = $tag;
	}

	/**
	 * Parse term.
	 *
	 * @param array $elements
	 * @return void
	 */
	/**
	 * Parse term data from XML elements.
	 *
	 * @since 2.0.0
	 * @param array $elements The term elements to parse.
	 * @return void
	 */
	private function parseTerm( array $elements ) {
		$term = [
			'term_id'          => 0,
			'term_taxonomy'    => '',
			'slug'             => '',
			'term_parent'      => '',
			'term_name'        => '',
			'term_description' => '',
			'termmeta'         => [],
		];

		foreach ( $elements as $element ) {
			$name = str_replace( '{' . self::WXR_NAMESPACE . '}', '', $element['name'] );

			if ( $name === 'termmeta' ) {
				$term['termmeta'][] = $this->parseTermMeta( $element['value'] );
			} elseif ( $name === 'term_slug' ) {
				$term['slug'] = $element['value'];
			} else {
				$term[ $name ] = $element['value'];
			}
		}

		$term['term_id'] = (int) $term['term_id'];
		$this->terms[]   = $term;
	}

	/**
	 * Parse term meta.
	 *
	 * @param array $elements
	 * @return array
	 */
	/**
	 * Parse term meta data from XML elements.
	 *
	 * @since 2.0.0
	 * @param array $elements The term meta elements to parse.
	 * @return array The parsed term meta data.
	 */
	private function parseTermMeta( array $elements ) {
		$meta = [
			'key'   => '',
			'value' => '',
		];

		foreach ( $elements as $element ) {
			$name = str_replace( '{' . self::WXR_NAMESPACE . '}', '', $element['name'] );
			if ( $name === 'meta_key' ) {
				$meta['key'] = $element['value'];
			} elseif ( $name === 'meta_value' ) {
				$meta['value'] = $element['value'];
			}
		}

		return $meta;
	}

	/**
	 * Parse post.
	 *
	 * @param array $elements
	 * @return void
	 */
	/**
	 * Parse post data from XML elements.
	 *
	 * @since 2.0.0
	 * @param array $elements The post elements to parse.
	 * @return void
	 */
	private function parsePost( array $elements ) {
		$post = [
			'post_title'   => '',
			'guid'         => '',
			'post_content' => '',
			'post_excerpt' => '',
			'post_author'  => '',
			'terms'        => [],
			'postmeta'     => [],
			'comments'     => [],
		];

		foreach ( $elements as $element ) {
			$name = $element['name'];

			switch ( $name ) {
				case '{}title':
					$post['post_title'] = $element['value'];
					break;
				case '{}guid':
					$post['guid'] = $element['value'];
					break;
				case '{' . self::CONTENT_NAMESPACE . '}encoded':
					$post['post_content'] = $element['value'];
					break;
				case '{' . self::EXCERPT_NAMESPACE . '}encoded':
					$post['post_excerpt'] = $element['value'];
					break;
				case '{' . self::DC_NAMESPACE . '}creator':
					$post['post_author'] = $element['value'];
					break;
				case '{}category':
					$post['terms'][] = $this->parsePostTerm( $element );
					break;
					break;
				case '{' . self::WXR_NAMESPACE . '}postmeta':
					$post['postmeta'][] = $this->parsePostMeta( $element['value'] );
					break;
				case '{' . self::WXR_NAMESPACE . '}comment':
					$post['comments'][] = $this->parseComment( $element['value'] );
					break;
				default:
					// Handle other wp: namespace elements
					if ( strpos( $name, '{' . self::WXR_NAMESPACE . '}' ) === 0 ) {
						$fieldName          = str_replace( '{' . self::WXR_NAMESPACE . '}', '', $name );
						$post[ $fieldName ] = $element['value'];
					}
					break;
			}
		}

		$post['post_id']     = (int) ( $post['post_id'] ?? 0 );
		$post['post_parent'] = (int) ( $post['post_parent'] ?? 0 );
		$post['menu_order']  = (int) ( $post['menu_order'] ?? 0 );
		$post['is_sticky']   = (int) ( $post['is_sticky'] ?? 0 );

		$this->posts[] = $post;
	}

	/**
	 * Parse post category.
	 *
	 * @param array $element
	 * @return array
	 */
	/**
	 * Parse post category data from XML element.
	 *
	 * @since 2.0.0
	 * @param array $element The post category element to parse.
	 * @return array The parsed post category data.
	 */
	private function parsePostTerm( array $element ) {
		$term = [
			'name'   => $element['value'],
			'slug'   => '',
			'domain' => '',
		];

		if ( isset( $element['attributes'] ) ) {
			$term['slug']   = $element['attributes']['nicename'] ?? '';
			$term['domain'] = $element['attributes']['domain'] ?? '';
		}

		return $term;
	}

	/**
	 * Parse post meta.
	 *
	 * @param array $elements
	 * @return array
	 */
	/**
	 * Parse post meta data from XML elements.
	 *
	 * @since 2.0.0
	 * @param array $elements The post meta elements to parse.
	 * @return array The parsed post meta data.
	 */
	private function parsePostMeta( array $elements ) {
		$meta = [
			'key'   => '',
			'value' => '',
		];

		foreach ( $elements as $element ) {
			$name = str_replace( '{' . self::WXR_NAMESPACE . '}', '', $element['name'] );
			if ( $name === 'meta_key' ) {
				$meta['key'] = $element['value'];
			} elseif ( $name === 'meta_value' ) {
				$meta['value'] = $element['value'];
			}
		}

		return $meta;
	}

	/**
	 * Parse comment.
	 *
	 * @param array $elements
	 * @return array
	 */
	/**
	 * Parse comment data from XML elements.
	 *
	 * @since 2.0.0
	 * @param array $elements The comment elements to parse.
	 * @return array The parsed comment data.
	 */
	private function parseComment( array $elements ) {
		$comment = [
			'comment_id'           => 0,
			'comment_author'       => '',
			'comment_author_email' => '',
			'comment_author_IP'    => '',
			'comment_author_url'   => '',
			'comment_date'         => '',
			'comment_date_gmt'     => '',
			'comment_content'      => '',
			'comment_approved'     => '',
			'comment_type'         => '',
			'comment_parent'       => '',
			'comment_user_id'      => 0,
			'commentmeta'          => [],
		];

		foreach ( $elements as $element ) {
			$name = str_replace( '{' . self::WXR_NAMESPACE . '}', '', $element['name'] );

			if ( $name === 'commentmeta' ) {
				$comment['commentmeta'][] = $this->parseCommentMeta( $element['value'] );
			} else {
				$comment[ $name ] = $element['value'];
			}
		}

		$comment['comment_id']      = (int) $comment['comment_id'];
		$comment['comment_user_id'] = (int) $comment['comment_user_id'];

		return $comment;
	}

	/**
	 * Parse comment meta.
	 *
	 * @param array $elements
	 * @return array
	 */
	/**
	 * Parse comment meta data from XML elements.
	 *
	 * @since 2.0.0
	 * @param array $elements The comment meta elements to parse.
	 * @return array The parsed comment meta data.
	 */
	private function parseCommentMeta( array $elements ) {
		$meta = [
			'key'   => '',
			'value' => '',
		];

		foreach ( $elements as $element ) {
			$name = str_replace( '{' . self::WXR_NAMESPACE . '}', '', $element['name'] );
			if ( $name === 'meta_key' ) {
				$meta['key'] = $element['value'];
			} elseif ( $name === 'meta_value' ) {
				$meta['value'] = $element['value'];
			}
		}

		return $meta;
	}

	/**
	 * Find element by name.
	 *
	 * @param array $elements
	 * @param string $name
	 * @return array|null
	 */
	/**
	 * Find an element by name in the elements array.
	 *
	 * @since 2.0.0
	 * @param array $elements The elements array to search.
	 * @param string $name The element name to find.
	 * @return array|null The found element or null if not found.
	 */
	private function findElement( array $elements, string $name ) {
		foreach ( $elements as $element ) {
			if ( $element['name'] === $name ) {
				return $element;
			}
		}
		return null;
	}
}
