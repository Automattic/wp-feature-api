# WordPress Feature API - Client SDK (@automattic/wp-feature-api)

This package provides the core client-side SDK for the WordPress Feature API. It allows client-side code running in the WordPress admin to register, discover, and execute features.

## Purpose

- Provides a `Feature` interface definition for client-side features to follow.
- Manages the client-side feature registry and data store via `@wordpress/data`.
- Exposes API functions for interacting with features:
  - `registerFeature(feature: Feature)`: Adds a client-side feature definition to the registry.
  - `executeFeature(featureId: string, args: any): Promise<unknown>`: Executes the callback of a registered client-side feature.
- Initializes the connection to the server-side feature registry via the REST API to discover features available on the server.

## Installation

```bash
npm install @automattic/wp-feature-api
```

## Usage

```js
import { registerFeature, executeFeature } from '@automattic/wp-feature-api';

// Register a feature
registerFeature({
  id: 'my-feature',
  title: 'My Feature',
  callback: async (args) => {
    // Feature implementation
    return 'result';
  }
});

// Execute a feature
const result = await executeFeature('my-feature', { someArg: 'value' });
```

## Build

This package is built using `@wordpress/scripts`. Run `npm run build` to build locally.
