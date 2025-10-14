<?php
/**
 * Filesystem service class for ThemeGrill Starter Templates.
 *
 * @package ThemeGrill\StarterTemplates\Services
 * @since   2.0.0
 */

namespace ThemeGrill\StarterTemplates\Services;

defined( 'ABSPATH' ) || exit;

/**
 * Filesystem utility class that provides a static interface to WordPress filesystem operations
 *
 * @method static string abspath() Get WordPress absolute path
 * @method static string wp_content_dir() Get wp-content directory path
 * @method static string wp_plugins_dir() Get plugins directory path
 * @method static string wp_themes_dir(?string $theme = null) Get themes directory path
 * @method static string wp_uploads_dir() Get uploads directory path
 * @method static string|false find_folder(string $folder) Find a folder in the filesystem
 * @method static array search_for_folder(string $folder) Search for a folder in the filesystem
 * @method static string|false gethchmod(string $file) Get file permissions
 * @method static int|false getnumchmodfromh(string $mode) Convert human-readable permissions to numeric
 * @method static string gethchmodfromh(string $mode) Convert between human-readable permission formats
 * @method static bool printf(string $path, mixed ...$args) Write formatted string to file
 * @method static string|false get_contents(string $file) Get contents of a file
 * @method static array|false get_contents_array(string $file) Get contents of a file as an array
 * @method static bool put_contents(string $file, string $contents, int $mode = false) Put contents to a file
 * @method static bool chdir(string $dir) Change current directory
 * @method static bool chgrp(string $file, mixed $group, bool $recursive = false) Change file/directory group
 * @method static bool chmod(string $file, mixed $mode = false, bool $recursive = false) Change file/directory permissions
 * @method static bool chown(string $file, mixed $owner, bool $recursive = false) Change file/directory owner
 * @method static string|false owner(string $file) Get file/directory owner
 * @method static string|false group(string $file) Get file/directory group
 * @method static bool copy(string $source, string $destination, bool $overwrite = false, mixed $mode = false) Copy a file
 * @method static bool move(string $source, string $destination, bool $overwrite = false) Move a file
 * @method static bool delete(string $file, bool $recursive = false, bool $type = false) Delete a file/directory
 * @method static bool exists(string $file) Check if file/directory exists
 * @method static bool is_file(string $file) Check if path is a file
 * @method static bool is_dir(string $path) Check if path is a directory
 * @method static bool is_readable(string $file) Check if file is readable
 * @method static bool is_writable(string $file) Check if file is writable
 * @method static int|false atime(string $file) Get file access time
 * @method static int|false mtime(string $file) Get file modification time
 * @method static int|false size(string $file) Get file size
 * @method static bool touch(string $file, int $time = 0, int $atime = 0) Set file access and modification times
 * @method static bool mkdir(string $path, mixed $chmod = false, mixed $chown = false, mixed $chgrp = false) Create a directory
 * @method static bool rmdir(string $path, bool $recursive = false) Remove a directory
 * @method static array|false dirlist(string $path, bool $includeHidden = true, bool $recursive = false) Get directory contents list
 * @method static string cwd() Get current working directory
 */
class FilesystemService {

	private static ?\WP_Filesystem_Direct $instance = null;

	/**
	 * Get the WordPress filesystem instance.
	 *
	 * @since 2.0.0
	 * @return \WP_Filesystem_Direct The filesystem instance.
	 */
	private static function getInstance(): \WP_Filesystem_Direct {
		if ( is_null( self::$instance ) ) {
			global $wp_filesystem;
			if ( ! $wp_filesystem || 'direct' !== $wp_filesystem->method ) {
				require_once ABSPATH . '/wp-admin/includes/file.php';
				$credentials = request_filesystem_credentials( '', 'direct' );
				WP_Filesystem( $credentials );
			}
			self::$instance = $wp_filesystem;
		}
		return self::$instance;
	}

	/**
	 * Handle static method calls by forwarding them to the WordPress filesystem instance.
	 *
	 * @since 2.0.0
	 * @param string $name Method name.
	 * @param array $arguments Method arguments.
	 * @return mixed The result of the filesystem method call.
	 * @throws \BadMethodCallException If the method doesn't exist.
	 */
	public static function __callStatic( string $name, array $arguments ): mixed {
		$instance = self::getInstance();
		if ( ! method_exists( $instance, $name ) ) {
			throw new \BadMethodCallException( esc_html( "Call to undefined method PDFPress\\Utils\\Filesystem::$name()" ) );
		}
		return $instance?->$name( ...$arguments );
	}
}
