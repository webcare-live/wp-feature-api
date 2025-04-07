# WordPress Feature API Demo Plugin

This demo plugin showcases how to use the WordPress Feature API to register and use features in your WordPress plugins or themes.

## Installation

1. In this directory, run `composer install && npm install && npm run build` to install dependencies and build the plugin.
2. Start a WordPress instance, you may use `npm run serve` to start through wp-env
3. Make sure you have the WordPress Feature API plugin installed and activated.
    1. `WP_FEATURE_API_LOAD_DEMO` must be true, you'll see a notice in the admin dashboard.

## Usage Examples

### Using the REST API

You can view the available features directly through the WordPress REST API:

```
GET: /wp-json/wp/v2/features
```

And can call a feature like this:

```
POST: /wp-json/wp/v2/features/[feature-id]
{
  "title": "My New Post",
  "content": "This is the content of my new post.",
  "status": "draft"
}
```

### Using Features Directly

Some REST funnctionality is already built in, so you can use those features directly.

```php
// Get a post
$site_info = wp_find_feature( 'resource-post' )->call([
	'id' => 1,
]);

// Create a post
$post_data = array(
    'title'   => 'My New Post',
    'content' => 'This is the content of my new post.',
    'status'  => 'draft',
);
$result = wp_find_feature( 'tool-posts' )->call( $post_data );

// Get current user information
$user_info = wp_find_feature("resource-users/me")->call();
```

## Custom Features

You can use this demo plugin as a template to create your own features. Simply add your feature registration and callback functions to the `RegisterFeatures.php` file or create new files to organize your features.

## Included Demo Features

This plugin registers some example features under `RegisterFeatures`. Some are plugin dependent, like for WooCommerce, so make sure you've installed and plugin dependencies if you want to use those features.

-   `resource-demo/woocommerce-info`: Get basic information about the WooCommerce configuration.
-   `resource-demo/site-info`: Get basic global site information.
