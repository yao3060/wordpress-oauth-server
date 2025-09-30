<?php

class WP_OAuth2_PKCE {
    
    private $oauth2_server;
    
    public function __construct() {
        add_action('init', array($this, 'init_rewrite_rules'));
        add_action('template_redirect', array($this, 'handle_oauth2_requests'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        
        // Initialize OAuth2 server
        $this->oauth2_server = new OAuth2_Server();
        
        // Initialize admin interface
        if (is_admin()) {
            new OAuth2_Admin();
        }
    }
    
    /**
     * Initialize rewrite rules for OAuth2 endpoints
     */
    public function init_rewrite_rules() {
        add_rewrite_rule('^oauth2/authorize/?$', 'index.php?oauth2_endpoint=authorize', 'top');
        add_rewrite_rule('^oauth2/token/?$', 'index.php?oauth2_endpoint=token', 'top');
        add_rewrite_rule('^oauth2/userinfo/?$', 'index.php?oauth2_endpoint=userinfo', 'top');
        
        add_rewrite_tag('%oauth2_endpoint%', '([^&]+)');
    }
    
    /**
     * Handle OAuth2 requests
     */
    public function handle_oauth2_requests() {
        $endpoint = get_query_var('oauth2_endpoint');
        
        // Debug: Log endpoint detection
        error_log('OAuth2: Endpoint detected = ' . $endpoint);
        error_log('OAuth2: REQUEST_URI = ' . $_SERVER['REQUEST_URI']);
        
        if (empty($endpoint)) {
            return;
        }
        
        switch ($endpoint) {
            case 'authorize':
                error_log('OAuth2: Calling handle_authorization_request');
                $this->oauth2_server->handle_authorization_request();
                break;
            case 'token':
                error_log('OAuth2: Calling handle_token_request');
                $this->oauth2_server->handle_token_request();
                break;
            case 'userinfo':
                error_log('OAuth2: Calling handle_userinfo_request');
                $this->oauth2_server->handle_userinfo_request();
                break;
        }
    }
    
    /**
     * Enqueue scripts and styles
     */
    public function enqueue_scripts() {
        if (get_query_var('oauth2_endpoint') === 'authorize') {
            wp_enqueue_script('wp-oauth2-pkce', WP_OAUTH2_PKCE_PLUGIN_URL . 'assets/js/oauth2-authorize.js', array('jquery'), WP_OAUTH2_PKCE_VERSION, true);
            wp_enqueue_style('wp-oauth2-pkce', WP_OAUTH2_PKCE_PLUGIN_URL . 'assets/css/oauth2-authorize.css', array(), WP_OAUTH2_PKCE_VERSION);
        }
    }
    
    /**
     * Create database tables
     */
    public static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // OAuth2 Clients table
        $clients_table = $wpdb->prefix . 'oauth2_clients';
        $clients_sql = "CREATE TABLE $clients_table (
            id varchar(80) NOT NULL,
            name varchar(255) NOT NULL,
            secret varchar(255) DEFAULT NULL,
            redirect_uri text NOT NULL,
            grant_types varchar(255) DEFAULT NULL,
            scope varchar(255) DEFAULT NULL,
            user_id bigint(20) DEFAULT NULL,
            is_confidential tinyint(1) DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        // OAuth2 Authorization Codes table
        $auth_codes_table = $wpdb->prefix . 'oauth2_authorization_codes';
        $auth_codes_sql = "CREATE TABLE $auth_codes_table (
            authorization_code varchar(40) NOT NULL,
            client_id varchar(80) NOT NULL,
            user_id bigint(20) NOT NULL,
            redirect_uri text NOT NULL,
            expires datetime NOT NULL,
            scope varchar(255) DEFAULT NULL,
            code_challenge varchar(128) DEFAULT NULL,
            code_challenge_method varchar(10) DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (authorization_code)
        ) $charset_collate;";
        
        // OAuth2 Access Tokens table
        $access_tokens_table = $wpdb->prefix . 'oauth2_access_tokens';
        $access_tokens_sql = "CREATE TABLE $access_tokens_table (
            access_token varchar(40) NOT NULL,
            client_id varchar(80) NOT NULL,
            user_id bigint(20) NOT NULL,
            expires datetime NOT NULL,
            scope varchar(255) DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (access_token)
        ) $charset_collate;";
        
        // OAuth2 Refresh Tokens table
        $refresh_tokens_table = $wpdb->prefix . 'oauth2_refresh_tokens';
        $refresh_tokens_sql = "CREATE TABLE $refresh_tokens_table (
            refresh_token varchar(40) NOT NULL,
            client_id varchar(80) NOT NULL,
            user_id bigint(20) NOT NULL,
            expires datetime NOT NULL,
            scope varchar(255) DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (refresh_token)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($clients_sql);
        dbDelta($auth_codes_sql);
        dbDelta($access_tokens_sql);
        dbDelta($refresh_tokens_sql);
    }
}
