<?php
/**
 * Plugin Name: WordPress OAuth2 PKCE Server
 * Description: OAuth2 Server with PKCE support for WordPress using League OAuth2 Server
 * Version: 1.0.0
 * Author: YaoYingying
 * License: MIT
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('WP_OAUTH2_PKCE_VERSION', '1.0.0');
define('WP_OAUTH2_PKCE_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WP_OAUTH2_PKCE_PLUGIN_URL', plugin_dir_url(__FILE__));

// Autoload Composer dependencies
require_once ABSPATH . 'vendor/autoload.php';

// Include plugin files
require_once WP_OAUTH2_PKCE_PLUGIN_DIR . 'includes/class-wp-oauth2-pkce.php';
require_once WP_OAUTH2_PKCE_PLUGIN_DIR . 'includes/class-oauth2-server.php';
require_once WP_OAUTH2_PKCE_PLUGIN_DIR . 'includes/class-oauth2-entities.php';
require_once WP_OAUTH2_PKCE_PLUGIN_DIR . 'includes/class-oauth2-repositories.php';
require_once WP_OAUTH2_PKCE_PLUGIN_DIR . 'includes/class-oauth2-admin.php';

// Initialize the plugin
function wp_oauth2_pkce_init() {
    new WP_OAuth2_PKCE();
}
add_action('plugins_loaded', 'wp_oauth2_pkce_init');

// Activation hook
register_activation_hook(__FILE__, 'wp_oauth2_pkce_activate');
function wp_oauth2_pkce_activate() {
    // Initialize the plugin to add rewrite rules
    $plugin = new WP_OAuth2_PKCE();
    $plugin->init_rewrite_rules();
    
    WP_OAuth2_PKCE::create_tables();
    flush_rewrite_rules();
}

// Deactivation hook
register_deactivation_hook(__FILE__, 'wp_oauth2_pkce_deactivate');
function wp_oauth2_pkce_deactivate() {
    flush_rewrite_rules();
}
