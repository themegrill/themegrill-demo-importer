<?php
/**
 * Theme service class for ThemeGrill Starter Templates.
 *
 * @package ThemeGrill\StarterTemplates\Services
 * @since   2.0.0
 */

namespace ThemeGrill\StarterTemplates\Services;

defined( 'ABSPATH' ) || exit;

class ThemeService {

	const ALLOWED_THEMES = [
		'spacious',
		'colormag',
		'flash',
		'estore',
		'ample',
		'accelerate',
		'colornews',
		'foodhunt',
		'fitclub',
		'radiate',
		'freedom',
		'himalayas',
		'esteem',
		'envince',
		'suffice',
		'explore',
		'masonic',
		'cenote',
		'zakra',
		'webshop',
		'elearning',
		'online-education',
		'skincare',
		'estory',
		'gizmo',
		'libreria',
		'vastra',
		'blissful',
		'kirana',
		'ornatedecor',
	];

	/**
	 * Check if the current theme is a core ThemeGrill theme.
	 *
	 * @since 2.0.0
	 * @return bool True if current theme is core.
	 */
	public static function isCoreTheme(): bool {
		$currentTemplate = get_template();
		return self::isThemeCore( $currentTemplate );
	}

	/**
	 * Check if a specific theme is a core ThemeGrill theme.
	 *
	 * @since 2.0.0
	 * @param string $templateName The theme template name.
	 * @return bool True if theme is core.
	 */
	public static function isThemeCore( string $templateName ): bool {
		$coreThemes = self::getAllAllowedThemes();
		return in_array( $templateName, $coreThemes, true ) || ( str_ends_with( $templateName, '-pro' ) ? in_array( $templateName . '-pro', $coreThemes, true ) : false );
	}

	/**
	 * Get all allowed themes.
	 *
	 * @since 2.0.0
	 * @return array Array of allowed theme names.
	 */
	public static function getAllAllowedThemes(): array {
		return self::ALLOWED_THEMES;
	}

	/**
	 * Check if a theme is allowed.
	 *
	 * @since 2.0.0
	 * @param string $templateName The theme template name.
	 * @return bool True if theme is allowed.
	 */
	public static function isAllowedTheme( string $templateName ): bool {
		return in_array( $templateName, self::ALLOWED_THEMES, true );
	}
}
