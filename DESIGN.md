# Design: WordPress Features API

In relation to the [RFC](RFC.md), this document outlines the design of the WordPress Features API.

## Overview

The main goal of the Features API is to easily register features (in the form of `resources` and `tools`) in a way that makes them accessible to AI and WordPress developers.

We propose a registry accessible on both the client and server that uses the WP REST API to retrieve and resolve features.

## Feature Structure

```ts
type FeatureLocationTuple = ['client'] | ['server'] | ['client', 'server'];

type WP_Feature = {
	id: string; // Namespaced ID
	name: string; // Human readable name, used for AI context and UI labels
	description: string; // Description of the feature, used for AI context
	type: 'resource' | 'tool'; // Type of feature
	meta?: any; // Additional metadata
	categories?: string[]; // Categories of the feature, used for grouping and filtering
	input_schema?: any; // Schema for the feature input
	output_schema?: any; // Schema for the feature output, useful for structured outputs
	callback?: (context: any) => {}; // Callback for the feature
	permissions?:
		| string
		| string[]
		| ((user: WP_User, feature: WP_Feature) => boolean); // Permissions required to use the feature
	filter?: (feature: WP_Feature) => boolean; // Filter to determine if the feature is available
	_location: FeatureLocationTuple; // Location of the feature: [client], [server] or [client, server]
};
```

## Server Registry

### `WP_Feature_Registry`

The registery for the server is done through a `WP_Feature_Registry` singleton.

#### `WP_Feature_Registry::get_instance()`

Returns the singleton instance of the registry.

#### `WP_Feature_Registry::register(WP_Feature $feature)`

Registers a feature and persists its metadata to the repository.

#### `WP_Feature_Registry::unregister(string|WP_Feature $feature)`

Unregisters a feature by its ID and removes it from the repository.

#### `WP_Feature_Registry::find(string $feature_id)`

Retrieves a known feature by its ID.

#### `WP_Feature_Registry::get(?WP_Feature_Query $query = null)`

Queries the registry for features. This allows us to retrieve and filter features based on the query parameters.

A null param returns all features from the cache.

This can also forward pagination options for the REST response.

#### `WP_Feature_Registry::use_repository(WP_Feature_Repository_Interface $repository)`

Sets the repository to use for the registry. This can be a custom table, reuse of `posts`, or any other repository. It's the implementation that determines how features are stored and retrieved.

`WP_Feature` data should be serializable for storage in the repository.

The repository should be compatible with standard WordPress caching mechanisms for better performance.

This makes querying features easier and more efficient than in just the registry memory.

### Global Functions

Global functions should be provided to make working with the registry a bit easier.

Eg. `wp_register_feature(WP_Feature|array $feature)`

## Client Registry

The client registry is a `features` module on the global `wp` object. The registry is a `@wordpress/data` store. It shares the same API as the server registry. However, instead of querying the `WP_Feature_Registry` (and `WP_Feature_Repository` database), it queries the REST API as an intermediate to the server registry.

### `wp.features.register(feature: WP_Feature)`

Registers a feature. These are always registered with a `_location` of `client`. They are stored in the `wp.features` store.

### `wp.features.find(feature_id: string): Promise<WP_Feature|WP_Error>`

Retrieves a feature by its ID. There is a resolution process that occurs here. A query is made to the REST API to retrieve any server registered feature. These only exist if they have defined `callback`s. Features are flagged with `client` and/or `server` locations depending on which registry they are found in.

Note, When a `['client', 'server']` feature is ran, it'll execute on the server first and its response returned to and ran as the context for the client feature.

### `wp.features.get(query: WP_Feature_Query): Promise<WP_Feature[]|WP_Error>`

Queries the registry. Same as find but returns a collection of matching features according to the query.

### [...] The same as `WP_Feature_Registry` signature

## Transport Layer: WP REST API

The transport layer for our client and server features is the WP REST API. We should as much as possible rely on features of the REST API whenever possible. This includes: parameter/schema validation, authn+z, response formats, filters and hooks for "middleware", sanitization, etc.

A registered `WP_Feature` with a `callback` will be returned by the WP REST feature route. No callback is a `404` and can be assumed to be found in the client registry only.

The feature forwards its `input_schema` and `permissions` to be validated by the REST API's built-in capabilities.

The feature forwards its `output_schema` to the REST API for response validation through (the `schema` arg and `rest_ensure_response`).

### Text vs Stream

Features may be ran as a text response or a stream. Therefore two endpoints are registered for each server-feature, one for the text response, and one for the SSE stream.

### Requests

```
GET /wp-json/wp/v2/features # Query features
GET /wp-json/wp/v2/features/{feature_id} # Get feature data
POST /wp-json/wp/v2/features/{feature_id} # Run the feature
POST /wp-json/wp/v2/features/{feature_id}/stream # Run the feature and reply as a stream
```

Run Feature Payload:

```ts
{
  metadata: {
    client_features: WP_Feature[];
  },
  context: any;
}
```

#### Errors

Standard WP REST API errors should be surfaced to the feature response.

## More Details

The following data structures apply to both the client and server since they will share a very similar API. For simplicity's sake, we'll only discuss the server API in PHP here.

### `WP_Feature_Query`

This class is used for querying the registry. Some of its properties, like `categories`, `location`, `type`, etc. are used in querying the repository. Others, especially the callbacks, are used to further filter the results after they are retrieved.

#### `WP_Feature_Query::query(WP_Feature_Search_Query $search)`

This method is used to query by either keyword search, or semantically if embeddings are available.

### `WP_Feature`

This is the main object for features, as outlined above as the `type WP_Feature`. Besides its properties, its main method is a `run` method that executes the feature given a context.

#### `WP_Feature::run(array $context)`

This method is used to execute the feature. It accepts an `context` parameter, which is validated against the feature's `input_schema`. The context is then passed to the feature's `callback` for execution. Before returning the result, the output is validated against the feature's `output_schema` and a `WP_Error` is returned if it doesn't validate.

## Future Considerations

### Tracking Client Registry on the Server

We should consider whether the client registry should be centrally registered on the server. This would register only the static properties. This may help with keeping the client javascript smaller and more efficient, and avoids having to pass client features over the network to the server, since they're readily available there.

With this, we may also explore a unified way of registering features through something like a `features.json` file that can define all the static feature data for both client and server features. This can have two types of references for the callbacks:

```json
{
  ...
  "client_callback": "path/to/js/callback.js", // expect an exported function that can be dynamically imported
  "server_callback": "path/to/php/callback.php" // expect a WP_Feature_Callback class
}
```

### Limiting Client Size Registry

This Features API may grow to be quite large, so we should consider some mechanisms to limit the size of the registry on the client. Some initial thoughts are:

- registering callbacks only on the client, since the static feature properties are already registered on the server
- lazy loading features on demand

### Embedding Features for Semantic Search

Once embedding and vector support in WordPress environments arrive, we should leverage this for better retrieval of relevant features. This is why we propose a database repository for the feature metadata.

### Grouping Features

Features can be grouped into a "feature set". This can be used to group features that are related in some way and share common configuration.

```php
// Group features
wp_register_feature_group("woocommerce", [
    "id" => "woocommerce",
    "name" => "WooCommerce",
    "description" => "Manage WooCommerce tools and resources.",
    "permissions" => ["manage_woocommerce"],
    "categories" => ["woocommerce", "products"],
    "features" => [
      "woocommerce/product/report",
      "woocommerce/product/update_price",
    ],
]);
```

This provides more availability to scope features too:

```tsx
const wooFeatures = wp.features.get({
	group: 'woocommerce',
});
```

### Versioning / Deprecating Features

If features are plentiful and being iterated on, it may be worth introducing versions of the same feature:

```php
// Version-aware feature registration
wp_register_feature("woocommerce/product/report", [
    "id" => "woocommerce/product/report",
    "version" => "2.0.0", // Current version
    "deprecated_version" => "3.0.0", // Optional: version when this will be removed
    "since_version" => "1.0.0", // When this feature was introduced
    "alternatives" => ["woocommerce/product/analytics"], // Suggested replacement features
    "deprecated_message" => "Use woocommerce/product/analytics instead for enhanced reporting features",

    // Support for multiple versions of the same feature
    "versions" => [
        "1.0.0" => [
            "input_schema" => [
                "productId" => ["type" => "string"],
            ],
            "output_schema" => [
                "sales" => ["type" => "number"],
            ],
            "callback" => function($input) {
                // Legacy implementation
            }
        ],
        "2.0.0" => [
            "input_schema" => [
                "productId" => ["type" => "string"],
                "dateRange" => ["type" => "string", "enum" => ["day", "week", "month"]]
            ],
            "output_schema" => [
                "sales" => ["type" => "number"],
                "trends" => ["type" => "object"]
            ],
            "callback" => function($input) {
                // Current implementation
            }
        ]
    ]
]);
```

Use and handle versioning:

```tsx
// Request specific version
const feature = wp.features.find('woocommerce/product/report', {
	version: '1.0.0', // Falls back to latest if not found
});

// Check if feature is deprecated
if (feature.isDeprecated()) {
	console.warn(
		`Feature ${feature.id} is deprecated. ${feature.deprecated_message}`
	);
}

// Get suggested alternatives
const alternatives = feature.getAlternatives();
```

Notify or quiet deprecation:

```php
// Notify when deprecated feature is used
add_action('wp_feature_deprecated_run', function(
    string $feature_id,
    string $version_used,
    string $deprecated_version,
    array $alternatives
) {
    _deprecated_function(
        sprintf('Feature: %s (v%s)', $feature_id, $version_used),
        $deprecated_version,
        sprintf('Use one of: %s', implode(', ', $alternatives))
    );
});

// Filter to modify deprecation behavior
add_filter('wp_feature_handle_deprecated', function(
    bool $should_run,
    string $version_used,
    WP_Feature $feature,
) {
    // Optionally prevent deprecated features from running
    if ($feature->is("some_feature") && $version_used < '2.0.0') {
        return false;
    }
    return $should_run;
}, 10, 3);
```

Add version information to REST response headers:

```php
// Add version information to REST response headers
add_filter('wp_feature_rest_response', function($response, $feature) {
    $response->header('X-WP-Feature-Version', $feature->version);
    if ($feature->isDeprecated()) {
        $response->header('X-WP-Feature-Deprecated', 'true');
        $response->header('X-WP-Feature-Alternatives', implode(',', $feature->alternatives));
    }
    return $response;
}, 10, 2);
```

## WP REST Aliases

The REST API already shares a lot of the same functionality we are trying to expose in the Feature API. We can leverage this to some extent to avoid duplicating functionality by defining aliases.

If a feature is registered with a `rest_alias` that corresponds to a REST route, then the feature will use any properties of the rest route as its own properties, such as the callback, args, schema, and permissions.

```php
wp_register_feature("posts", [
    "id" => "posts",
    "is_rest_alias" => true,
    "description" => "Query and get WordPress post objects.",
	"type" => "resource",
]);
```
