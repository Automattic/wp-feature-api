# WordPress Feature API

The WordPress Feature API is a system for exposing WordPress functionality in a standardized, discoverable way for both server and client-side use. It's designed to make WordPress functionality accessible to AI systems (particularly LLMs) and developers through a unified registry of resources and tools.

## Key Features

- **Unified Registry**: Central registry of features accessible from both client and server
- **Standardized Format**: uses the MCP specification for the registry
- **Reuses existing functionality**: existing WordPress functionality like REST endpoints are reused as features, making them more discoverable and easier to use by LLMs.
- **Filterable**: Features can be filtered, categorized, and searched for more accurate feature matching.
- **Extensible**: Easy to register new features from plugins and themes

## Project Structure

This project is structured as a monorepo using npm workspaces:

- **`packages/client`**: The core client-side SDK (`@wp-feature-api/client`). Provides the API (`registerFeature`, `executeFeature`, `Feature` type) for interacting with the feature registry on the frontend and manages the underlying data store. Third-party plugins can use this to register their own client-side features.
- **`packages/client-features`**: A library containing implementations of standard client-side features (e.g., block insertion, navigation). It depends on the client SDK and is used by the main plugin to register the core features.
- **`demo/wp-feature-api-agent`**: A demo WordPress plugin showcasing how to use the Feature API, including registering features, and implementing WP Features as tools in Typescript based AI Agent.
- **`src/`**: Contains the main JavaScript entry point (`src/index.js`) for the core WordPress plugin. This script initializes the client SDK and registers the core client features when the plugin is active.
- **`includes/`**: Contains the core PHP logic for the Feature API, including the registry, REST API endpoints, and server-side feature definitions.

## MCP

It relies heavily on the [MCP Specification](https://spec.modelcontextprotocol.io/specification/2025-03-26/), however it's tailored to the needs of WordPress. Since WordPress is by nature both the server and the client, the Feature API is designed to be used in both contexts, and leverage existing WordPress functionality.

Features may surface in an actual WP MCP server consumed by an external MCP client. The main difference is that the features are compatible across the server and client, allowing for WordPress to execute features itself on both the backend and frontend.

Note, this does not implement the MCP server and transport layer. However, the feature registry may be used by an MCP server in WordPress whenever available.

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

### Installation

1. Clone the repository.
2. Run `npm run setup` to install all dependencies (both PHP and JavaScript).

### Building

Run `npm run build` from the root directory. This command will build all the JavaScript packages (`client`, `client-features`, `demo`) and the main plugin script (`src/index.js`).

### Running the Demo

1. Ensure dependencies are installed and code is built (see above).
2. Use `@wordpress/env` (or your preferred local WordPress environment such as Studio) to start WordPress. You can use `npm run wp-env start` from the root directory.
3. Activate the "WordPress Feature API" plugin.
4. The demo plugin (`wp-feature-api-agent`) should load automatically (controlled by the `WP_FEATURE_API_LOAD_DEMO` constant in `wp-feature-api.php`). You should see an admin notice confirming this.
5. Navigate to the "WP Feature Agent Demo" page added under the Settings menu in the WordPress admin to configure your OpenAI API key.
6. Refresh and see the AI Agent chat interface.
7. Ask the AI Agent questions about your WordPress site and features. It has access to both server-side and client-side features.
