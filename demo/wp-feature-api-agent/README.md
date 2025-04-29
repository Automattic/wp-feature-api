# WordPress Feature API Demo

This demo plugin showcases how to use the WordPress Feature API, including registering features, and implementing WP Features as tools in a Typescript based AI Agent.

## Usage

1. This demo is included in the main `wp-feature-api` repository. Follow the Installation instructions in the [main README.md](../../README.md), and run the following commands from the root of the `wp-feature-api` repository:
   - Install dependencies: `npm install`
   - Build the plugin: `npm run build`
2. Ensure the main "WordPress Feature API" plugin is activated in your WordPress environment.
3. The demo should load automatically (controlled by `WP_FEATURE_API_LOAD_DEMO` in the main plugin file).
4. Navigate to "Settings" -> "WP Feature Agent Demo" in the WordPress admin to configure your OpenAI API key.
5. Refresh and see the AI Agent chat interface.
6. Ask the AI Agent questions about your WordPress site and features. It has access to both server-side and client-side features.
