# WordPress Feature API Demo Plugin

This demo plugin showcases how to use the WordPress Feature API, including registering both server-side and client-side features.

## Installation

1. This demo is included in the main `wp-feature-api` repository. Follow the Installation instructions in the [main README.md](../../README.md).
2. Ensure the main "WordPress Feature API" plugin is activated in your WordPress environment.
3. The demo should load automatically (controlled by `WP_FEATURE_API_LOAD_DEMO` in the main plugin file). An admin notice will confirm it's active.
4. Navigate to "Tools" -> "Feature API Demo" in the WordPress admin to see the demo chat interface.

## Usage Examples

### Using the REST API

You can view the available server-side features directly through the WordPress REST API:

```
GET: /wp-json/wp/v2/features
```

And can call a feature like this:

```
POST: /wp-json/wp/v2/features/[feature-id]
{
  "title": "My New Post",
  "content": "This is the content of my new post.",
  "status": "draft"
}
```

### Using Features Directly

Some REST functionality is already built in, so you can use those features directly.

```php
// Get a post
$site_info = wp_find_feature( 'resource-post' )->call([
 'id' => 1,
]);

// Create a post
$post_data = array(
    'title'   => 'My New Post',
    'content' => 'This is the content of my new post.',
    'status'  => 'draft',
);
$result = wp_find_feature( 'tool-posts' )->call( $post_data );

// Get current user information
$user_info = wp_find_feature("resource-users/me")->call();
```

## Custom Features

You can use this demo plugin as a template to create your own features. Simply add your feature registration and callback functions to the `RegisterFeatures.php` file or create new files to organize your features.

## Included Demo Features

This plugin registers some example features under `RegisterFeatures`. Some are plugin dependent, like for WooCommerce, so make sure you've installed and plugin dependencies if you want to use those features.

- `resource-demo/woocommerce-info`: Get basic information about the WooCommerce configuration.
- `resource-demo/site-info`: Get basic global site information.

## Client-Side Features

This demo utilizes the `@wp-feature-api/client` SDK package to interact with the Feature API on the frontend.

### Registering a Custom Client Feature

- `demo/log-message`: Logs a message to the browser console.
