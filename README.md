# WordPress Feature API

The WordPress Feature API is a system for exposing WordPress functionality in a standardized, discoverable way for both server and client-side use. It's designed to make WordPress functionality accessible to AI systems (particularly LLMs) and developers through a unified registry of resources and tools.

## Key Features

-   **Unified Registry**: Central registry of features accessible from both client and server
-   **Standardized Format**: uses the MCP specification for the registry
-   **Reuses existing functionality**: existing WordPress functionality like REST endpoints are reused as features, making them more discoverable and easier to use by LLMs.
-   **Filterable**: Features can be filtered, categorized, and searched for more accurate feature matching.
-   **Extensible**: Easy to register new features from plugins and themes

## MCP

It relies heavily on the [MCP Specification](https://spec.modelcontextprotocol.io/specification/2025-03-26/), however it's tailored to the needs of WordPress. Since WordPress is by nature both the server and the client, the Feature API is designed to be used in both contexts, and leverage existing WordPress functionality.

Features may surface in an actual WP MCP server consumed by an external MCP client. The main difference is that the features are compatible across the server and client, allowing for WordPress to execute features itself on both the backend and frontend.

Note, this does not implement the MCP server and transport layer. However, the feature registry may be used by an MCP server in WordPress whenever available.

Features are not limited to LLM consumption and can be used throughout WordPress directly as a primitive API for generic functionality. Hence the more generic name of "Feature API" instead of "MCP API".

## Filtering

An important aspect of the Feature API is its ability to filter features manually and automatically. Since the success of an LLM agent will depend on the quality of tools that match the user's intent or current context within WordPress, the Feature API provides several mechanisms to ensure that the right tools are available at the right time.

Filtering can be done by:

-   Querying feature properties
-   Keyword search across name, description, and ID.
-   Categories
-   `is_eligible` boolean callback
-   Context matching for when we already have some context and want Features that can be fulfilled using that context.

## Getting Started

[Documentation coming soon]
