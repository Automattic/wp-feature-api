Copy-paste this file into any AI chat or share it with a human developer. It is a single, compressed reference that explains what the plugin does, how its pieces fit together, and how to extend it.

---

# WordPress Feature API Documentation

## 1. Title and Introduction

**WordPress Feature API: Exposing Site Capabilities for Developers and AI**

This document provides comprehensive technical documentation for the WordPress Feature API plugin. Its purpose is to guide developers on how to understand, use, and extend the Feature API to register and interact with WordPress functionality in a standardized, discoverable way, particularly for consumption by AI agents and other programmatic systems.

## 2. Summary Section

The WordPress Feature API provides a standardized way to register and discover distinct units of functionality within a WordPress site. These units, called "Features," represent either data retrieval actions (**Resources**) or data modification/creation actions (**Tools**). It acts as a central registry, accessible from both PHP (server-side) and JavaScript (client-side), making it easier for core, plugins, themes, and external systems (like AI agents) to understand and interact with the capabilities available on a specific site. Key components include server-side registration using `wp_register_feature`, client-side registration via `@automattic/wp-feature-api`, REST API endpoints for discovery and execution, and a flexible system for defining inputs, outputs, permissions, and eligibility for each feature.

## 3. Architecture Overview

The Feature API employs a registry pattern with distinct server-side and client-side components, unified through a REST API transport layer.

1. **Server-Side (PHP):**
    * `WP_Feature_Registry`: A singleton class managing all registered features.
    * `WP_Feature`: Represents a single feature (Resource or Tool) with properties like ID, name, description, type, schemas, callbacks, permissions, etc.
    * `WP_Feature_Repository_Interface`: Defines how features are stored (default: `WP_Feature_Repository_Memory` for in-memory storage per request).
    * `WP_Feature_Query`: Class for filtering and searching features.
    * `WP_REST_Feature_Controller`: Exposes features via the WP REST API (`/wp/v2/features`).
    * `wp_register_feature()`: Global function for easy feature registration.

2. **Client-Side (JavaScript - `@automattic/wp-feature-api`):**
    * **Data Store (`@wordpress/data`)**: Manages the state of registered features on the client.
    * **API Functions (`registerFeature`, `executeFeature`, `getRegisteredFeatures`, etc.)**: Provide the interface for interacting with the client-side registry.
    * **Synchronization:** Fetches server-side features via the REST API upon initialization and resolves them alongside client-registered features.

3. **Transport Layer (WP REST API):**
    * `GET /wp/v2/features`: Lists features (discoverability).
    * `GET /wp/v2/features/{feature-id}`: Gets details of a specific feature.
    * `POST|GET /wp/v2/features/{feature-id}/run`: Executes a feature (handles input validation, permissions, calling the feature's callback).

4. **Extensibility:**
    * Plugins/Themes register features using `wp_register_feature` (PHP) or `registerFeature` (JS).
    * Features can be Resources (data retrieval) or Tools (actions).
    * `rest_alias` allows exposing existing WP REST endpoints as features easily.

**Diagrammatic Flow (Conceptual):**

```
+---------------------+      +-------------------------+      +------------------------+
|   Client-Side JS    |----->| WP REST API             |<---->| Server-Side PHP        |
| (React UI, Agent)   |      | (/wp/v2/features)       |      | (Plugin/Theme/Core)    |
+---------------------+      +-------------------------+      +------------------------+
       |  ^                          |  ^                              |  ^
       |  |(uses client API)         |  |(discovery/execution)        |  |(uses PHP API)
       v  |                          v  |                              v  |
+---------------------+      +-------------------------+      +------------------------+
| @automattic/wp-feature-api |<----->| WP_REST_Feature_Controller|<---->| WP_Feature_Registry    |
| (Data Store, API fns)|      | (Handles REST Req/Res)  |      | (Manages Features)     |
+---------------------+      +-------------------------+      +------------------------+
                                                                       |
                                                                       v
                                                           +------------------------+
                                                           | WP_Feature_Repository  |
                                                           | (Storage: Memory/DB)   |
                                                           +------------------------+
```

## 4. Core Components

### Server-Side (PHP)

* **`WP_Feature_Registry`**: The central singleton registry. Accessed via `wp_feature_registry()`. Manages feature storage and retrieval.
* **`WP_Feature`**: Represents a single feature. Created internally when using `wp_register_feature`. Provides methods like `call()`, `is_eligible()`, `get_input_schema()`.
* **`wp_register_feature( array|WP_Feature $args )`**: The primary function to register a server-side feature.

    ```php
    <?php
    // In functions.php or your plugin

    // 1. Define the callback
    function myplugin_get_site_tagline() {
        return get_bloginfo( 'description' );
    }

    // 2. Register the feature on init
    add_action( 'init', function() {
        if ( ! function_exists( 'wp_register_feature' ) ) {
            return; // Feature API not active
        }

        wp_register_feature( array(
            'id'          => 'myplugin/site-tagline', // Unique namespaced ID
            'name'        => __( 'Get Site Tagline', 'my-plugin' ),
            'description' => __( 'Retrieves the tagline (description) of the site.', 'my-plugin' ),
            'type'        => \WP_Feature::TYPE_RESOURCE, // Data retrieval
            'callback'    => 'myplugin_get_site_tagline', // Function to execute
            'permission_callback' => '__return_true', // Publicly accessible
            'categories'  => array( 'my-plugin', 'site-info' ),
        ) );
    } );

    // 3. Use the feature later
    function myplugin_display_tagline() {
        $feature = wp_find_feature( 'resource-myplugin/site-tagline' ); // Note: 'resource-' prefix added automatically when finding

        if ( $feature && $feature->is_eligible() ) {
            $tagline = $feature->call();
            if ( ! is_wp_error( $tagline ) ) {
                echo 'Site Tagline: ' . esc_html( $tagline );
            }
        }
    }
    ```

### Client-Side (JavaScript - `@automattic/wp-feature-api`)

* **`store` (`@wordpress/data` store)**: Manages client-side feature state.
* **`registerFeature( feature: Feature )`**: Registers a client-side feature.
* **`executeFeature( featureId: string, args: any )`**: Executes a feature (client or server).
* **`getRegisteredFeatures()`**: Retrieves all currently known features (client + resolved server).

    ```javascript
    // In your client-side code (e.g., block editor script)
    import { registerFeature, executeFeature, getRegisteredFeatures } from '@automattic/wp-feature-api';
    import { store as editorStore } from '@wordpress/editor';
    import { dispatch } from '@wordpress/data';

    // 1. Define and register a client-side feature
    const saveCurrentPostFeature = {
      id: 'my-editor-features/save-post',
      name: 'Save Current Post',
      description: 'Triggers the save action for the post currently being edited.',
      type: 'tool',
      location: 'client', // Important!
      categories: ['my-editor', 'post-actions'],
      callback: () => {
        try {
          dispatch(editorStore).savePost();
          return { success: true };
        } catch (error) {
          console.error('Failed to save post:', error);
          return { success: false, error: error.message };
        }
      },
      output_schema: {
        type: 'object',
        properties: { success: { type: 'boolean' }, error: { type: 'string' } },
        required: ['success']
      }
    };

    registerFeature(saveCurrentPostFeature);

    // 2. Use a feature (client or server-side)
    async function updateAndSave(newTitle) {
      try {
        // Example: Use a hypothetical server-side or client-side feature to update title
        await executeFeature('editor/set-title', { title: newTitle });
        console.log('Title updated.');

        // Now execute the client-side save feature we registered
        const saveResult = await executeFeature('my-editor-features/save-post', {});
        console.log('Save result:', saveResult);

      } catch (error) {
        console.error('Error during update and save:', error);
      }

      // Discover available features
      const allFeatures = await getRegisteredFeatures();
      console.log('Available Features:', allFeatures);
    }
    ```

## 5. Extension Points

The primary way to extend the API is by registering your own Features, either as Resources (for data retrieval) or Tools (for actions).

### Registering Server-Side Features (PHP)

Use the `wp_register_feature()` function, typically hooked into the `init` action.

* **What it does:** Adds a PHP-based capability to the central registry, making it discoverable and executable via PHP calls (`$feature->call()`) or the REST API.
* **When to use:** For functionality implemented in PHP, interacting with WordPress core, database, or other server-side resources.

**Complete Code Example:**

```php
<?php
/**
 * Plugin Name: My Custom Features
 * Description: Registers custom features with the WP Feature API.
 * Version: 1.0
 * Author: Developer Name
 */

 // Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
 exit;
}

/**
 * Callback function for retrieving a specific option.
 *
 * @param array $context Input context containing 'option_name'.
 * @return mixed|WP_Error Option value on success, WP_Error on failure.
 */
function my_custom_features_get_option_callback( $context ) {
 if ( ! isset( $context['option_name'] ) ) {
  return new WP_Error( 'missing_option_name', __( 'Option name is required.', 'my-custom-features' ) );
 }

 $option_name = sanitize_key( $context['option_name'] );
 $value = get_option( $option_name );

 if ( false === $value ) {
        // Distinguish between 'option does not exist' and 'option is false'
  $all_options = wp_load_alloptions();
        if (!array_key_exists($option_name, $all_options)) {
             return new WP_Error( 'option_not_found', sprintf( __( 'Option "%s" not found.', 'my-custom-features' ), $option_name ), array( 'status' => 404 ) );
        }
 }

 return $value;
}

/**
 * Callback function for updating a specific option.
 *
 * @param array $context Input context containing 'option_name' and 'option_value'.
 * @return bool|WP_Error True on success, WP_Error on failure.
 */
function my_custom_features_update_option_callback( $context ) {
 if ( ! isset( $context['option_name'] ) || ! isset( $context['option_value'] ) ) {
  return new WP_Error( 'missing_params', __( 'Both option_name and option_value are required.', 'my-custom-features' ) );
 }

 $option_name = sanitize_key( $context['option_name'] );
 // Sanitize based on expected type - this is basic, complex types need more care
 $option_value = sanitize_text_field( $context['option_value'] );

 $success = update_option( $option_name, $option_value );

 if ( ! $success ) {
  // update_option returns false if value is the same or on failure
        // We might want to check if the value actually changed if needed
        return new WP_Error( 'update_failed', sprintf( __( 'Failed to update option "%s".', 'my-custom-features' ), $option_name ) );
 }

 return true;
}


/**
 * Registers the custom features.
 */
function my_custom_features_register() {
 // Ensure Feature API is available
 if ( ! function_exists( 'wp_register_feature' ) || ! class_exists( '\WP_Feature' ) ) {
  add_action( 'admin_notices', function() {
   echo '<div class="notice notice-error"><p>My Custom Features plugin requires the WordPress Feature API plugin to be active.</p></div>';
  });
  return;
 }

 // --- Get Option Feature (Resource) ---
 wp_register_feature( array(
  'id'          => 'my-custom-features/get-option',
  'name'        => __( 'Get WordPress Option', 'my-custom-features' ),
  'description' => __( 'Retrieves the value of a specific WordPress option from the options table.', 'my-custom-features' ),
  'type'        => \WP_Feature::TYPE_RESOURCE, // Read-only
  'callback'    => 'my_custom_features_get_option_callback',
  'permission_callback' => function() {
   // Only allow users who can manage options
   return current_user_can( 'manage_options' );
  },
  'input_schema' => array(
   'type' => 'object',
   'properties' => array(
    'option_name' => array(
     'type' => 'string',
     'description' => __( 'The name of the option to retrieve.', 'my-custom-features' ),
                    'required' => true, // Mark as required in description/docs
    ),
   ),
            // Formal required declaration for validation
            'required' => ['option_name'],
  ),
  'output_schema' => array(
   // Type can be mixed (string, int, bool, array, object)
   'type' => array('string', 'integer', 'boolean', 'array', 'object', 'null'),
   'description' => __( 'The value of the requested option.', 'my-custom-features' ),
  ),
  'categories'  => array( 'my-custom-features', 'options', 'site-settings' ),
 ) );

 // --- Update Option Feature (Tool) ---
    wp_register_feature( array(
  'id'          => 'my-custom-features/update-option',
  'name'        => __( 'Update WordPress Option', 'my-custom-features' ),
  'description' => __( 'Updates the value of a specific WordPress option in the options table.', 'my-custom-features' ),
  'type'        => \WP_Feature::TYPE_TOOL, // Action/Write
  'callback'    => 'my_custom_features_update_option_callback',
  'permission_callback' => function() {
   return current_user_can( 'manage_options' );
  },
  'input_schema' => array(
   'type' => 'object',
   'properties' => array(
    'option_name' => array(
     'type' => 'string',
     'description' => __( 'The name of the option to update.', 'my-custom-features' ),
                    'required' => true,
    ),
                'option_value' => array(
                    // Allow various primitive types for option value
     'type' => array('string', 'integer', 'boolean', 'null'),
     'description' => __( 'The new value for the option.', 'my-custom-features' ),
                    'required' => true,
    ),
   ),
            'required' => ['option_name', 'option_value'],
  ),
  'output_schema' => array(
   'type' => 'boolean',
   'description' => __( 'True if the option was successfully updated, false otherwise (or if value was unchanged).', 'my-custom-features' ),
  ),
  'categories'  => array( 'my-custom-features', 'options', 'site-settings' ),
 ) );
}
add_action( 'init', 'my_custom_features_register', 20 ); // Priority 20 to run after core features

```

**Explanation of Parameters and Options:**

* `id` (string, required): Unique, namespaced identifier (e.g., `my-plugin/feature-name`). Use lowercase alphanumeric, hyphens, slashes. *Do not* include the type prefix (`resource-` or `tool-`) here.
* `name` (string, required): Human-readable, translatable name.
* `description` (string, required): Detailed, translatable explanation for developers and AI. Explain purpose, inputs, outputs.
* `type` (string, required): `WP_Feature::TYPE_RESOURCE` (like GET) or `WP_Feature::TYPE_TOOL` (like POST/PUT/DELETE).
* `callback` (callable|null): The PHP function/method to execute. Receives one argument: `$context` (associative array of validated input). Should return the result or a `WP_Error`. Can be `null` if it's a `rest_alias` or handled purely by filters.
* `permission_callback` (callable|string[]|string|null): Determines if the current user can run the feature.
  * `callable`: Function returning `true`, `false`, or `WP_Error`. Receives `WP_User` and `WP_Feature` objects.
  * `string[]`: Array of WordPress capabilities (e.g., `['edit_posts', 'manage_options']`). Checks if the user has *all* capabilities.
  * `string`: A single WordPress capability (e.g., `'manage_options'`).
  * `null` (or omitted): Defaults to denying access. Use `__return_true` for public features (use with caution). Often inferred for `rest_alias`.
* `is_eligible` (callable|null): Determines if the feature is available *in the current context*. Returns `true` or `false`. Useful for checking if dependent plugins are active, settings are enabled, etc. Defaults to `true`.
* `input_schema` (array|null): JSON Schema definition of expected input `$context`. Used for validation by the REST API and documentation.
* `output_schema` (array|null): JSON Schema definition of the expected return value from the `callback`. Used for documentation and potentially response validation.
* `categories` (string[]|null): Array of category slugs for organization and filtering.
* `meta` (array|null): Associative array for arbitrary metadata.
* `rest_alias` (string|false): If set to a WP REST route (e.g., `/wp/v2/posts/(?P<id>[\d]+)`), the feature acts as an alias for that endpoint. Input/output schemas and permissions might be inferred. Defaults to `false`.

### Registering Client-Side Features (JavaScript)

Use the `registerFeature` function exported by the `@automattic/wp-feature-api` package.

* **What it does:** Adds a JavaScript-based capability to the client-side registry. The feature's `callback` function executes directly in the user's browser.
* **When to use:** For functionality that interacts directly with the browser environment, the DOM, or client-side WordPress components like the Block Editor or Site Editor.

**Complete Code Example:**

```javascript
/**
 * WordPress dependencies
 */
import { registerPlugin } from '@wordpress/plugins';
import { __ } from '@wordpress/i18n';
import { store as noticesStore } from '@wordpress/notices';
import { dispatch, select } from '@wordpress/data';

/**
 * Feature API Client dependencies
 */
import { registerFeature } from '@automattic/wp-feature-api'; // Assuming this is correctly imported/available

/**
 * Client-side Feature: Show Admin Notice
 */
const showAdminNoticeFeature = {
  id: 'my-client-features/show-notice', // Unique namespaced ID
  name: __('Show Admin Notice (Client-Side)', 'my-client-features'),
  description: __('Displays a notice message in the WordPress admin area.', 'my-client-features'),
  type: 'tool',      // It performs an action
  location: 'client', // Crucial: Indicates this runs in the browser
  categories: ['my-client-features', 'ui', 'notifications'],
  input_schema: {
    type: 'object',
    properties: {
      message: {
        type: 'string',
        description: __('The text content of the notice.', 'my-client-features'),
        required: true,
      },
      type: {
        type: 'string',
        description: __('Type of notice (success, info, warning, error).', 'my-client-features'),
        enum: ['success', 'info', 'warning', 'error'],
        default: 'info',
      },
      isDismissible: {
        type: 'boolean',
        description: __('Whether the notice can be dismissed by the user.', 'my-client-features'),
        default: true,
      },
      id: {
        type: 'string',
        description: __('Optional unique ID for the notice (allows programmatic removal).', 'my-client-features'),
      }
    },
    required: ['message'],
  },
  output_schema: {
    type: 'object',
    properties: {
      success: { type: 'boolean' },
      noticeId: { type: 'string', description: 'The generated or provided notice ID.' },
    },
    required: ['success', 'noticeId'],
  },
  // The actual JavaScript function to execute
  callback: (args) => {
    const { message, type = 'info', isDismissible = true, id } = args;

    if (typeof message !== 'string' || message.trim() === '') {
      throw new Error('Notice message cannot be empty.');
    }

    // Use the @wordpress/notices store to create the notice
    try {
      // Generate a unique ID if one wasn't provided
      const noticeId = id || `client-feature-notice-${Date.now()}`;

      // Create the notice using the notices store dispatcher
      dispatch(noticesStore).createNotice(type, message, {
        id: noticeId,
        isDismissible: isDismissible,
        // Add other options if needed, e.g., actions
      });

      // Return success and the ID used
      return { success: true, noticeId: noticeId };

    } catch (error) {
      console.error('Failed to create admin notice:', error);
      throw new Error(`Failed to show notice: ${error instanceof Error ? error.message : String(error)}`);
    }
  },
  // Optional: Only make this feature available if the notices store exists
  is_eligible: () => {
     try {
       // Check if the notices store is available in the data registry
       return !!select(noticesStore);
     } catch (e) {
       return false; // Store not found
     }
   }
};


/**
 * Example of how to register this feature
 * This would typically run within a script enqueued for the WordPress admin
 */
function registerMyClientFeatures() {
 if (typeof registerFeature === 'function') {
   registerFeature(showAdminNoticeFeature);
        console.log('Registered client-side feature: show-notice');

        // Example Usage (e.g., in another part of your client-side code):
        /*
        import { executeFeature } from '@automattic/wp-feature-api';

        async function triggerNotice() {
          try {
            const result = await executeFeature('my-client-features/show-notice', {
              message: 'This is a success notice from a client feature!',
              type: 'success'
            });
            console.log('Notice creation result:', result);
          } catch (error) {
            console.error('Error triggering notice:', error);
          }
        }
        // Call triggerNotice() when needed, e.g., after an action
        */

 } else {
  console.error('Feature API client `registerFeature` not available.');
 }
}

// You might register this within a WordPress plugin initialization
// registerPlugin('my-client-features-registration', { render: () => { registerMyClientFeatures(); return null; } });
// Or simply call it when your script loads:
registerMyClientFeatures();

```

**Explanation of Parameters and Options:**

* **`id`, `name`, `description`, `type`, `categories`, `input_schema`, `output_schema`, `meta`**: Same meaning as server-side features.
* **`location`** (string, required): **Must be set to `'client'`**. This tells the registry the callback is JavaScript and executes browser-side.
* **`callback`** (function, required): The JavaScript function to execute. It receives the `args` object based on the `input_schema`. It should return the result or throw an error.
* **`is_eligible`** (function|null): Optional JavaScript function returning `true` or `false`. Can check browser context, DOM elements, or client-side state (e.g., using `@wordpress/data` selectors like in the example).
* **`icon`** (any): Optional. Can be a Dashicon slug (string) or a React SVG component for use in UI integrations like the Command Palette.

## 6. Advanced Examples

These examples showcase how to integrate the Feature API with other WordPress functionalities or plugins.

### Example 1: Generating WooCommerce Data (using WooCommerce Smooth Generator)

This requires the WooCommerce and WooCommerce Smooth Generator plugins to be active. It registers *tools* to generate sample data.

```php
/**
 * Code Snippet: Register WooCommerce Smooth Generator features with the Feature API.
 *
 * IMPORTANT: Requires WordPress, WooCommerce, Feature API plugin, and
 * WooCommerce Smooth Generator plugin to be installed and active.
 * Deactivate this snippet if Smooth Generator adds native support later.
 */

// --- Permission & Eligibility Callbacks ---
function wcgs_snippet_check_permission() {
 // Allow users who can manage WC or install plugins
 return current_user_can( 'install_plugins' ) || current_user_can( 'manage_woocommerce' );
}

function wcgs_snippet_check_eligibility() {
 // Check if WC and the specific Generator class exist
 return function_exists( 'WC' ) && class_exists( '\WC\SmoothGenerator\Generator\Product' );
}

// --- Callback Adapter Functions ---

/** Adapter for Product generator. */
function wcgs_snippet_run_product_generator( array $context ) {
 if ( ! class_exists( '\WC\SmoothGenerator\Generator\Product' ) ) return new \WP_Error( 'wcgs_missing_generator', 'Product generator class not found.' );
 $amount = $context['amount'] ?? 10;
 // Only pass allowed arguments to the batch function
 $args   = wp_array_slice_assoc( $context, array( 'type', 'use-existing-terms' ) );
 return \WC\SmoothGenerator\Generator\Product::batch( (int) $amount, $args );
}

// ... (Similar adapter functions for Order, Customer, Coupon, Term generators - see original snippet)
function wcgs_snippet_run_order_generator( array $context ) {
 if ( ! class_exists( '\WC\SmoothGenerator\Generator\Order' ) ) return new \WP_Error( 'wcgs_missing_generator', 'Order generator class not found.' );
 $amount = $context['amount'] ?? 10;
 $args   = wp_array_slice_assoc( $context, array( 'date-start', 'date-end', 'status', 'coupons', 'skip-order-attribution' ) );
 return \WC\SmoothGenerator\Generator\Order::batch( (int) $amount, $args );
}
function wcgs_snippet_run_customer_generator( array $context ) {
 if ( ! class_exists( '\WC\SmoothGenerator\Generator\Customer' ) ) return new \WP_Error( 'wcgs_missing_generator', 'Customer generator class not found.' );
 $amount = $context['amount'] ?? 10;
 $args   = wp_array_slice_assoc( $context, array( 'country', 'type' ) );
 return \WC\SmoothGenerator\Generator\Customer::batch( (int) $amount, $args );
}
function wcgs_snippet_run_coupon_generator( array $context ) {
 if ( ! class_exists( '\WC\SmoothGenerator\Generator\Coupon' ) ) return new \WP_Error( 'wcgs_missing_generator', 'Coupon generator class not found.' );
 $amount = $context['amount'] ?? 10;
 $args   = wp_array_slice_assoc( $context, array( 'min', 'max' ) );
 return \WC\SmoothGenerator\Generator\Coupon::batch( (int) $amount, $args );
}
function wcgs_snippet_run_term_generator( array $context ) {
 if ( ! class_exists( '\WC\SmoothGenerator\Generator\Term' ) ) return new \WP_Error( 'wcgs_missing_generator', 'Term generator class not found.' );
 $taxonomy = $context['taxonomy'] ?? null;
 $amount   = $context['amount'] ?? 10;
 $args     = wp_array_slice_assoc( $context, array( 'max-depth', 'parent' ) );
 if ( is_null( $taxonomy ) ) return new \WP_Error( 'missing_taxonomy', __( 'Taxonomy argument is required for generating terms.', 'wc-smooth-generator' ) );
 if ( ! taxonomy_exists( $taxonomy ) || ! in_array( $taxonomy, array( 'product_cat', 'product_tag' ), true ) ) return new \WP_Error( 'invalid_taxonomy', __( 'Invalid taxonomy provided. Use "product_cat" or "product_tag".', 'wc-smooth-generator' ) );
 return \WC\SmoothGenerator\Generator\Term::batch( (int) $amount, $taxonomy, $args );
}

/**
 * Main registration function hooked to 'init'.
 */
function wc_smooth_generator_register_features_snippet() {
 if ( ! function_exists( 'wp_register_feature' ) || ! class_exists( '\WP_Feature' ) || ! function_exists( 'WC' ) ) {
  return; // Exit if dependencies aren't met
 }

 $output_schema_ids = array(
  'type'        => 'array',
  'items'       => array( 'type' => 'integer' ),
  'description' => __( 'An array containing the IDs of the generated items.', 'wc-smooth-generator' ),
 );

 // --- Register Product Generator ---
 wp_register_feature( array(
  'id'                  => 'wc-smooth-generator/generate-products',
  'name'                => __( 'Generate WooCommerce Products', 'wc-smooth-generator' ),
  'description'         => __( 'Generates WooCommerce products (simple/variable). Specify amount, optionally type (simple/variable) and use-existing-terms (boolean).', 'wc-smooth-generator' ),
  'type'                => \WP_Feature::TYPE_TOOL,
  'callback'            => 'wcgs_snippet_run_product_generator',
  'permission_callback' => 'wcgs_snippet_check_permission',
  'is_eligible'         => 'wcgs_snippet_check_eligibility',
  'input_schema'        => array(
   'type'       => 'object',
   'properties' => array(
    'amount'             => array( 'type' => 'integer', 'description' => __( 'Number of products.', 'wc-smooth-generator' ), 'default' => 10, 'minimum' => 1 ),
    'type'               => array( 'type' => 'string', 'description' => __( 'Type (simple/variable). Defaults to mix.', 'wc-smooth-generator' ), 'enum' => array( 'simple', 'variable' ) ),
    'use-existing-terms' => array( 'type' => 'boolean', 'description' => __( 'Only use existing categories/tags.', 'wc-smooth-generator' ), 'default' => false ),
   ),
   'required' => ['amount']
  ),
  'output_schema'       => $output_schema_ids,
  'categories'          => array( 'wc-smooth-generator', 'data-generation', 'woocommerce', 'testing', 'product' ),
 ) );

    // --- Register Order Generator ---
 wp_register_feature( array(
        'id'                  => 'wc-smooth-generator/generate-orders',
        'name'                => __( 'Generate WooCommerce Orders', 'wc-smooth-generator' ),
        'description'         => __( 'Generates WooCommerce orders. Specify amount, optionally date range (date-start/date-end YYYY-MM-DD), status (completed/processing/etc.), coupons (boolean), skip-order-attribution (boolean).', 'wc-smooth-generator' ),
        'type'                => \WP_Feature::TYPE_TOOL,
        'callback'            => 'wcgs_snippet_run_order_generator',
        'permission_callback' => 'wcgs_snippet_check_permission',
        'is_eligible'         => 'wcgs_snippet_check_eligibility', // Assumes Order generator exists if Product does
        'input_schema'        => array(
            'type'       => 'object',
            'properties' => array(
                'amount'                 => array( 'type' => 'integer', 'description' => __( 'Number of orders.', 'wc-smooth-generator' ), 'default' => 10, 'minimum' => 1 ),
                'date-start'             => array( 'type' => 'string', 'format' => 'date', 'description' => __( 'Start date (YYYY-MM-DD).', 'wc-smooth-generator' ) ),
                'date-end'               => array( 'type' => 'string', 'format' => 'date', 'description' => __( 'End date (YYYY-MM-DD).', 'wc-smooth-generator' ) ),
                'status'                 => array( 'type' => 'string', 'description' => __( 'Order status. Defaults to mix.', 'wc-smooth-generator' ), 'enum' => array( 'completed', 'processing', 'on-hold', 'failed' ) ),
                'coupons'                => array( 'type' => 'boolean', 'description' => __( 'Create and apply coupons.', 'wc-smooth-generator' ), 'default' => false ),
                'skip-order-attribution' => array( 'type' => 'boolean', 'description' => __( 'Skip order attribution meta.', 'wc-smooth-generator' ), 'default' => false ),
            ),
             'required' => ['amount']
        ),
        'output_schema'       => $output_schema_ids,
        'categories'          => array( 'wc-smooth-generator', 'data-generation', 'woocommerce', 'testing', 'order' ),
    ) );

 // ... (Registrations for Customer, Coupon, Term generators using respective adapter functions)

}
add_action( 'init', 'wc_smooth_generator_register_features_snippet', 20 );
```

### Example 2: Interacting with Code Snippets

This requires the Code Snippets plugin to be active. It exposes various snippet management actions as features.

```php
/**
 * Code Snippet: Register Code Snippets Core Features with the Feature API.
 * Description: Exposes core Code Snippets functionality (list, get, create, update, delete, etc.) as Features.
 */

if ( ! defined( 'CS_FEATURE_API_PREFIX' ) ) {
 define( 'CS_FEATURE_API_PREFIX', 'code-snippets/' ); // Use slash for better namespacing
}

// --- Permission & Eligibility ---
function cs_feature_api_permission_callback() {
 // Leverages Code Snippets' internal permission check
 return function_exists( '\Code_Snippets\code_snippets' ) && \Code_Snippets\code_snippets()->current_user_can();
}
function cs_feature_api_eligibility_callback() {
 // Check if Feature API and Code Snippets functions are available
 return function_exists( 'wp_register_feature' ) && class_exists( '\WP_Feature' ) && function_exists( '\Code_Snippets\get_snippets' );
}

// --- Helper ---
function cs_feature_api_prepare_snippet_for_response( $snippet ) {
 if ( ! $snippet instanceof \Code_Snippets\Snippet || ! $snippet->id ) return null;
 $data = $snippet->get_fields();
 // Add useful read-only fields
 $data['type']       = $snippet->type;
 $data['scope_name'] = $snippet->scope_name;
 $data['tags_list']  = $snippet->tags_list;
 $data['description'] = $data['desc'] ?? ''; // Rename desc to description
 unset($data['desc']);
 return $data;
}

// --- Adapter Functions ---
function cs_feature_api_list_snippets_callback( array $context ) {
 $ids      = isset( $context['ids'] ) && is_array( $context['ids'] ) ? array_map( 'intval', $context['ids'] ) : [];
 $network  = isset( $context['network'] ) ? (bool) $context['network'] : null; // Allow boolean input
 $snippets = \Code_Snippets\get_snippets( $ids, $network );
 return array_values( array_filter( array_map( 'cs_feature_api_prepare_snippet_for_response', $snippets ) ) );
}

function cs_feature_api_get_snippet_callback( array $context ) {
    if ( empty( $context['id'] ) || ! is_numeric( $context['id'] ) ) return new \WP_Error( 'missing_id', 'Snippet ID is required.' );
 $id      = intval( $context['id'] );
    $network = isset( $context['network'] ) ? (bool) $context['network'] : null;
 $snippet = \Code_Snippets\get_snippet( $id, $network );
 if ( !$snippet || !$snippet->id ) return new \WP_Error( 'not_found', 'Snippet not found.', [ 'status' => 404 ] );
 $prepared = cs_feature_api_prepare_snippet_for_response( $snippet );
 return $prepared ?? new \WP_Error( 'prepare_failed', 'Failed to prepare snippet data.' );
}

function cs_feature_api_create_snippet_callback( array $context ) {
 $snippet = new \Code_Snippets\Snippet( $context ); // Pass context directly
 $snippet->network = isset( $context['network'] ) ? (bool) $context['network'] : \Code_Snippets\DB::validate_network_param();
 $snippet->id = 0; // Ensure it's treated as new
 $result = \Code_Snippets\save_snippet( $snippet );
 if ( !$result || !$result->id ) return new \WP_Error( 'create_failed', 'Failed to create snippet.' );
 $prepared = cs_feature_api_prepare_snippet_for_response( $result );
 return $prepared ?? new \WP_Error( 'prepare_failed', 'Failed to prepare created snippet data.' );
}

function cs_feature_api_activate_snippet_callback( array $context ) {
    if ( empty( $context['id'] ) || ! is_numeric( $context['id'] ) ) return new \WP_Error( 'missing_id', 'Snippet ID is required.' );
    $id = intval($context['id']);
    $network = isset($context['network']) ? (bool) $context['network'] : null;
    $result = \Code_Snippets\activate_snippet( $id, $network );
    if ($result instanceof \Code_Snippets\Snippet) {
        $prepared = cs_feature_api_prepare_snippet_for_response($result);
        return $prepared ?? new \WP_Error('prepare_failed', 'Failed to prepare activated snippet.');
    } elseif (is_string($result)) { // Error message string
        return new \WP_Error('activation_failed', $result);
    }
    return new \WP_Error('activation_failed', 'Unknown error activating snippet.');
}

// ... (Similar adapter functions for update, delete, deactivate, clone, export - see original snippet)


/**
 * Main registration function hooked to 'init'.
 */
function cs_feature_api_register_features() {
 if ( ! cs_feature_api_eligibility_callback() ) return;

 // --- Define Schemas ---
 $snippet_output_schema = [ /* ... Full schema as defined in original snippet ... */ ];
    $snippet_id_input_schema = [ /* ... Schema with id and network(boolean) ... */ ];
 $snippet_ids_input_schema = [ /* ... Schema with ids(array) and network(boolean) ... */ ];
    $snippet_create_input_schema = [ /* ... Schema for create (no ID, boolean network) ... */ ];
    $snippet_update_input_schema = [ /* ... Schema for update (requires ID, boolean network) ... */ ];


 // --- Register Features ---

 // List Snippets (Resource)
 wp_register_feature( array(
  'id'                  => CS_FEATURE_API_PREFIX . 'list',
  'name'                => __( 'List Code Snippets', 'code-snippets' ),
  'description'         => __( 'Retrieves a list of code snippets. Can optionally filter by specific IDs.', 'code-snippets' ),
  'type'                => \WP_Feature::TYPE_RESOURCE,
  'callback'            => 'cs_feature_api_list_snippets_callback',
  'permission_callback' => 'cs_feature_api_permission_callback',
  'input_schema'        => [
   'type' => 'object',
   'properties' => [
                'ids'     => [ 'type' => 'array', 'items' => [ 'type' => 'integer' ] ],
    'network' => [ 'type' => 'boolean', 'description' => 'Retrieve network snippets? (Multisite only)' ],
   ],
  ],
  'output_schema'       => [ 'type'  => 'array', 'items' => $snippet_output_schema ],
  'categories'          => [ 'code-snippets', 'list' ],
 ) );

    // Get Snippet (Resource)
 wp_register_feature( array(
  'id'                  => CS_FEATURE_API_PREFIX . 'get',
  'name'                => __( 'Get Code Snippet', 'code-snippets' ),
  'description'         => __( 'Retrieves details for a specific code snippet by ID.', 'code-snippets' ),
  'type'                => \WP_Feature::TYPE_RESOURCE,
  'callback'            => 'cs_feature_api_get_snippet_callback',
  'permission_callback' => 'cs_feature_api_permission_callback',
  'input_schema'        => $snippet_id_input_schema,
  'output_schema'       => $snippet_output_schema,
  'categories'          => [ 'code-snippets', 'get' ],
 ) );

    // Create Snippet (Tool)
 wp_register_feature( array(
  'id'                  => CS_FEATURE_API_PREFIX . 'create',
  'name'                => __( 'Create Code Snippet', 'code-snippets' ),
  'description'         => __( 'Creates a new code snippet. Provide name, code, scope, and optionally description, tags, priority, network.', 'code-snippets' ),
  'type'                => \WP_Feature::TYPE_TOOL,
  'callback'            => 'cs_feature_api_create_snippet_callback',
  'permission_callback' => 'cs_feature_api_permission_callback',
  'input_schema'        => $snippet_create_input_schema,
  'output_schema'       => $snippet_output_schema,
  'categories'          => [ 'code-snippets', 'create' ],
 ) );

 // Activate Snippet (Tool)
    wp_register_feature( array(
        'id'                  => CS_FEATURE_API_PREFIX . 'activate',
        'name'                => __( 'Activate Code Snippet', 'code-snippets' ),
        'description'         => __( 'Activates a specific code snippet by ID.', 'code-snippets' ),
        'type'                => \WP_Feature::TYPE_TOOL,
        'callback'            => 'cs_feature_api_activate_snippet_callback',
        'permission_callback' => 'cs_feature_api_permission_callback',
        'input_schema'        => $snippet_id_input_schema,
        'output_schema'       => $snippet_output_schema, // Returns the updated snippet on success
        'categories'          => [ 'code-snippets', 'activation', 'update' ],
    ) );

 // ... (Registrations for update, delete, deactivate, clone, export - using respective callbacks and schemas)

}
add_action( 'init', 'cs_feature_api_register_features', 20 ); // Run after core features

```

## 7. API Reference

This section provides quick code snippets for common tasks using the Feature API.

**Recommended Tool:** Install the [Code Snippets](https://wordpress.org/plugins/code-snippets/) plugin to easily add and manage these PHP examples on your site.

### Registering a Server-Side Feature (PHP)

```php
<?php
// Add this to your theme's functions.php or a custom plugin / Code Snippet

/**
 * Registers a simple feature to get the site title.
 */
function my_api_register_site_title_feature() {
    if ( ! function_exists('wp_register_feature') || ! class_exists('\WP_Feature') ) return;

    wp_register_feature( array(
        'id'          => 'my-api/get-site-title', // Unique ID
        'name'        => __( 'Get Site Title', 'my-textdomain' ),
        'description' => __( 'Returns the current WordPress site title.', 'my-textdomain' ),
        'type'        => \WP_Feature::TYPE_RESOURCE, // Read-only
        'callback'    => 'get_bloginfo', // Use existing WP function
        'permission_callback' => '__return_true', // Public access
        'input_schema' => array( // Define expected input (even if empty)
             'type' => 'object',
             'properties' => array(
                 'name' => array( // get_bloginfo expects 'name' arg
                     'type' => 'string',
                     'default' => 'name',
                     'required' => false,
                 )
             )
        ),
        'output_schema' => array( // Define expected output
            'type' => 'string',
            'description' => __( 'The site title.', 'my-textdomain' ),
        ),
        'categories'  => array( 'my-api', 'site-info' ),
    ) );
}
// Hook into init, priority > 10 to ensure core features are registered first if needed
add_action( 'init', 'my_api_register_site_title_feature', 20 );
```

### Finding and Calling a Feature (PHP)

```php
<?php
// Add this where you need to use the feature

function my_api_use_site_title_feature() {
    if ( ! function_exists('wp_find_feature') ) return;

    // Full ID includes type prefix
    $feature = wp_find_feature( 'resource-my-api/get-site-title' );

    if ( $feature && $feature->is_eligible() ) {
        // Call the feature (context maps to callback args if needed)
        // For get_bloginfo, default context works for 'name'
        $site_title = $feature->call();

        if ( ! is_wp_error( $site_title ) ) {
            error_log( 'Site title from Feature API: ' . $site_title );
            // Output: Site title from Feature API: Your Site Name
        } else {
            error_log( 'Error calling feature: ' . $site_title->get_error_message() );
        }
    } else {
        error_log( 'Site title feature not found or not eligible.' );
    }

    // Example with context for a feature needing it
    // Assuming 'resource-my-custom-features/get-option' is registered
    $option_feature = wp_find_feature('resource-my-custom-features/get-option');
    if($option_feature) {
        $admin_email = $option_feature->call( ['option_name' => 'admin_email'] );
        if(!is_wp_error($admin_email)) {
             error_log( 'Admin email: ' . $admin_email );
        }
    }
}

// Example: Trigger this function somewhere, e.g., on an admin page load
// add_action('admin_init', 'my_api_use_site_title_feature');
```

### Querying Features (PHP)

```php
<?php
// Add this where you need to find multiple features

function my_api_find_all_my_features() {
    if ( ! function_exists('wp_get_features') ) return;

    // Get all features in the 'my-api' category
    $my_features = wp_get_features( array(
        'categories' => array( 'my-api' ),
    ) );

    if ( ! empty( $my_features ) ) {
        error_log( 'Found ' . count( $my_features ) . ' features in "my-api" category:' );
        foreach ( $my_features as $feature ) {
            error_log( '- ' . $feature->get_id() . ': ' . $feature->get_name() );
        }
    } else {
        error_log( 'No features found in "my-api" category.' );
    }

    // Get all registered 'tool' type features
    $all_tools = wp_get_features( array(
        'type' => array( \WP_Feature::TYPE_TOOL ),
    ) );
     error_log( 'Total tools registered: ' . count( $all_tools ) );

}

// Example: Trigger this function
// add_action('admin_init', 'my_api_find_all_my_features');
```

### Registering a Client-Side Feature (JS)

*(Requires `@automattic/wp-feature-api` package and its dependencies to be enqueued)*

```javascript
// Add this to your admin JavaScript file

import { registerFeature } from '@automattic/wp-feature-api';
import { dispatch } from '@wordpress/data';
import { store as noticesStore } from '@wordpress/notices';
import { __ } from '@wordpress/i18n'; // If using translations

function registerMyClientFeature() {
  if (typeof registerFeature !== 'function') {
    console.error('Feature API client `registerFeature` not available.');
    return;
  }

  const myNoticeFeature = {
    id: 'my-client/show-info-notice',
    name: __('Show Info Notice (Client)', 'my-textdomain'),
    description: __('Displays an informational notice in the admin area.', 'my-textdomain'),
    type: 'tool',
    location: 'client', // Mark as client-side
    callback: (args) => {
      const message = args?.message || 'Default client notice message.';
      try {
        dispatch(noticesStore).createNotice('info', message, {
          isDismissible: true,
          // You can add more options here
        });
        return { success: true };
      } catch (error) {
        console.error('Failed to show client notice:', error);
        return { success: false, error: error.message };
      }
    },
    input_schema: {
      type: 'object',
      properties: {
        message: { type: 'string', description: 'The message to display.' },
      },
      required: ['message'],
    },
    output_schema: {
      type: 'object',
      properties: {
        success: { type: 'boolean' },
        error: { type: 'string' },
      },
      required: ['success'],
    },
    categories: ['my-client', 'ui', 'notifications'],
    // Optional: check if notices store exists
    is_eligible: () => typeof dispatch === 'function' && !!noticesStore,
  };

  registerFeature(myNoticeFeature);
  console.log('Registered client feature: my-client/show-info-notice');
}

// Run registration when the script loads
registerMyClientFeature();
```

### Executing Any Feature (Client-Side JS)

*(Requires `@automattic/wp-feature-api` package)*

```javascript
// Add this to your admin JavaScript file where you need to execute a feature

import { executeFeature } from '@automattic/wp-feature-api';

async function useFeatures() {
  try {
    // Execute the server-side site title feature
    console.log('Executing server feature: resource-my-api/get-site-title...');
    const siteTitle = await executeFeature('resource-my-api/get-site-title', {}); // Empty args for this one
    console.log('Server Feature Result (Site Title):', siteTitle);

    // Execute the client-side notice feature registered above
    console.log('Executing client feature: my-client/show-info-notice...');
    const noticeResult = await executeFeature('my-client/show-info-notice', {
      message: 'Hello from executeFeature!',
    });
    console.log('Client Feature Result (Notice):', noticeResult);

    // Execute a feature that expects input and might fail
    console.log('Executing server feature: resource-my-custom-features/get-option...');
    const blogname = await executeFeature('resource-my-custom-features/get-option', {
       option_name: 'blogname' // Provide required input
    });
    console.log('Server Feature Result (Blogname):', blogname);

    // Example of handling potential error (e.g., option not found)
    const nonExistentOption = await executeFeature('resource-my-custom-features/get-option', {
       option_name: 'this_option_does_not_exist'
    });
     // Note: executeFeature throws on WP_Error, needs try/catch
     console.log('Result for non-existent option:', nonExistentOption); // This line might not be reached if it throws

  } catch (error) {
    console.error('Error executing feature:', error);
    // If the error object has more details (like from WP_Error)
    if(error?.message) console.error('Error Message:', error.message);
    if(error?.code) console.error('Error Code:', error.code);
    if(error?.data) console.error('Error Data:', error.data);
  }
}

// Call this function when needed, e.g., on a button click or page load
// useFeatures();
```

## 8. Configuration and Settings

The core Feature API plugin currently has minimal direct configuration.

* **Demo Plugin Loading:** The primary configuration is enabling the demo agent plugin (`demo/wp-feature-api-agent`). This is controlled by the `WP_FEATURE_API_LOAD_DEMO` PHP constant. Define it as `true` in your `wp-config.php` to load the demo.

    ```php
    // In wp-config.php
    define( 'WP_FEATURE_API_LOAD_DEMO', true );
    ```

* **Feature Repository:** The underlying storage mechanism can be changed using the `wp_feature_repository` filter. By default, it uses `WP_Feature_Repository_Memory`. Advanced users could filter this to use a custom database-backed repository.

    ```php
    <?php
    // Example: Using a hypothetical custom database repository
    // add_filter( 'wp_feature_repository', function( $default_repository ) {
    //    include_once( plugin_dir_path( __FILE__ ) . 'includes/class-my-custom-db-repository.php' );
    //    return new My_Custom_DB_Repository();
    // } );
    ```

* **Schema Adapters:** Filters `wp_feature_input_schema_adapter` and `wp_feature_output_schema_adapter` allow specifying a custom class (extending `WP_Feature_Schema_Adapter`) to transform schemas for specific consumers (like different LLM providers).
* **Demo Settings:** The included demo plugin (`wp-feature-api-agent`) adds its own settings page ("Settings" > "WP Feature Agent Demo") to configure the OpenAI API key required for its functionality. This demonstrates *how* settings could be added for features or related systems, but these settings are specific to the demo itself.

Programmatic access to configuration would typically involve standard WordPress methods like `get_option()` if features stored settings, or checking constants like `WP_FEATURE_API_LOAD_DEMO`.

## 9. Integration Guidelines

Integrating the Feature API into your theme or plugin primarily involves registering relevant features.

**Best Practices:**

1. **Namespace IDs:** Always prefix your feature IDs with a unique namespace related to your plugin or theme (e.g., `my-plugin/action`, `my-theme/get-setting`). This prevents collisions with core features or other plugins.
2. **Use `init` Hook:** Register features within a function hooked to the `init` action, ideally with a priority greater than 10 (e.g., 20) to ensure core features and potentially other foundational features are registered first.
3. **Check for Existence:** Before calling `wp_register_feature` or other API functions, check if they exist to ensure compatibility if the Feature API plugin is not active.

    ```php
    if ( ! function_exists( 'wp_register_feature' ) ) {
        return; // Feature API not active, do nothing
    }
    // ... proceed with registration ...
    ```

4. **Clear Descriptions:** Write comprehensive descriptions. Explain the purpose, expected inputs, and potential outputs clearly. This is vital for AI agent usability.
5. **Define Schemas:** Provide `input_schema` and `output_schema` using JSON Schema format whenever possible. This enables validation and helps consumers understand data structures.
6. **Implement Permissions:** Use `permission_callback` to restrict access appropriately based on user roles or capabilities (`current_user_can`). Avoid `__return_true` unless the feature is genuinely public.
7. **Use Eligibility Checks:** If your feature relies on specific conditions (plugin active, setting enabled), implement an `is_eligible` callback.
8. **Categorize:** Use the `categories` array to group your features logically. Include your namespace as a category.

**Complete Working Example (Plugin Integration):**

```php
<?php
/**
 * Plugin Name: My Plugin with Features
 * Description: Demonstrates registering features from a plugin.
 * Version: 1.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'MY_PLUGIN_FEATURES_PREFIX', 'my-plugin-features/' );

// --- Callback Functions ---

function my_plugin_features_get_post_count_callback( $context ) {
    $post_type = $context['post_type'] ?? 'post';
    if ( ! post_type_exists( $post_type ) ) {
        return new WP_Error( 'invalid_post_type', sprintf( 'Post type "%s" does not exist.', $post_type ) );
    }
    $counts = wp_count_posts( $post_type );
    return $counts->publish ?? 0; // Return count of published posts
}

function my_plugin_features_change_admin_color_callback( $context ) {
    if ( ! isset( $context['color'] ) ) {
        return new WP_Error( 'missing_color', 'Color scheme name is required.' );
    }
    $user_id = get_current_user_id();
    if ( ! $user_id ) {
        return new WP_Error( 'no_user', 'Could not get current user ID.' );
    }
    // Basic validation - check if color scheme exists
    global $_wp_admin_css_colors;
    if ( ! isset( $_wp_admin_css_colors[ $context['color'] ] ) ) {
         return new WP_Error( 'invalid_color_scheme', sprintf('Invalid color scheme: %s', $context['color'] ) );
    }

    update_user_meta( $user_id, 'admin_color', $context['color'] );
    return true;
}


// --- Registration Function ---

function my_plugin_features_register_all() {
    if ( ! function_exists( 'wp_register_feature' ) || ! class_exists( '\WP_Feature' ) ) {
        // Optionally add an admin notice here if needed
        return;
    }

    // Feature 1: Get Post Count (Resource)
    wp_register_feature( array(
        'id'          => MY_PLUGIN_FEATURES_PREFIX . 'get-post-count',
        'name'        => __( 'Get Published Post Count', 'my-plugin-features' ),
        'description' => __( 'Returns the number of published posts for a given post type (default: post).', 'my-plugin-features' ),
        'type'        => \WP_Feature::TYPE_RESOURCE,
        'callback'    => 'my_plugin_features_get_post_count_callback',
        'permission_callback' => function() { return current_user_can('edit_posts'); }, // Allow editors+
        'input_schema' => array(
            'type' => 'object',
            'properties' => array(
                'post_type' => array(
                    'type' => 'string',
                    'description' => __( 'The post type slug (e.g., post, page, product).', 'my-plugin-features' ),
                    'default' => 'post',
                 ),
            ),
        ),
        'output_schema' => array(
            'type' => 'integer',
            'description' => __( 'The number of published posts.', 'my-plugin-features' ),
        ),
        'categories'  => array( 'my-plugin-features', 'content', 'query' ),
    ) );

    // Feature 2: Change Admin Color Scheme (Tool)
    wp_register_feature( array(
        'id'          => MY_PLUGIN_FEATURES_PREFIX . 'set-admin-color',
        'name'        => __( 'Set Admin Color Scheme', 'my-plugin-features' ),
        'description' => __( 'Changes the admin color scheme for the current user.', 'my-plugin-features' ),
        'type'        => \WP_Feature::TYPE_TOOL,
        'callback'    => 'my_plugin_features_change_admin_color_callback',
        'permission_callback' => '__return_true', // Any logged-in user can change their own color scheme
        'input_schema' => array(
            'type' => 'object',
            'properties' => array(
                'color' => array(
                    'type' => 'string',
                    'description' => __( 'The name (slug) of the color scheme (e.g., fresh, blue, light, modern, sunrise, ectoplasm, midnight, ocean, coffee).', 'my-plugin-features' ),
                    'enum' => ['fresh', 'blue', 'light', 'modern', 'sunrise', 'ectoplasm', 'midnight', 'ocean', 'coffee'], // Add more if custom schemes exist
                    'required' => true,
                ),
            ),
             'required' => ['color'],
        ),
        'output_schema' => array(
            'type' => 'boolean',
            'description' => __( 'True on success.', 'my-plugin-features' ),
        ),
        'categories'  => array( 'my-plugin-features', 'user', 'settings', 'ui' ),
    ) );

}
add_action( 'init', 'my_plugin_features_register_all', 20 );
```

## 10. Troubleshooting

Common issues and solutions when working with the Feature API:

* **Issue:** `Call to undefined function wp_register_feature()` or `Class 'WP_Feature' not found`.
  * **Solution:** Ensure the "WordPress Feature API" plugin is installed and activated. If registering features in your own plugin, make sure your registration code runs *after* the Feature API plugin has loaded (e.g., use the `init` hook with a priority of 11 or higher, or check `function_exists('wp_register_feature')` before calling it).
* **Issue:** Feature not appearing when calling `wp_get_features()` or via the REST API (`/wp/v2/features`).
  * **Solution:**
        1. **Check Eligibility:** Verify the feature's `is_eligible` callback (if defined) returns `true` in the current context. Temporarily remove the `is_eligible` check to confirm if this is the cause.
        2. **Check Registration Hook/Priority:** Ensure `wp_register_feature` is called correctly on the `init` hook (or a later suitable hook) and that the Feature API itself has loaded first.
        3. **Check ID:** Double-check the feature `id` for typos and ensure it follows the naming conventions (lowercase alphanumeric, hyphens, slashes, no leading/trailing/double slashes).
* **Issue:** Calling `$feature->call()` or the REST `/run` endpoint returns a permission error (e.g., `rest_forbidden` or custom `WP_Error`).
  * **Solution:**
        1. **Verify `permission_callback`:** Check the logic within the feature's `permission_callback`. Ensure it correctly evaluates the current user's capabilities (`current_user_can`).
        2. **Check Authentication:** If using the REST API, ensure the request is properly authenticated (e.g., logged-in user cookie, Application Password, OAuth token depending on setup).
        3. **Check `rest_alias` Permissions:** If using `rest_alias`, the permissions are often inherited from the underlying REST endpoint. Verify the user has the required capabilities for that specific REST route.
* **Issue:** Calling `$feature->call()` or the REST `/run` endpoint returns an input validation error.
  * **Solution:**
        1. **Check `input_schema`:** Review the feature's `input_schema` definition.
        2. **Check Input Context:** Ensure the `$context` array passed to `$feature->call()` or the JSON body sent to the REST endpoint matches the structure and data types defined in the `input_schema`, including required fields.
        3. **Schema Adapter:** Be aware that the schema might be transformed by `WP_Feature_Schema_Adapter` (especially for OpenAI compatibility rules like making fields required/nullable). Check the adapter logic if customization is involved.
* **Issue:** Feature works via PHP (`$feature->call()`) but fails via REST API `/run` endpoint (or vice-versa).
  * **Solution:**
        1. **Permissions:** REST API calls go through standard WordPress REST authentication and permission checks *in addition* to the feature's `permission_callback`. Ensure both layers allow access.
        2. **Input Formatting:** Check how input is provided. PHP calls use an array `$context`. REST `POST` calls expect a JSON body. REST `GET` calls (for resources) might use URL query parameters, especially for `rest_alias`. Ensure the data matches the expected format for the method.
        3. **Serialization:** Data returned via REST is JSON encoded. Ensure your callback returns data that serializes correctly.
* **Issue:** Client-side feature registered with `registerFeature` doesn't work or isn't found.
  * **Solution:**
        1. **Check `location`:** Ensure the feature definition has `location: 'client'`.
        2. **Check JS Execution:** Verify the JavaScript file containing the `registerFeature` call is correctly enqueued and executing in the browser *after* the `@automattic/wp-feature-api` script has loaded. Check the browser console for errors.
        3. **Check `is_eligible` (Client):** If the client-side feature has an `is_eligible` function, ensure it returns `true` in the browser context where you expect it to be available.
* **Issue:** REST API returns unexpected schema (e.g., all fields required, `additionalProperties: false`).
  * **Solution:** This is likely due to the default `WP_Feature_Schema_Adapter` applying OpenAI compatibility rules. You can customize this behavior using the `wp_feature_input_schema_adapter` / `wp_feature_output_schema_adapter` filters if needed, or adjust your registered schema to anticipate these transformations.
