<?php
/**
 * Improved WordPress Asset Loader
 *
 * @package     ArrayPress\WP\AssetLoader
 * @copyright   Copyright (c) 2025, ArrayPress Limited
 * @license     GPL2+
 * @version     2.0.0
 * @author      ArrayPress Team
 */

declare( strict_types=1 );

namespace ArrayPress\WP;

defined( 'ABSPATH' ) || exit;

/**
 * Class AssetLoader
 *
 * Enhanced utility for loading WordPress assets from Composer libraries.
 * Supports namespace-based asset registration with improved path detection.
 */
class AssetLoader {

	/**
	 * Registered asset configurations by namespace
	 *
	 * @var array
	 */
	private static array $registrations = [];

	/**
	 * Tracks which assets have been enqueued to prevent duplicates
	 *
	 * @var array
	 */
	private static array $enqueued_assets = [];

	/**
	 * Cache for namespace detection to improve performance
	 *
	 * @var array
	 */
	private static array $namespace_cache = [];

	/**
	 * Register an asset configuration for a namespace
	 *
	 * @param string      $namespace    The namespace for the library
	 * @param string|null $assets_path  Optional. Path to the assets folder
	 * @param string|null $assets_url   Optional. URL to the assets folder (for CDN support)
	 * @param array       $config       Optional. Additional configuration
	 *
	 * @return bool True on success, false on failure
	 */
	public static function register(
		string $namespace,
		?string $assets_path = null,
		?string $assets_url = null,
		array $config = []
	): bool {
		// Auto-detect path if not provided
		if ( $assets_path === null ) {
			$assets_path = self::detect_assets_path( $namespace );
		}

		if ( ! $assets_path ) {
			return false;
		}

		// Auto-detect URL if not provided
		if ( $assets_url === null ) {
			$assets_url = self::path_to_url( $assets_path );
		}

		// Store the configuration
		self::$registrations[ $namespace ] = [
			'assets_path' => rtrim( $assets_path, '/' ),
			'assets_url'  => rtrim( $assets_url, '/' ),
			'config'      => array_merge( [
				'version_strategy' => 'filemtime', // or 'static'
				'cache_busting'    => true,
				'handle_prefix'    => '',
			], $config )
		];

		return true;
	}

	/**
	 * Enhanced script enqueuing with better error handling
	 *
	 * @param string      $file      Relative path to the JS file
	 * @param array       $deps      Dependencies
	 * @param string|null $version   Version string
	 * @param bool        $in_footer Whether to load in footer
	 * @param string      $handle    Custom handle
	 * @param string|null $namespace Specific namespace to use
	 *
	 * @return string|false Script handle on success, false on failure
	 */
	public static function enqueue_script(
		string $file,
		array $deps = [ 'jquery' ],
		?string $version = null,
		bool $in_footer = true,
		string $handle = '',
		?string $namespace = null
	) {
		$namespace = $namespace ?: self::get_calling_namespace();

		if ( ! $namespace || ! isset( self::$registrations[ $namespace ] ) ) {
			error_log( "AssetLoader: Namespace '{$namespace}' not registered for file '{$file}'" );
			return false;
		}

		$config = self::$registrations[ $namespace ];
		$file_path = $config['assets_path'] . '/' . ltrim( $file, '/' );

		if ( ! file_exists( $file_path ) ) {
			error_log( "AssetLoader: Script file not found: {$file_path}" );
			return false;
		}

		// Generate handle
		$handle = $handle ?: self::generate_handle( $namespace, basename( $file, '.js' ), $config );

		// Check if already enqueued
		if ( wp_script_is( $handle, 'enqueued' ) ) {
			return $handle;
		}

		// Check internal tracking
		$asset_key = $namespace . '|script|' . $file;
		if ( isset( self::$enqueued_assets[ $asset_key ] ) ) {
			return self::$enqueued_assets[ $asset_key ];
		}

		// Build URL
		$url = $config['assets_url'] . '/' . ltrim( $file, '/' );

		// Handle versioning
		$version = self::resolve_version( $version, $file_path, $config );

		// Enqueue the script
		wp_enqueue_script( $handle, $url, $deps, $version, $in_footer );

		// Track the asset
		self::$enqueued_assets[ $asset_key ] = $handle;

		return $handle;
	}

	/**
	 * Enhanced style enqueuing
	 *
	 * @param string      $file      Relative path to the CSS file
	 * @param array       $deps      Dependencies
	 * @param string|null $version   Version string
	 * @param string      $media     Media type
	 * @param string      $handle    Custom handle
	 * @param string|null $namespace Specific namespace to use
	 *
	 * @return string|false Style handle on success, false on failure
	 */
	public static function enqueue_style(
		string $file,
		array $deps = [],
		?string $version = null,
		string $media = 'all',
		string $handle = '',
		?string $namespace = null
	) {
		$namespace = $namespace ?: self::get_calling_namespace();

		if ( ! $namespace || ! isset( self::$registrations[ $namespace ] ) ) {
			error_log( "AssetLoader: Namespace '{$namespace}' not registered for file '{$file}'" );
			return false;
		}

		$config = self::$registrations[ $namespace ];
		$file_path = $config['assets_path'] . '/' . ltrim( $file, '/' );

		if ( ! file_exists( $file_path ) ) {
			error_log( "AssetLoader: Style file not found: {$file_path}" );
			return false;
		}

		// Generate handle
		$handle = $handle ?: self::generate_handle( $namespace, basename( $file, '.css' ), $config );

		// Check if already enqueued
		if ( wp_style_is( $handle, 'enqueued' ) ) {
			return $handle;
		}

		// Check internal tracking
		$asset_key = $namespace . '|style|' . $file;
		if ( isset( self::$enqueued_assets[ $asset_key ] ) ) {
			return self::$enqueued_assets[ $asset_key ];
		}

		// Build URL
		$url = $config['assets_url'] . '/' . ltrim( $file, '/' );

		// Handle versioning
		$version = self::resolve_version( $version, $file_path, $config );

		// Enqueue the style
		wp_enqueue_style( $handle, $url, $deps, $version, $media );

		// Track the asset
		self::$enqueued_assets[ $asset_key ] = $handle;

		return $handle;
	}

	/**
	 * Get asset URL with namespace support
	 *
	 * @param string      $file      Relative path to the asset
	 * @param string|null $namespace Specific namespace to use
	 *
	 * @return string|null Asset URL or null if not found
	 */
	public static function get_asset_url( string $file, ?string $namespace = null ): ?string {
		$namespace = $namespace ?: self::get_calling_namespace();

		if ( ! $namespace || ! isset( self::$registrations[ $namespace ] ) ) {
			return null;
		}

		$config = self::$registrations[ $namespace ];
		$file_path = $config['assets_path'] . '/' . ltrim( $file, '/' );

		if ( ! file_exists( $file_path ) ) {
			return null;
		}

		return $config['assets_url'] . '/' . ltrim( $file, '/' );
	}

	/**
	 * Improved namespace detection with caching
	 *
	 * @return string|null
	 */
	private static function get_calling_namespace(): ?string {
		$backtrace = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, 8 );
		$cache_key = '';

		// Build cache key from backtrace
		foreach ( $backtrace as $trace ) {
			if ( isset( $trace['file'] ) ) {
				$cache_key .= $trace['file'] . '|';
			}
		}

		// Check cache first
		if ( isset( self::$namespace_cache[ $cache_key ] ) ) {
			return self::$namespace_cache[ $cache_key ];
		}

		$namespace = null;

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

				// Try different levels - sometimes we want the parent namespace
				for ( $i = count( $class_parts ) - 1; $i >= 1; $i-- ) {
					$potential_namespace = implode( '\\', array_slice( $class_parts, 0, $i ) );
					if ( isset( self::$registrations[ $potential_namespace ] ) ) {
						$namespace = $potential_namespace;
						break 2;
					}
				}
			}

			// Try to extract namespace from file
			if ( isset( $trace['file'] ) ) {
				$file_namespace = self::extract_namespace_from_file( $trace['file'] );
				if ( $file_namespace && isset( self::$registrations[ $file_namespace ] ) ) {
					$namespace = $file_namespace;
					break;
				}
			}
		}

		// Cache the result
		self::$namespace_cache[ $cache_key ] = $namespace;

		return $namespace;
	}

	/**
	 * Improved asset path detection
	 *
	 * @param string $target_namespace The namespace we're trying to find assets for
	 *
	 * @return string|null
	 */
	private static function detect_assets_path( string $target_namespace ): ?string {
		$backtrace = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, 10 );

		foreach ( $backtrace as $trace ) {
			if ( ! isset( $trace['file'] ) || ! file_exists( $trace['file'] ) ) {
				continue;
			}

			$file_namespace = self::extract_namespace_from_file( $trace['file'] );

			// Check if this file's namespace matches or is a parent of our target
			if ( $file_namespace === $target_namespace ||
			     strpos( $target_namespace, $file_namespace . '\\' ) === 0 ) {

				$file_dir = dirname( $trace['file'] );

				// Common patterns for finding assets
				$patterns = [
					$file_dir . '/assets',
					$file_dir . '/../assets',
					dirname( $file_dir ) . '/assets',  // For src/ structures
					$file_dir . '/../../assets',       // For deeper structures
				];

				foreach ( $patterns as $pattern ) {
					$resolved_path = realpath( $pattern );
					if ( $resolved_path && is_dir( $resolved_path ) ) {
						return $resolved_path;
					}
				}
			}
		}

		return null;
	}

	/**
	 * Handle version resolution
	 *
	 * @param string|null $version    Provided version
	 * @param string      $file_path  File path for filemtime
	 * @param array       $config     Configuration array
	 *
	 * @return string
	 */
	private static function resolve_version( ?string $version, string $file_path, array $config ): string {
		if ( $version !== null ) {
			return $version;
		}

		if ( ! $config['config']['cache_busting'] ) {
			return '1.0.0';
		}

		switch ( $config['config']['version_strategy'] ) {
			case 'filemtime':
				return (string) filemtime( $file_path );

			case 'static':
			default:
				return '1.0.0';
		}
	}

	/**
	 * Generate handle with prefix support
	 *
	 * @param string $namespace
	 * @param string $name
	 * @param array  $config
	 *
	 * @return string
	 */
	private static function generate_handle( string $namespace, string $name, array $config ): string {
		$prefix = $config['config']['handle_prefix'] ?:
			sanitize_key( str_replace( '\\', '-', strtolower( $namespace ) ) );

		$base_handle = $prefix . '-' . sanitize_key( $name );
		$handle = $base_handle;

		// Ensure uniqueness
		$counter = 1;
		while ( wp_script_is( $handle ) || wp_style_is( $handle ) ) {
			$handle = $base_handle . '-' . $counter;
			$counter++;
		}

		return $handle;
	}

	/**
	 * Extract namespace from file (cached)
	 *
	 * @param string $file
	 *
	 * @return string|null
	 */
	private static function extract_namespace_from_file( string $file ): ?string {
		static $file_cache = [];

		if ( isset( $file_cache[ $file ] ) ) {
			return $file_cache[ $file ];
		}

		if ( ! file_exists( $file ) ) {
			return null;
		}

		$content = file_get_contents( $file );
		$namespace = null;

		if ( preg_match( '/^\s*namespace\s+([^\s;]+)/m', $content, $matches ) ) {
			$namespace = $matches[1];
		}

		$file_cache[ $file ] = $namespace;
		return $namespace;
	}

	/**
	 * Convert file path to URL (enhanced)
	 *
	 * @param string $path
	 *
	 * @return string|null
	 */
	private static function path_to_url( string $path ): ?string {
		$path = wp_normalize_path( $path );

		// Common WordPress paths
		$mappings = [
			wp_normalize_path( WP_CONTENT_DIR ) => content_url(),
			wp_normalize_path( ABSPATH ) => home_url(),
			wp_normalize_path( WP_PLUGIN_DIR ) => plugins_url(),
		];

		foreach ( $mappings as $local_path => $url ) {
			if ( strpos( $path, $local_path ) === 0 ) {
				return str_replace( $local_path, $url, $path );
			}
		}

		return null;
	}

	/**
	 * Enhanced localization with duplicate prevention
	 *
	 * @param string $handle
	 * @param string $object_name
	 * @param array  $data
	 *
	 * @return bool
	 */
	public static function localize_script( string $handle, string $object_name, array $data ): bool {
		// More robust duplicate check
		if ( wp_script_is( $handle, 'localized' ) ) {
			global $wp_scripts;
			if ( isset( $wp_scripts->registered[ $handle ]->extra['data'] ) &&
			     strpos( $wp_scripts->registered[ $handle ]->extra['data'], "var $object_name" ) !== false ) {
				return true;
			}
		}

		return wp_localize_script( $handle, $object_name, $data );
	}

	/**
	 * Get debug information
	 *
	 * @return array
	 */
	public static function get_debug_info(): array {
		return [
			'registrations' => self::$registrations,
			'enqueued_assets' => self::$enqueued_assets,
			'namespace_cache' => self::$namespace_cache,
		];
	}

	/**
	 * Clear all caches and registrations
	 *
	 * @param string $namespace Optional specific namespace to clear
	 *
	 * @return void
	 */
	public static function clear( string $namespace = '' ): void {
		if ( empty( $namespace ) ) {
			self::$registrations = [];
			self::$enqueued_assets = [];
			self::$namespace_cache = [];
		} else {
			unset( self::$registrations[ $namespace ] );

			// Clear related assets
			foreach ( self::$enqueued_assets as $key => $handle ) {
				if ( strpos( $key, $namespace . '|' ) === 0 ) {
					unset( self::$enqueued_assets[ $key ] );
				}
			}
		}
	}

}