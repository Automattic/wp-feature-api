This documentation provides a comprehensive guide to the WordPress Features API, a system designed to register, discover, and execute server-side and client-side functionality within WordPress, primarily intended for use by AI agents and other programmatic systems.

**Table of Contents:**

1.  **Introduction & Overview**
    *   What is the Features API?
    *   Core Concepts (Features, Tools, Resources)
    *   Goals and Benefits
    *   Relationship with MCP (Multi-Capability Protocol)
2.  **Getting Started**
    *   Installation
    *   Basic Usage Example
3.  **Registering Features (`wp_register_feature`)**
    *   Function Signature
    *   Parameters Explained (`id`, `name`, `description`, `type`, `callback`, `input_schema`, `output_schema`, `permission_callback`, `is_eligible`, `categories`, `meta`, `rest_alias`)
    *   Code Examples:
        *   Registering a Simple Resource
        *   Registering a Simple Tool
        *   Registering a Feature using `rest_alias`
        *   Registering a Feature with Schemas
        *   Registering a Feature with Eligibility Checks
        *   Registering a Feature with Permissions
4.  **Using Features (`wp_find_feature`, `wp_get_features`)**
    *   Finding a Specific Feature (`wp_find_feature`)
    *   Querying Multiple Features (`wp_get_features`)
    *   The `WP_Feature_Query` Class
        *   Filtering by Type
        *   Filtering by Category
        *   Filtering by Location (Server/Client)
        *   Filtering by Schema Fields
        *   Searching by Keyword
    *   Executing a Feature (`$feature->call()`)
5.  **REST API Endpoints**
    *   Overview
    *   Authentication
    *   Endpoints:
        *   `GET /wp/v2/features` (List Features)
        *   `GET /wp/v2/features/categories` (List Categories)
        *   `GET /wp/v2/features/categories/{id}` (Get Category)
        *   `GET /wp/v2/features/{feature-id}` (Get Feature)
        *   `POST /wp/v2/features/{feature-id}/run` (Run Tool Feature)
        *   `GET /wp/v2/features/{feature-id}/run` (Run Resource Feature)
    *   Request/Response Examples
6.  **Categories**
    *   Purpose and Usage
    *   Defining Categories
    *   Querying by Category
7.  **Advanced Topics**
    *   Repositories (`WP_Feature_Repository_Interface`)
    *   Client-Side Features (Conceptual & Demo)
    *   Schema Adapters (`WP_Feature_Schema_Adapter`)
    *   Composability (Conceptual)
8.  **Extending & Contributing**
    *   Registering Features in Plugins/Themes
    *   Best Practices (Namespacing, Descriptions)
9.  **MCP Integration (Conceptual)**
    *   Relationship between Features API and MCP
    *   The MCP Adapter Concept
