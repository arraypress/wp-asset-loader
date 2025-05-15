<?php
/**
 * WordPress Asset Loader
 *
 * @package     ArrayPress\WP\AssetLoader
 * @copyright   Copyright (c) 2025, ArrayPress Limited
 * @license     GPL2+
 * @version     1.0.0
 * @author      David Sherlock
 */

declare( strict_types=1 );

namespace ArrayPress\WP;

defined( 'ABSPATH' ) || exit;

/**
 * Class AssetLoader
 *
 * Simple utility for loading WordPress assets from Composer libraries.
 * Supports namespace-based asset registration and automatic path resolution.
 */
class AssetLoader {

	/**
	 * Registered asset paths by namespace
	 *
	 * @var array
	 */
	private static array $resolvers = [];

	/**
	 * Register an asset path for a namespace
	 *
	 * @param string      $namespace   The namespace for the library
	 * @param string|null $assets_path Optional. Path to the assets folder. Defaults to /assets relative to caller.
	 *
	 * @return void
	 */
	public static function register( string $namespace, ?string $assets_path = null ): void {
		if ( $assets_path === null ) {
			// Get the actual calling namespace to find the right file
			$caller_namespace = self::get_caller_namespace();

			// Find the file that contains this namespace
			$backtrace   = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, 6 );
			$caller_file = null;

			foreach ( $backtrace as $trace ) {
				// Skip this class and utility files
				if ( isset( $trace['class'] ) && $trace['class'] === __CLASS__ ) {
					continue;
				}
				if ( isset( $trace['file'] ) && strpos( $trace['file'], 'Utilities.php' ) !== false ) {
					continue;
				}

				// Check if this file has the right namespace
				if ( isset( $trace['file'] ) && file_exists( $trace['file'] ) ) {
					$file_namespace = self::extract_namespace_from_file( $trace['file'] );
					if ( $file_namespace === $caller_namespace ) {
						$caller_file = $trace['file'];
						break;
					}
				}
			}

			if ( ! $caller_file ) {
				// Fallback to first non-utility file
				foreach ( $backtrace as $trace ) {
					if ( isset( $trace['file'] ) && strpos( $trace['file'], 'Utilities.php' ) === false ) {
						$caller_file = $trace['file'];
						break;
					}
				}
			}

			if ( $caller_file ) {
				$caller_dir = dirname( $caller_file );

				// Check if we're in a /src directory (common for composer packages)
				if ( basename( $caller_dir ) === 'src' ) {
					// Go up one level to the package root, then look for assets
					$package_root = dirname( $caller_dir );
					$assets_path  = $package_root . '/assets';
				} else {
					// Default behavior: assets folder relative to caller
					$assets_path = $caller_dir . '/assets';
				}

				// Fallback: if assets doesn't exist, try some common patterns
				if ( ! is_dir( $assets_path ) ) {
					$alternatives = [
						$caller_dir . '/assets',
						dirname( $caller_dir ) . '/assets',
						$caller_dir . '/../assets',
					];

					foreach ( $alternatives as $alt_path ) {
						if ( is_dir( $alt_path ) ) {
							$assets_path = $alt_path;
							break;
						}
					}
				}
			} else {
				// Fallback if we can't determine caller
				$assets_path = dirname( __DIR__ ) . '/assets';
			}
		}

		self::$resolvers[ $namespace ] = rtrim( $assets_path, '/' );
	}

	/**
	 * Enqueue a CSS file from the calling library
	 *
	 * @param string      $file    Relative path to the CSS file (from assets folder)
	 * @param array       $deps    Optional. Array of style handles to enqueue before this one. Default empty array.
	 * @param string|null $version Optional. Version string for cache busting. Default null.
	 * @param string      $media   Optional. Media for which this stylesheet applies. Default 'all'.
	 * @param string      $handle  Optional. Custom handle for the style. If empty, auto-generated. Default empty.
	 *
	 * @return string|false Style handle on success, false on failure
	 */
	public static function enqueue_style(
		string $file,
		array $deps = [],
		?string $version = null,
		string $media = 'all',
		string $handle = ''
	) {
		$namespace = self::get_caller_namespace();

		if ( ! $namespace || ! isset( self::$resolvers[ $namespace ] ) ) {
			return false;
		}

		$assets_path = self::$resolvers[ $namespace ];
		$file_path   = $assets_path . '/' . ltrim( $file, '/' );

		if ( ! file_exists( $file_path ) ) {
			return false;
		}

		$url = self::path_to_url( $file_path );

		if ( ! $url ) {
			return false;
		}

		$handle  = $handle ?: self::generate_handle( $namespace, basename( $file, '.css' ) );
		$version = $version ?: (string) filemtime( $file_path );

		wp_enqueue_style( $handle, $url, $deps, $version, $media );

		return $handle;
	}

	/**
	 * Enqueue a JavaScript file from the calling library
	 *
	 * @param string      $file      Relative path to the JS file (from assets folder)
	 * @param array       $deps      Optional. Array of script handles to enqueue before this one. Default ['jquery'].
	 * @param string|null $version   Optional. Version string for cache busting. Default null.
	 * @param bool        $in_footer Optional. Whether to enqueue the script in the footer. Default true.
	 * @param string      $handle    Optional. Custom handle for the script. If empty, auto-generated. Default empty.
	 *
	 * @return string|false Script handle on success, false on failure
	 */
	public static function enqueue_script(
		string $file,
		array $deps = [ 'jquery' ],
		?string $version = null,
		bool $in_footer = true,
		string $handle = ''
	) {
		$namespace = self::get_caller_namespace();

		if ( ! $namespace || ! isset( self::$resolvers[ $namespace ] ) ) {
			return false;
		}

		$assets_path = self::$resolvers[ $namespace ];
		$file_path   = $assets_path . '/' . ltrim( $file, '/' );

		if ( ! file_exists( $file_path ) ) {
			return false;
		}

		$url = self::path_to_url( $file_path );

		if ( ! $url ) {
			return false;
		}

		$handle  = $handle ?: self::generate_handle( $namespace, basename( $file, '.js' ) );
		$version = $version ?: (string) filemtime( $file_path );

		wp_enqueue_script( $handle, $url, $deps, $version, $in_footer );

		return $handle;
	}

	/**
	 * Get the URL for an asset file
	 *
	 * @param string      $file      Relative path to the asset file
	 * @param string|null $namespace Optional. Specific namespace to use. If null, uses caller's namespace.
	 *
	 * @return string|null Asset URL or null if not found
	 */
	public static function get_asset_url( string $file, ?string $namespace = null ): ?string {
		$namespace = $namespace ?: self::get_caller_namespace();

		if ( ! $namespace || ! isset( self::$resolvers[ $namespace ] ) ) {
			return null;
		}

		$assets_path = self::$resolvers[ $namespace ];
		$file_path   = $assets_path . '/' . ltrim( $file, '/' );

		if ( ! file_exists( $file_path ) ) {
			return null;
		}

		return self::path_to_url( $file_path );
	}

	/**
	 * Get the file path for an asset file
	 *
	 * @param string      $file      Relative path to the asset file
	 * @param string|null $namespace Optional. Specific namespace to use. If null, uses caller's namespace.
	 *
	 * @return string|null Asset file path or null if not found
	 */
	public static function get_asset_path( string $file, ?string $namespace = null ): ?string {
		$namespace = $namespace ?: self::get_caller_namespace();

		if ( ! $namespace || ! isset( self::$resolvers[ $namespace ] ) ) {
			return null;
		}

		$assets_path = self::$resolvers[ $namespace ];
		$file_path   = $assets_path . '/' . ltrim( $file, '/' );

		if ( ! file_exists( $file_path ) ) {
			return null;
		}

		return $file_path;
	}

	/**
	 * Localize data for a script
	 *
	 * @param string $handle      Script handle
	 * @param string $object_name JavaScript object name
	 * @param array  $data        Localization data
	 *
	 * @return bool Whether the localization was successful
	 */
	public static function localize_script( string $handle, string $object_name, array $data ): bool {
		return wp_localize_script( $handle, $object_name, $data );
	}

	/**
	 * Get all registered namespaces and their paths
	 *
	 * @return array Array of registered namespaces and paths
	 */
	public static function get_registered(): array {
		return self::$resolvers;
	}

	/**
	 * Clear registration for a specific namespace or all namespaces
	 *
	 * @param string $namespace Optional. Specific namespace to clear. If empty, clears all registrations.
	 *
	 * @return void
	 */
	public static function clear( string $namespace = '' ): void {
		if ( empty( $namespace ) ) {
			self::$resolvers = [];
		} else {
			unset( self::$resolvers[ $namespace ] );
		}
	}

	/**
	 * Get the namespace of the calling function
	 *
	 * @return string|null Namespace or null if not found
	 */
	private static function get_caller_namespace(): ?string {
		$backtrace = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, 4 );

		foreach ( $backtrace as $trace ) {
			// Skip this class
			if ( isset( $trace['class'] ) && $trace['class'] === __CLASS__ ) {
				continue;
			}

			// Skip utility functions
			if ( isset( $trace['file'] ) && strpos( $trace['file'], 'Utilities.php' ) !== false ) {
				continue;
			}

			// Get namespace from class if available
			if ( isset( $trace['class'] ) ) {
				$class_parts = explode( '\\', $trace['class'] );
				array_pop( $class_parts ); // Remove class name

				return implode( '\\', $class_parts );
			}

			// Try to extract namespace from file
			if ( isset( $trace['file'] ) ) {
				$namespace = self::extract_namespace_from_file( $trace['file'] );
				if ( $namespace ) {
					return $namespace;
				}
			}
		}

		return null;
	}

	/**
	 * Extract namespace from a PHP file
	 *
	 * @param string $file File path
	 *
	 * @return string|null Extracted namespace or null if not found
	 */
	private static function extract_namespace_from_file( string $file ): ?string {
		if ( ! file_exists( $file ) ) {
			return null;
		}

		$content = file_get_contents( $file );

		if ( preg_match( '/^\s*namespace\s+([^\s;]+)/m', $content, $matches ) ) {
			return $matches[1];
		}

		return null;
	}

	/**
	 * Convert a file path to a URL
	 *
	 * @param string $path File path to convert
	 *
	 * @return string|null URL or null if conversion fails
	 */
	private static function path_to_url( string $path ): ?string {
		$path = wp_normalize_path( $path );

		// Try wp-content first
		$wp_content_dir = wp_normalize_path( WP_CONTENT_DIR );
		if ( strpos( $path, $wp_content_dir ) === 0 ) {
			return str_replace( $wp_content_dir, content_url(), $path );
		}

		// Fallback to ABSPATH
		$abspath = wp_normalize_path( ABSPATH );
		if ( strpos( $path, $abspath ) === 0 ) {
			return str_replace( $abspath, home_url(), $path );
		}

		return null;
	}

	/**
	 * Generate a unique handle for assets
	 *
	 * @param string $namespace Base namespace for the handle
	 * @param string $name      Asset name
	 *
	 * @return string Generated handle
	 */
	private static function generate_handle( string $namespace, string $name ): string {
		$base_handle = sanitize_key( str_replace( '\\', '-', strtolower( $namespace ) ) . '-' . $name );
		$handle      = $base_handle;

		// Ensure uniqueness
		$counter = 1;
		while ( wp_script_is( $handle ) || wp_style_is( $handle ) ) {
			$handle = $base_handle . '-' . $counter;
			$counter ++;
		}

		return $handle;
	}

}