# WordPress Asset Loader - Composer Library Asset Management Made Easy

A simple and elegant utility for WordPress that automatically detects and loads CSS and JavaScript assets from Composer libraries. No more manual path detection - just register your namespace and start enqueueing assets.

## Features

* 🎯 **Dead Simple API**: One line to register, one line to enqueue
* 📦 **Namespace-Based**: Uses your library's namespace for automatic organization
* 🔍 **Auto-Detection**: Automatically finds your assets folder (defaults to `/assets`)
* 🔒 **Unique Handles**: Prevents conflicts with other libraries
* ⚡ **Automatic Versioning**: Uses file modification time for cache busting
* 🗂️ **Flexible Structure**: Supports custom asset folder structures

## Requirements

* PHP 7.4 or later
* WordPress 6.7.1 or later

## Installation

Install via Composer:

```bash
composer require arraypress/wp-asset-loader
```

## Basic Usage

You can use either the AssetLoader class directly or the utility functions:

```php
// Using the AssetLoader class
use ArrayPress\WP\AssetLoader;

// Register your assets (defaults to /assets folder)
AssetLoader::register( __NAMESPACE__ );

// Enqueue assets
AssetLoader::enqueue_style( 'css/admin.css' );
AssetLoader::enqueue_script( 'js/admin.js' );

// Or using utility functions
register_library_assets( __NAMESPACE__ );
enqueue_library_style( 'css/admin.css' );
enqueue_library_script( 'js/admin.js' );
```

### Utility Functions

The package provides convenient utility functions for all operations:

```php
// Register assets
register_library_assets( __NAMESPACE__ );
register_library_assets( __NAMESPACE__, __DIR__ . '/dist' ); // Custom path

// Enqueue styles
enqueue_library_style( 'admin.css' );
enqueue_library_style( 'admin.css', ['wp-admin'], '1.0.0' );

// Enqueue scripts
enqueue_library_script( 'admin.js' );
enqueue_library_script( 'admin.js', ['jquery'], '1.0.0', true );

// Get asset URLs and paths
$url = get_library_asset_url( 'images/icon.png' );
$path = get_library_asset_path( 'css/admin.css' );

// Localize scripts
localize_library_script( $handle, 'MyConfig', $data );
```

## Examples

### Basic Setup

```php
// In your library's main file
namespace ArrayPress\S3;

class Browser {
    public function __construct() {
        // Register assets - assumes assets are in /assets folder
        register_library_assets( __NAMESPACE__ );
        
        // Enqueue your CSS and JS
        enqueue_library_style( 'css/s3-browser.css' );
        enqueue_library_script( 'js/s3-browser.js' );
    }
}
```

### Custom Asset Folder

```php
// If your assets are in a different folder (e.g., dist/, build/)
register_library_assets( __NAMESPACE__, __DIR__ . '/dist' );
register_library_assets( __NAMESPACE__, __DIR__ . '/build' );
```

### Advanced Usage

```php
// Enqueue with dependencies and custom handle
$handle = enqueue_library_style(
    'admin.css',
    ['wp-admin', 'common'],  // Dependencies
    '1.0.0',                 // Version
    'screen',                // Media
    'my-plugin-admin'        // Custom handle
);

// Localize script with data
$script_handle = enqueue_library_script(
    'admin.js',
    ['jquery', 'wp-util'],   // Dependencies
    '1.0.0',                 // Version
    true,                    // In footer
    'my-plugin-script'       // Custom handle
);

// Add localization data
localize_library_script( $script_handle, 'MyPluginConfig', [
    'ajaxUrl' => admin_url( 'admin-ajax.php' ),
    'nonce'   => wp_create_nonce( 'my-plugin-nonce' ),
    'settings' => get_option( 'my_plugin_settings', [] )
]);
```

### Multiple Libraries in One Plugin

```php
// Library 1: S3 Browser
namespace ArrayPress\S3;

class Browser {
    public function init() {
        register_library_assets( __NAMESPACE__ );
        enqueue_library_style( 'css/s3-browser.css' );
    }
}

// Library 2: Form Builder
namespace ArrayPress\Forms;

class FormBuilder {
    public function init() {
        register_library_assets( __NAMESPACE__ );
        enqueue_library_style( 'css/form-builder.css' );
    }
}
```

## Folder Structure

The library expects a standard folder structure:

```
your-library/
├── src/
│   └── YourClass.php
├── assets/
│   ├── css/
│   │   ├── admin.css
│   │   └── public.css
│   ├── js/
│   │   ├── admin.js
│   │   └── public.js
│   └── images/
│       └── icon.png
└── composer.json
```

## Working with Composer Vendor Directory

This works perfectly with Composer's vendor directory structure:

```
my-wordpress-plugin/
├── composer.json
├── plugin.php
├── vendor/
│   ├── autoload.php
│   └── arraypress/
│       ├── s3-browser/
│       │   ├── src/
│       │   └── assets/
│       └── form-builder/
│           ├── src/
│           └── assets/
```

## API Reference

### AssetLoader Class

```php
// Register namespace
AssetLoader::register( string $namespace, ?string $assets_path = null );

// Enqueue styles
AssetLoader::enqueue_style( string $file, array $deps = [], ?string $version = null, string $media = 'all', string $handle = '' );

// Enqueue scripts
AssetLoader::enqueue_script( string $file, array $deps = ['jquery'], ?string $version = null, bool $in_footer = true, string $handle = '' );

// Get asset URL/path
AssetLoader::get_asset_url( string $file, ?string $namespace = null );
AssetLoader::get_asset_path( string $file, ?string $namespace = null );

// Manage registrations
AssetLoader::get_registered();
AssetLoader::clear( string $namespace = '' );
```

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request. For major changes, please open an issue first to discuss what you would like to change.

## License

Licensed under the GPLv2 or later license.

## Support

- [Issue Tracker](https://github.com/arraypress/wp-asset-loader/issues)