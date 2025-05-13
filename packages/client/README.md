# WordPress Feature API - Client SDK (@automattic/wp-feature-api)

This package provides the core client-side SDK for the WordPress Feature API. It allows client-side code running in the WordPress admin to register, discover, and execute features.

## Purpose

- Provides a `Feature` interface definition for client-side features to follow.
- Manages the client-side feature registry and data store via `@wordpress/data`.
- Exposes API functions for interacting with features:
  - `registerFeature(feature: Feature)`: Adds a client-side feature definition to the registry.
  - `executeFeature(featureId: string, args: any): Promise<unknown>`: Executes the callback of a registered client-side feature.
- Initializes the connection to the server-side feature registry via the REST API to discover features available on the server.

## Installation

```bash
npm install @automattic/wp-feature-api
```

## WordPress Script Dependencies & Enqueueing

This package is designed to work as a singleton, provided by the main "WordPress Feature API" plugin. Ensure the main `wp-feature-api` plugin is installed and active on your WordPress site before using this package.

When using `@automattic/wp-feature-api` in your WordPress plugin, you must declare the correct dependencies in your `wp_enqueue_script` call. The main `wp-feature-api` plugin provides the actual client script.

1. **Ensure the main "WordPress Feature API" plugin is active.**
2. Add `'wp-features'` to your script's dependency array. This is the handle for the client SDK provided by the main plugin.

```php
$asset_file = include( plugin_dir_path( __FILE__ ) . 'build/index.asset.php' ); // If using @wordpress/scripts

wp_enqueue_script(
    'your-plugin-script-handle',
    plugin_dir_url( __FILE__ ) . 'build/index.js',
    array_merge( $asset_file['dependencies'], array( 'wp-features' ) ), // Add 'wp-features' to the dependencies
    $asset_file['version'],
    true
);
```

If you are not using `@wordpress/scripts` to generate an `index.asset.php` file, you may need to add relevant dependencies like `wp-data`, `wp-core-data`, `wp-api-fetch`, `wp-plugins`, in addition to `wp-features`.

## Webpack Configuration

To ensure that your plugin uses the client SDK provided by the main `wp-feature-api` plugin (and to avoid bundling the SDK code into your plugin), you need to configure `@automattic/wp-feature-api` as an external in your webpack configuration.

This tells webpack to expect `@automattic/wp-feature-api` to be available globally as `window.wp.features` at runtime.

```javascript
// In your plugin's webpack.config.js
module.exports = {
  // ... other webpack config
  externals: {
    '@automattic/wp-feature-api': 'wp.features',
    // It's good practice to also externalize other @wordpress packages
    '@wordpress/data': 'wp.data',
    // Add any other @wordpress packages you use directly...
  }
};
```

## Usage

```js
import { registerFeature, executeFeature } from '@automattic/wp-feature-api';

// Register a feature
registerFeature({
  id: 'my-feature',
  title: 'My Feature',
  callback: async (args) => {
    // Feature implementation
    return 'result';
  }
});

// Execute a feature
const result = await executeFeature('my-feature', { someArg: 'value' });
```

## Build

This package is built using `@wordpress/scripts`. Run `npm run build` to build locally.
