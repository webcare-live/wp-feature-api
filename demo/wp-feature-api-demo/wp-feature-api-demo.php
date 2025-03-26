<?php
/**
 * Plugin Name: WordPress Feature API Demo
 * Plugin URI: https://github.com/Automattic/wp-feature-api
 * Description: Demo plugin showcasing the WordPress Features API.
 * Version: 0.1.0
 * Author: WordPress Contributors
 * Author URI: https://wordpress.org/
 * Text Domain: wp-feature-api-demo
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Requires at least: 6.0
 * Requires PHP: 7.2
 *
 * @package WordPress\Feature_API_Demo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once plugin_dir_path( __FILE__ ) . 'vendor/autoload.php';

use A8C\WpFeatureApiDemo\Main;

$main = new Main();
add_action( 'plugins_loaded', array( $main, 'init' ), 20 );
