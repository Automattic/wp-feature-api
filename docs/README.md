This documentation provides a comprehensive guide to the WordPress Feature API, a system designed to register, discover, and execute server-side and client-side functionality within WordPress, primarily intended for use by AI agents and other programmatic systems.

**Table of Contents:**

1. **Introduction & Overview**
    * What is the Feature API?
    * Core Concepts (Features, Tools, Resources)
    * Goals and Benefits
    * Relationship with MCP (Multi-Capability Protocol)
2. **Getting Started**
    * Installation
    * Basic Usage Example
3. **Registering Features (`wp_register_feature`)**
    * Function Signature
    * Parameters Explained (`id`, `name`, `description`, `type`, `callback`, `input_schema`, `output_schema`, `permission_callback`, `is_eligible`, `categories`, `meta`, `rest_alias`)
    * Code Examples:
        * Registering a Simple Resource
        * Registering a Simple Tool
        * Registering a Feature using `rest_alias`
        * Registering a Feature with Schemas
        * Registering a Feature with Eligibility Checks
        * Registering a Feature with Permissions
4. **Using Features (`wp_find_feature`, `wp_get_features`)**
    * Finding a Specific Feature (`wp_find_feature`)
    * Querying Multiple Features (`wp_get_features`)
    * The `WP_Feature_Query` Class
        * Filtering by Type
        * Filtering by Category
        * Filtering by Location (Server/Client)
        * Filtering by Schema Fields
        * Searching by Keyword
    * Executing a Feature (`$feature->call()`)
5. **REST API Endpoints**
    * Overview
    * Authentication
    * Endpoints:
        * `GET /wp/v2/features` (List Features)
        * `GET /wp/v2/features/categories` (List Categories)
        * `GET /wp/v2/features/categories/{id}` (Get Category)
        * `GET /wp/v2/features/{feature-id}` (Get Feature)
        * `POST /wp/v2/features/{feature-id}/run` (Run Tool Feature)
        * `GET /wp/v2/features/{feature-id}/run` (Run Resource Feature)
    * Request/Response Examples
6. **Categories**
    * Purpose and Usage
    * Defining Categories
    * Querying by Category
7. **Client-Side Features**
    * Registration Patterns
    * Execution
8. **Advanced Topics**
    * Repositories (`WP_Feature_Repository_Interface`)
    * Schema Adapters (`WP_Feature_Schema_Adapter`)
    * Composability (Conceptual)
9. **Extending & Contributing**
    * Registering Features in Plugins/Themes
    * Best Practices (Namespacing, Descriptions)
10. **MCP Integration (Conceptual)**
    * Relationship between Feature API and MCP
    * The MCP Adapter Concept
11. **Feature API Agent Demo**
    * Demo Overview
    * Key Concepts Illustrated
    * Technical Implementation
    * Architecture
    * Server-Side Features
    * Client-Side Implementation
    * Chat Interface
    * LLM Tool Use via Feature API
    * Agent Orchestration
    * Backend Proxy
12. **Release Process**
    * Distribution Formats
    * Automated Release Process
    * Creating a Release
    * WordPress Plugin ZIP
    * Composer Package
    * NPM Package
