<?php
/**
 * Plugin Name: WordPress OAuth2 Server
 * Description: OAuth2 server for WordPress using League OAuth2 Server. Supports Authorization Code (PKCE), Client Credentials, and Refresh Token grants.
 * Version: 1.0.0
 * Author: YaoYingying
 * License: MIT
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('WP_OAUTH2_SERVER_VERSION', '1.0.0');
define('WP_OAUTH2_SERVER_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WP_OAUTH2_SERVER_PLUGIN_URL', plugin_dir_url(__FILE__));

// Back-compat constants for existing includes and handles
if (!defined('WP_OAUTH2_PKCE_VERSION')) {
    define('WP_OAUTH2_PKCE_VERSION', WP_OAUTH2_SERVER_VERSION);
}
if (!defined('WP_OAUTH2_PKCE_PLUGIN_DIR')) {
    define('WP_OAUTH2_PKCE_PLUGIN_DIR', WP_OAUTH2_SERVER_PLUGIN_DIR);
}
if (!defined('WP_OAUTH2_PKCE_PLUGIN_URL')) {
    define('WP_OAUTH2_PKCE_PLUGIN_URL', WP_OAUTH2_SERVER_PLUGIN_URL);
}

// Autoload Composer dependencies
require_once ABSPATH . 'vendor/autoload.php';

// Include plugin files
require_once WP_OAUTH2_SERVER_PLUGIN_DIR . 'includes/class-wp-oauth2-server.php';
require_once WP_OAUTH2_SERVER_PLUGIN_DIR . 'includes/class-oauth2-server.php';
require_once WP_OAUTH2_SERVER_PLUGIN_DIR . 'includes/class-oauth2-entities.php';
require_once WP_OAUTH2_SERVER_PLUGIN_DIR . 'includes/class-oauth2-repositories.php';
require_once WP_OAUTH2_SERVER_PLUGIN_DIR . 'includes/class-oauth2-admin.php';

// Initialize the plugin
function wp_oauth2_server_init() {
    new WP_OAuth2_Server();
}
add_action('plugins_loaded', 'wp_oauth2_server_init');

// Activation hook
register_activation_hook(__FILE__, 'wp_oauth2_server_activate');
function wp_oauth2_server_activate() {
    // Initialize the plugin to add rewrite rules
    $plugin = new WP_OAuth2_Server();
    $plugin->init_rewrite_rules();
    
    WP_OAuth2_Server::create_tables();
    flush_rewrite_rules();
}

// Deactivation hook
register_deactivation_hook(__FILE__, 'wp_oauth2_server_deactivate');
function wp_oauth2_server_deactivate() {
    flush_rewrite_rules();
}
