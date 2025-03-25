# WordPress Feature API Demo Plugin

This demo plugin showcases how to use the WordPress Feature API to register and use features in your WordPress plugins or themes.

## Requirements

- WordPress 6.0 or higher
- PHP 7.2 or higher
- [WordPress Feature API](https://github.com/WordPress/wp-feature-api) plugin installed and activated

## Installation

1. Make sure you have the WordPress Feature API plugin installed and activated.
2. Upload the `wp-feature-api-demo` folder to the `/wp-content/plugins/` directory.
3. Activate the "WordPress Feature API Demo" plugin through the 'Plugins' menu in WordPress.

## Included Demo Features

This plugin registers three example features:

### 1. Site Information (Resource)

- ID: `demo/site-info`
- Type: Resource
- Description: Get basic information about the WordPress site.
- Permission: `read`

### 2. Create Post (Tool)

- ID: `demo/create-post`
- Type: Tool
- Description: Create a new post in WordPress.
- Permission: `publish_posts`
- Input Schema: Requires `title` and `content`, with optional `status`.
- Output Schema: Returns created post `id`, `url`, and `status`.

### 3. Current User Information (Resource)

- ID: `demo/current-user`
- Type: Resource
- Description: Get information about the current user.
- Permission: `read`

## Usage Examples

### Using the REST API

You can interact with these features through the WordPress REST API:

1. Get Site Information:
```
GET /wp-json/wp/v2/features/demo/site-info
```

2. Create a Post:
```
POST /wp-json/wp/v2/features/demo/create-post
{
  "title": "My New Post",
  "content": "This is the content of my new post.",
  "status": "draft"
}
```

3. Get Current User Information:
```
GET /wp-json/wp/v2/features/demo/current-user
```

### Using PHP in Your Code

```php
// Get site information
$site_info = wp_find_feature( 'demo/site-info' )->run();

// Create a post
$post_data = array(
    'title'   => 'My New Post',
    'content' => 'This is the content of my new post.',
    'status'  => 'draft',
);
$result = wp_find_feature( 'demo/create-post' )->run( $post_data );

// Get current user information
$user_info = wp_find_feature( 'demo/current-user' )->run();
```

## Custom Features

You can use this demo plugin as a template to create your own features. Simply add your feature registration and callback functions to the `demo-features.php` file or create new files to organize your features.

## License

GPLv2 or later 
