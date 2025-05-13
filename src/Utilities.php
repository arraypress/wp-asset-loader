<?php
/**
 * Asset Loader Utility Functions
 *
 * @package     ArrayPress\WP\AssetLoader
 * @copyright   Copyright (c) 2025, ArrayPress Limited
 * @license     GPL2+
 * @version     1.0.0
 * @author      David Sherlock
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

use ArrayPress\WP\AssetLoader;

if ( ! function_exists( 'register_library_assets' ) ):
	/**
	 * Helper function to register asset path for a namespace
	 *
	 * @param string      $namespace   The namespace for the library
	 * @param string|null $assets_path Optional. Path to assets folder. Defaults to /assets relative to caller.
	 *
	 * @return void
	 */
	function register_library_assets( string $namespace, ?string $assets_path = null ): void {
		AssetLoader::register( $namespace, $assets_path );
	}
endif;

if ( ! function_exists( 'enqueue_library_style' ) ):
	/**
	 * Helper function to enqueue a CSS file from the calling library
	 *
	 * @param string      $file    Relative path to the CSS file (from assets folder)
	 * @param array       $deps    Optional. Array of style handles to enqueue before this one. Default empty array.
	 * @param string|null $version Optional. Version string for cache busting. Default null.
	 * @param string      $media   Optional. Media for which this stylesheet applies. Default 'all'.
	 * @param string      $handle  Optional. Custom handle for the style. If empty, auto-generated. Default empty.
	 *
	 * @return string|false Style handle on success, false on failure
	 */
	function enqueue_library_style(
		string $file,
		array $deps = [],
		?string $version = null,
		string $media = 'all',
		string $handle = ''
	) {
		return AssetLoader::enqueue_style( $file, $deps, $version, $media, $handle );
	}
endif;

if ( ! function_exists( 'enqueue_library_script' ) ):
	/**
	 * Helper function to enqueue a JavaScript file from the calling library
	 *
	 * @param string      $file      Relative path to the JS file (from assets folder)
	 * @param array       $deps      Optional. Array of script handles to enqueue before this one. Default ['jquery'].
	 * @param string|null $version   Optional. Version string for cache busting. Default null.
	 * @param bool        $in_footer Optional. Whether to enqueue the script in the footer. Default true.
	 * @param string      $handle    Optional. Custom handle for the script. If empty, auto-generated. Default empty.
	 *
	 * @return string|false Script handle on success, false on failure
	 */
	function enqueue_library_script(
		string $file,
		array $deps = [ 'jquery' ],
		?string $version = null,
		bool $in_footer = true,
		string $handle = ''
	) {
		return AssetLoader::enqueue_script( $file, $deps, $version, $in_footer, $handle );
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
	 * @param string      $file      Relative path to the asset file
	 * @param string|null $namespace Optional. Specific namespace to use. If null, uses caller's namespace.
	 *
	 * @return string|null Asset file path or null if not found
	 */
	function get_library_asset_path( string $file, ?string $namespace = null ): ?string {
		return AssetLoader::get_asset_path( $file, $namespace );
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