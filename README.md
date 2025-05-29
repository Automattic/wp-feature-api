# WordPress Feature API

The WordPress Feature API is a system for exposing WordPress functionality in a standardized, discoverable way for both server and client-side use. It's designed to make WordPress functionality accessible to AI systems (particularly LLMs) and developers through a unified registry of resources and tools.

## Key Features

- **Unified Registry**: Central registry of features accessible from both client and server
- **Standardized Format**: uses the MCP specification for the registry
- **Reuses existing functionality**: existing WordPress functionality like REST endpoints are reused as features, making them more discoverable and easier to use by LLMs
- **Filterable**: Features can be filtered, categorized, and searched for more accurate feature matching
- **Extensible**: Easy to register new features from plugins and themes

## Project Structure

This project is structured as a monorepo using npm workspaces:

- **`packages/client`**: The core client-side SDK (`@automattic/wp-feature-api`). Provides the API (`registerFeature`, `executeFeature`, `Feature` type) for interacting with the feature registry on the frontend and manages the underlying data store. Third-party plugins can use this to register their own client-side features.
- **`packages/client-features`**: A library containing implementations of standard client-side features (e.g., block insertion, navigation). It depends on the client SDK and is used by the main plugin to register the core features for WordPress.
- **`demo/wp-feature-api-agent`**: A demo WordPress plugin showcasing how to use the Feature API, including registering features, and implementing WP Features as tools in a Typescript based AI Agent.
- **`src/`**: Contains the main JavaScript entry point (`src/index.js`) for the core WordPress plugin. This script initializes the client SDK and registers the core client features when the plugin is active.
- **`wp-feature-api.php`** & **`includes/`**: Contains the core PHP logic for the Feature API, including the registry, REST API endpoints, and server-side feature definitions. This is exported as a Composer package for use in other plugins.

## MCP

It relies heavily on the [MCP Specification](https://spec.modelcontextprotocol.io/specification/2025-03-26/), however it's tailored to the needs of WordPress. Since WordPress is by nature both the server and the client, the Feature API is designed to be used in both contexts, and leverage existing WordPress functionality.

Features may surface in an actual WP MCP server consumed by an external MCP client. The main difference is that the features are compatible across the server and client, allowing for WordPress to execute features itself on both the backend and frontend.

Note, this does not implement the MCP server and transport layer. However, the feature registry may be used by an MCP server like Automattic's [wordpress-mcp](https://github.com/Automattic/wordpress-mcp) plugin.

Features are not limited to LLM consumption and can be used throughout WordPress directly as a primitive API for generic functionality. Hence the more generic name of "Feature API" instead of "MCP API".

## Filtering

An important aspect of the Feature API is its ability to filter features manually and automatically. Since the success of an LLM agent will depend on the quality of tools that match the user's intent or current context within WordPress, the Feature API provides several mechanisms to ensure that the right tools are available at the right time.

Filtering can be done by:

- Querying feature properties
- Keyword search across name, description, and ID.
- Categories
- `is_eligible` boolean callback
- Context matching for when we already have some context and want Features that can be fulfilled using that context.

## Getting Started

### Development

#### Installation

1. Clone the repository.
2. Run `npm run setup` to install all dependencies (both PHP and JavaScript).

#### Building

Run `npm run build` from the root directory. This command will build all the JavaScript packages (`client`, `client-features`, `demo`) and the main plugin script (`src/index.js`).

### Using WordPress Feature API in Your Plugin via Composer

Plugin developers should include the WordPress Feature API in their plugins using Composer. The Feature API will automatically handle version conflicts when multiple plugins include it.

#### 1. Add as a Composer dependency

Manually adding to your `composer.json` file:

```json
{
  "require": {
    "automattic/wp-feature-api": "^0.1.5" // Make sure to use the latest version
  }
}
```

If using the `composer` command in the terminal:

```bash
composer require automattic/wp-feature-api:"^0.1.5"
```

#### 2. Load the Feature API in your plugin

To safely load the Feature API:

```php
// Plugin bootstrap code
function my_plugin_init() {
    // Just include the main plugin file - it automatically registers itself with the version manager
    require_once __DIR__ . '/vendor/automattic/wp-feature-api/wp-feature-api.php';

    // Register our features once we know API is initialized
    add_action( 'wp_feature_api_init', 'my_plugin_register_features' );
}

// hook into plugins_loaded - the Feature API will resolve which version to use
add_action( 'plugins_loaded', 'my_plugin_init' );

/**
 * Register features provided by this plugin
 */
function my_plugin_register_features() {
    // Register your features here
    wp_register_feature( 'my-plugin/example-feature', array(
        'name' => 'Example Feature',
        'description' => 'An example feature from my plugin',
        'callback' => 'my_plugin_example_feature_callback',
        'type' => 'tool',
        'input_schema' => array(
            'type' => 'object',
            'properties' => array(
                'example_param' => array(
                    'type' => 'string',
                    'description' => 'An example parameter',
                ),
            ),
        ),
    ) );
}
```

### Running the Demo

1. Ensure dependencies are installed and code is built (see above).
2. Use `@wordpress/env` (or your preferred local WordPress environment such as Studio) to start WordPress. You can use `npm run wp-env start` from the root directory.
3. Activate the "WordPress Feature API" plugin.
4. The demo plugin (`wp-feature-api-agent`) should load automatically (controlled by the `WP_FEATURE_API_LOAD_DEMO` constant in `wp-feature-api.php`). You should see an admin notice confirming this.
5. Navigate to the "WP Feature Agent Demo" page added under the Settings menu in the WordPress admin to configure your OpenAI API key.
6. Refresh and see the AI Agent chat interface.
7. Ask the AI Agent questions about your WordPress site and features. It has access to both server-side and client-side features.

## Contributing

We welcome contributions! Please see our [CONTRIBUTING.md](CONTRIBUTING.md) for details on how to contribute to this project.
