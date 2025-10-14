<?php

namespace ThemeGrill\StarterTemplates\Services;

class ImageService {

	const EXTENSIONS = [
		'jpg',
		'jpeg',
		'png',
		'gif',
		'webp',
		'svg',
		'bmp',
		'tiff',
		'tif',
		'ico',
		'avif',
		'heic',
		'heif',
	];

	private const UPLOADS_PATH_INDICATORS = [
		'/uploads/',
		'/wp-content/uploads/',
		'/content/uploads/',
	];

	public static function extractImageUrls( string $markup ): array {
		if ( empty( $markup ) ) {
			return [];
		}

		$urls              = [];
		$extensionsPattern = implode( '|', array_map( 'preg_quote', self::EXTENSIONS ) );

		if ( preg_match_all( '/<img[^>]+(?:src|data-src)=["\']([^"\']+)["\'][^>]*>/i', $markup, $matches ) ) {
			$urls = array_merge( $urls, $matches[1] );
		}

		if ( preg_match_all( '/srcset=["\']([^"\']+)["\']/', $markup, $matches ) ) {
			foreach ( $matches[1] as $srcset ) {
				$srcsetUrls = preg_split( '/,\s*/', $srcset );
				foreach ( $srcsetUrls as $srcsetUrl ) {
					$url = preg_replace( '/\s+\d+[wx]?\s*$/', '', trim( $srcsetUrl ) );
					if ( $url ) {
						$urls[] = $url;
					}
				}
			}
		}

		if ( preg_match_all( '/background(?:-image)?:\s*url\(["\']?([^"\')\s]+)["\']?\)/i', $markup, $matches ) ) {
			$urls = array_merge( $urls, $matches[1] );
		}

		$regex = '/https?:\/\/[^\s<>"{}|\\^`\[\]]+\.(' . $extensionsPattern . ')(?:\?[^\s<>"{}|\\^`\[\]]*)?(?:#[^\s<>"{}|\\^`\[\]]*)?/i';
		if ( preg_match_all( $regex, $markup, $matches ) ) {
			$urls = array_merge( $urls, $matches[0] );
		}

		return array_values(
			array_unique(
				array_filter(
					array_map(
						fn( $url ) => self::cleanImageUrl( $url ),
						$urls
					)
				)
			)
		);
	}

	public static function cleanImageUrl( string $url ): ?string {
		$url = html_entity_decode( $url, ENT_QUOTES | ENT_HTML5, 'UTF-8' );

		$url = rtrim( $url, "\\ \t\n\r\0\x0B" );

		if ( str_starts_with( $url, '//' ) ) {
			$url = 'https:' . $url;
		}

		if ( empty( $url ) || strlen( $url ) > 2048 ) {
			return null;
		}

		if ( ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
			return null;
		}

		return $url;
	}

	public static function remapHost( string $url ) {
		if ( ! strpos( $url, '/uploads/' ) ) {
			return $url;
		}
		$urlParts = wp_parse_url( $url );

		if (
			! ( false !== $urlParts &&
				isset( $urlParts['host'], $urlParts['path'] ) &&
				! empty( $urlParts['host'] ) &&
				! empty( $urlParts['path'] ) )
		) {
			return $url;
		}

		return self::buildRemappedUrl( $urlParts ) ?? $url;
	}

	private static function buildRemappedUrl( array $urlParts ) {
		$uploadsDir = wp_get_upload_dir();

		if ( empty( $uploadsDir['baseurl'] ) ) {
			return null;
		}

		$pathSegments = self::extractRelevantPathSegments( $urlParts['path'] );

		if ( empty( $pathSegments ) ) {
			return null;
		}

		$newPath = '/' . implode( '/', $pathSegments );
		$newUrl  = rtrim( $uploadsDir['baseurl'], '/' ) . $newPath;

		if ( ! empty( $urlParts['query'] ) ) {
			$newUrl .= '?' . $urlParts['query'];
		}

		if ( ! empty( $urlParts['fragment'] ) ) {
			$newUrl .= '#' . $urlParts['fragment'];
		}

		return esc_url( $newUrl );
	}

	private static function extractRelevantPathSegments( string $path ): array {
		$uploadsPos = self::findUploadsPosition( $path );

		if ( false !== $uploadsPos ) {
			return [];
		}

		$pathSegments = array_filter( explode( '/', $path ) );
		$pathSegments = array_values( $pathSegments );

		$uploadsIndex = array_search( 'uploads', $pathSegments, true );

		if ( false === $uploadsIndex ) {
			return [];
		}

		return array_slice( $pathSegments, $uploadsIndex + 1 );
	}

	private static function findUploadsPosition( string $path ): int|false {
		foreach ( self::UPLOADS_PATH_INDICATORS as $indicator ) {
			$pos = strpos( $path, $indicator );
			if ( false !== $pos ) {
				return $pos;
			}
		}

		return false;
	}
}
