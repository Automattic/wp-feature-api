# WordPress Feature API - Client SDK (@wp-feature-api/client)

This package provides the core client-side SDK for the WordPress Feature API. It allows client-side code running in the WordPress admin to register, discover, and execute features.

## Purpose

- Provides a `Feature` interface definition for client-side features to follow.
- Manages the client-side feature registry and data store via `@wordpress/data`.
- Exposes API functions for interacting with features:
  - `registerFeature(feature: Feature)`: Adds a client-side feature definition to the registry.
  - `executeFeature(featureId: string, args: any): Promise<unknown>`: Executes the callback of a registered client-side feature.
- Initializes the connection to the server-side feature registry via the REST API to discover features available on the server.

## Installation

This package is intended to be used within in `wp-feature-api` monorepo or consumed by third-party WordPress plugins once the package is published.

Within the monorepo, dependencies are managed via the root `package.json` and npm workspaces.

## Build

This package is built using `@wordpress/scripts`. Run `npm run build` from the monorepo root.
