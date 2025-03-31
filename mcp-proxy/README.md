## MCP Proxy for the WordPress feature API

This package is a proxy server that takes care of the HTTP basic auth, requests the features registered using the WordPress feature API and exposes this features as tools via an MCP server working on stdio.

The idea is that this package is very minimal and is just needed until MCP clients support stateless HTTP connections (and WordPress supports oauth or MCP clients support basic auth).


## Testing
The client more used to test this package was Cursor.

- Create an application password for your wordpress username.


- Configure an MCP server following this syntax:
```json
{
	"mcpServers": {
		"feature-api": {
			"command": "node",
			"args": [ "REPO_PATH/wp-feature-api/mcp-proxy/dist/index.js" ],
			"env": {
				"WP_API_USERNAME": "USERNAME",
				"WP_API_PASSWORD": "APPLICATION_PASSWORD",
				"WP_API_URL": "http://WEBSITE.URL"
			}
		}
	}
}
```

- Try the following prompts:

List the last 10 posts on my site, with title date, and a very short summary written by you.

Create a new post which title "Summmary of my last 10 posts" which contains the summary you just shared.

## Add new tools

To add new tools use the WordPress feature API and the feature should automatically become available as a tool in the MCP server without any additional code necessary:
```php
	wp_register_feature(
		array(
			'id'          => 'demo/site-info',
			'name'        => __( 'Site Information', 'wp-feature-api-demo' ),
			'description' => __( 'Get basic information about the WordPress site.', 'wp-feature-api-demo' ),
			'type'        => WP_Feature::TYPE_RESOURCE,
			'categories'  => array( 'demo', 'site', 'information' ),
			'callback'    => 'wp_feature_api_demo_site_info_callback',
		)
	);
```
