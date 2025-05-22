<?php
/**
 * Asset Loader Utility Functions
 *
 * @package     ArrayPress\WP\AssetLoader
 * @copyright   Copyright (c) 2025, ArrayPress Limited
 * @license     GPL2+
 * @version     2.0.0
 * @author      ArrayPress Team
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

use ArrayPress\WP\AssetLoader;

if ( ! function_exists( 'register_library_assets' ) ):
	/**
	 * Helper function to register asset configuration for a namespace
	 *
	 * @param string      $namespace   The namespace for the library
	 * @param string|null $assets_path Optional. Path to assets folder. Defaults to auto-detection.
	 * @param string|null $assets_url  Optional. URL to assets folder (for CDN support). Defaults to auto-detection.
	 * @param array       $config      Optional. Additional configuration options.
	 *
	 * @return bool True on success, false on failure
	 */
	function register_library_assets(
		string $namespace,
		?string $assets_path = null,
		?string $assets_url = null,
		array $config = []
	): bool {
		return AssetLoader::register( $namespace, $assets_path, $assets_url, $config );
	}
endif;

if ( ! function_exists( 'enqueue_library_style' ) ):
	/**
	 * Helper function to enqueue a CSS file from the calling library
	 *
	 * @param string      $file      Relative path to the CSS file (from assets folder)
	 * @param array       $deps      Optional. Array of style handles to enqueue before this one. Default empty array.
	 * @param string|null $version   Optional. Version string for cache busting. Default null (auto-detected).
	 * @param string      $media     Optional. Media for which this stylesheet applies. Default 'all'.
	 * @param string      $handle    Optional. Custom handle for the style. If empty, auto-generated. Default empty.
	 * @param string|null $namespace Optional. Specific namespace to use. If null, uses caller's namespace.
	 *
	 * @return string|false Style handle on success, false on failure
	 */
	function enqueue_library_style(
		string $file,
		array $deps = [],
		?string $version = null,
		string $media = 'all',
		string $handle = '',
		?string $namespace = null
	) {
		return AssetLoader::enqueue_style( $file, $deps, $version, $media, $handle, $namespace );
	}
endif;

if ( ! function_exists( 'enqueue_library_script' ) ):
	/**
	 * Helper function to enqueue a JavaScript file from the calling library
	 *
	 * @param string      $file      Relative path to the JS file (from assets folder)
	 * @param array       $deps      Optional. Array of script handles to enqueue before this one. Default ['jquery'].
	 * @param string|null $version   Optional. Version string for cache busting. Default null (auto-detected).
	 * @param bool        $in_footer Optional. Whether to enqueue the script in the footer. Default true.
	 * @param string      $handle    Optional. Custom handle for the script. If empty, auto-generated. Default empty.
	 * @param string|null $namespace Optional. Specific namespace to use. If null, uses caller's namespace.
	 *
	 * @return string|false Script handle on success, false on failure
	 */
	function enqueue_library_script(
		string $file,
		array $deps = [ 'jquery' ],
		?string $version = null,
		bool $in_footer = true,
		string $handle = '',
		?string $namespace = null
	) {
		return AssetLoader::enqueue_script( $file, $deps, $version, $in_footer, $handle, $namespace );
	}
endif;

if ( ! function_exists( 'get_library_asset_url' ) ):
	/**
	 * Helper function to get the URL for an asset file from the calling library
	 *
	 * @param string      $file      Relative path to the asset file
	 * @param string|null $namespace Optional. Specific namespace to use. If null, uses caller's namespace.
	 *
	 * @return string|null Asset URL or null if not found
	 */
	function get_library_asset_url( string $file, ?string $namespace = null ): ?string {
		return AssetLoader::get_asset_url( $file, $namespace );
	}
endif;

if ( ! function_exists( 'get_library_asset_path' ) ):
	/**
	 * Helper function to get the file path for an asset file from the calling library
	 *
	 * Note: This function maintains backward compatibility, but the improved AssetLoader
	 * focuses on URLs. For file paths, consider using get_library_asset_url() instead.
	 *
	 * @param string      $file      Relative path to the asset file
	 * @param string|null $namespace Optional. Specific namespace to use. If null, uses caller's namespace.
	 *
	 * @return string|null Asset file path or null if not found
	 */
	function get_library_asset_path( string $file, ?string $namespace = null ): ?string {
		// For backward compatibility, we'll reconstruct the path from registration
		$namespace = $namespace ?: AssetLoader::get_calling_namespace();

		if ( ! $namespace ) {
			return null;
		}

		$debug_info = AssetLoader::get_debug_info();
		if ( ! isset( $debug_info['registrations'][ $namespace ] ) ) {
			return null;
		}

		$assets_path = $debug_info['registrations'][ $namespace ]['assets_path'];
		$file_path   = $assets_path . '/' . ltrim( $file, '/' );

		return file_exists( $file_path ) ? $file_path : null;
	}
endif;

if ( ! function_exists( 'localize_library_script' ) ):
	/**
	 * Helper function to localize data for a script
	 *
	 * @param string $handle      Script handle
	 * @param string $object_name JavaScript object name
	 * @param array  $data        Localization data
	 *
	 * @return bool Whether the localization was successful
	 */
	function localize_library_script( string $handle, string $object_name, array $data ): bool {
		return AssetLoader::localize_script( $handle, $object_name, $data );
	}
endif;

if ( ! function_exists( 'register_library_assets_with_config' ) ):
	/**
	 * Helper function to register assets with advanced configuration
	 *
	 * @param string $namespace     The namespace for the library
	 * @param array  $config        Configuration options
	 *                              - 'assets_path' => string|null Path to assets folder
	 *                              - 'assets_url' => string|null URL to assets folder
	 *                              - 'version_strategy' => 'filemtime'|'static' Version strategy
	 *                              - 'cache_busting' => bool Enable cache busting
	 *                              - 'handle_prefix' => string Custom handle prefix
	 *
	 * @return bool True on success, false on failure
	 */
	function register_library_assets_with_config( string $namespace, array $config = [] ): bool {
		$assets_path = $config['assets_path'] ?? null;
		$assets_url  = $config['assets_url'] ?? null;

		// Remove path/url from config array as they're separate parameters
		unset( $config['assets_path'], $config['assets_url'] );

		return AssetLoader::register( $namespace, $assets_path, $assets_url, $config );
	}
endif;

if ( ! function_exists( 'enqueue_library_script_with_namespace' ) ):
	/**
	 * Convenience function for enqueuing scripts with explicit namespace (solves trait issues)
	 *
	 * @param string $file      Relative path to the JS file
	 * @param string $namespace Explicit namespace to use
	 * @param array  $deps      Dependencies
	 * @param string $handle    Custom handle
	 *
	 * @return string|false Script handle on success, false on failure
	 */
	function enqueue_library_script_with_namespace(
		string $file,
		string $namespace,
		array $deps = [ 'jquery' ],
		string $handle = ''
	) {
		return AssetLoader::enqueue_script( $file, $deps, null, true, $handle, $namespace );
	}
endif;

if ( ! function_exists( 'enqueue_library_style_with_namespace' ) ):
	/**
	 * Convenience function for enqueuing styles with explicit namespace (solves trait issues)
	 *
	 * @param string $file      Relative path to the CSS file
	 * @param string $namespace Explicit namespace to use
	 * @param array  $deps      Dependencies
	 * @param string $handle    Custom handle
	 *
	 * @return string|false Style handle on success, false on failure
	 */
	function enqueue_library_style_with_namespace(
		string $file,
		string $namespace,
		array $deps = [],
		string $handle = ''
	) {
		return AssetLoader::enqueue_style( $file, $deps, null, 'all', $handle, $namespace );
	}
endif;

if ( ! function_exists( 'get_library_debug_info' ) ):
	/**
	 * Helper function to get debug information from the AssetLoader
	 *
	 * @return array Debug information including registrations and enqueued assets
	 */
	function get_library_debug_info(): array {
		return AssetLoader::get_debug_info();
	}
endif;

if ( ! function_exists( 'clear_library_assets' ) ):
	/**
	 * Helper function to clear asset registrations and caches
	 *
	 * @param string $namespace Optional. Specific namespace to clear. If empty, clears all.
	 *
	 * @return void
	 */
	function clear_library_assets( string $namespace = '' ): void {
		AssetLoader::clear( $namespace );
	}
endif;