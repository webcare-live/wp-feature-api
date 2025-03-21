# WordPress Feature API Demo

This directory contains a demo plugin for the WordPress Feature API.

## Enabling the Demo Plugin

There are two ways to use the demo plugin:

### Method 1: Using the WP_FEATURE_API_LOAD_DEMO constant

Add the following line to your `wp-config.php` file:

```php
define( 'WP_FEATURE_API_LOAD_DEMO', true );
```

This will automatically load the demo plugin when the main WP Feature API plugin is initialized.

### Method 2: Manual installation

1. Copy the `wp-feature-api-demo` directory to your WordPress plugins directory (`wp-content/plugins/`).
2. Activate the "WordPress Feature API Demo" plugin through the WordPress admin interface.

## Included Demo Features

The demo plugin registers several example features:

1. **Site Information** (Resource)
   - ID: `demo/site-info`
   - Get basic information about the WordPress site

2. **Create Post** (Tool)
   - ID: `demo/create-post`
   - Create a new post with specified title, content, and status

3. **Current User** (Resource)
   - ID: `demo/current-user`
   - Get information about the currently logged-in user

## Using as a Reference

This demo plugin serves as a reference implementation for:

- Registering both resource and tool features
- Implementing feature callbacks
- Defining input and output schemas
- Setting appropriate permissions
- Handling errors and returning responses

## Customization

You can use this demo plugin as a starting point for your own custom features.

See the README.md file in the `wp-feature-api-demo` directory for more detailed information about the demo plugin's features and usage. 
