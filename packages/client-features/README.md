# WordPress Feature API - Client Features (@wp-feature-api/client-features)

This package provides a library of standard, reusable client-side features for the WordPress Feature API.

- Contains the actual implementation logic for common client-side actions within the WordPress frontend (e.g., interacting with the block editor, navigation).
- Uses the main `@wp-feature-api/client` package (for the `Feature`, `registerFeature`, etc).
- The main plugin's initialization script (`src/index.js`) imports and calls `registerCoreFeatures` to make these standard features available when the plugin loads.

## Usage

This package is primarily intended for internal use within the `wp-feature-api` project. Third-party plugins typically **do not** need to depend on or interact with this package directly.

Instead, third-party plugins should depend on `@wp-feature-api/client` to register their *own* custom features. The features provided here are registered automatically by the main `wp-feature-api` plugin, and can be used as examples for how to implement your own custom features.

## Build

This package is built using `@wordpress/scripts`. Run `npm run build` from the monorepo root.
