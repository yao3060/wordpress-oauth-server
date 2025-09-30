<?php

class OAuth2_Admin {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'handle_admin_actions'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_options_page(
            __('OAuth2 PKCE Settings', 'wp-oauth2-pkce'),
            __('OAuth2 PKCE', 'wp-oauth2-pkce'),
            'manage_options',
            'oauth2-pkce',
            array($this, 'admin_page')
        );
    }
    
    /**
     * Handle admin actions
     */
    public function handle_admin_actions() {
        if (!isset($_GET['page']) || $_GET['page'] !== 'oauth2-pkce') {
            return;
        }
        
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // Handle client creation
        if (isset($_POST['create_client']) && wp_verify_nonce($_POST['oauth2_nonce'], 'create_client')) {
            $this->create_client();
        }
        
        // Handle client deletion
        if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['client_id'])) {
            if (wp_verify_nonce($_GET['_wpnonce'], 'delete_client_' . $_GET['client_id'])) {
                $this->delete_client($_GET['client_id']);
            }
        }
    }
    
    /**
     * Create new OAuth2 client
     */
    private function create_client() {
        global $wpdb;
        
        $name = sanitize_text_field($_POST['client_name']);
        $redirect_uri = esc_url_raw($_POST['redirect_uri']);
        $is_confidential = isset($_POST['is_confidential']) ? 1 : 0;
        
        if (empty($name) || empty($redirect_uri)) {
            add_settings_error('oauth2_pkce', 'missing_fields', __('Client name and redirect URI are required.', 'wp-oauth2-pkce'));
            return;
        }
        
        $client_id = wp_generate_password(32, false);
        $client_secret = $is_confidential ? wp_generate_password(64, false) : null;
        
        $table = $wpdb->prefix . 'oauth2_clients';
        $result = $wpdb->insert($table, [
            'id' => $client_id,
            'name' => $name,
            'secret' => $client_secret ? password_hash($client_secret, PASSWORD_DEFAULT) : null,
            'redirect_uri' => $redirect_uri,
            'grant_types' => 'authorization_code,refresh_token',
            'scope' => 'read,write,profile',
            'user_id' => get_current_user_id(),
            'is_confidential' => $is_confidential
        ]);
        
        if ($result) {
            // Store the plain text secret temporarily for display
            set_transient('oauth2_new_client_' . $client_id, [
                'client_id' => $client_id,
                'client_secret' => $client_secret,
                'name' => $name
            ], 300); // 5 minutes
            
            add_settings_error('oauth2_pkce', 'client_created', __('OAuth2 client created successfully!', 'wp-oauth2-pkce'), 'success');
        } else {
            add_settings_error('oauth2_pkce', 'client_error', __('Failed to create OAuth2 client.', 'wp-oauth2-pkce'));
        }
    }
    
    /**
     * Delete OAuth2 client
     */
    private function delete_client($client_id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'oauth2_clients';
        $result = $wpdb->delete($table, ['id' => $client_id]);
        
        if ($result) {
            add_settings_error('oauth2_pkce', 'client_deleted', __('OAuth2 client deleted successfully!', 'wp-oauth2-pkce'), 'success');
        } else {
            add_settings_error('oauth2_pkce', 'delete_error', __('Failed to delete OAuth2 client.', 'wp-oauth2-pkce'));
        }
    }
    
    /**
     * Get all clients
     */
    private function get_clients() {
        global $wpdb;
        
        $table = $wpdb->prefix . 'oauth2_clients';
        return $wpdb->get_results("SELECT * FROM $table ORDER BY created_at DESC");
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        if ($hook !== 'settings_page_oauth2-pkce') {
            return;
        }
        
        wp_enqueue_script('oauth2-admin', WP_OAUTH2_PKCE_PLUGIN_URL . 'assets/js/admin.js', ['jquery'], WP_OAUTH2_PKCE_VERSION, true);
        wp_enqueue_style('oauth2-admin', WP_OAUTH2_PKCE_PLUGIN_URL . 'assets/css/admin.css', [], WP_OAUTH2_PKCE_VERSION);
    }
    
    /**
     * Admin page content
     */
    public function admin_page() {
        $clients = $this->get_clients();
        $new_client = null;
        
        // Check for newly created client
        foreach ($clients as $client) {
            $transient_data = get_transient('oauth2_new_client_' . $client->id);
            if ($transient_data) {
                $new_client = $transient_data;
                delete_transient('oauth2_new_client_' . $client->id);
                break;
            }
        }
        ?>
        
        <div class="wrap">
            <h1><?php _e('OAuth2 PKCE Settings', 'wp-oauth2-pkce'); ?></h1>
            
            <?php settings_errors('oauth2_pkce'); ?>
            
            <?php if ($new_client): ?>
            <div class="notice notice-info">
                <h3><?php _e('New Client Created', 'wp-oauth2-pkce'); ?></h3>
                <p><strong><?php _e('Client ID:', 'wp-oauth2-pkce'); ?></strong> <code><?php echo esc_html($new_client['client_id']); ?></code></p>
                <?php if ($new_client['client_secret']): ?>
                <p><strong><?php _e('Client Secret:', 'wp-oauth2-pkce'); ?></strong> <code><?php echo esc_html($new_client['client_secret']); ?></code></p>
                <?php endif; ?>
                <p class="description"><?php _e('Please save these credentials securely. The client secret will not be shown again.', 'wp-oauth2-pkce'); ?></p>
            </div>
            <?php endif; ?>
            
            <div class="oauth2-info-box">
                <h3><?php _e('OAuth2 Endpoints', 'wp-oauth2-pkce'); ?></h3>
                <table class="widefat">
                    <tr>
                        <td><strong><?php _e('Authorization URL:', 'wp-oauth2-pkce'); ?></strong></td>
                        <td><code><?php echo esc_url(site_url('/oauth2/authorize')); ?></code></td>
                    </tr>
                    <tr>
                        <td><strong><?php _e('Token URL:', 'wp-oauth2-pkce'); ?></strong></td>
                        <td><code><?php echo esc_url(site_url('/oauth2/token')); ?></code></td>
                    </tr>
                    <tr>
                        <td><strong><?php _e('UserInfo URL:', 'wp-oauth2-pkce'); ?></strong></td>
                        <td><code><?php echo esc_url(site_url('/oauth2/userinfo')); ?></code></td>
                    </tr>
                </table>
            </div>
            
            <h2><?php _e('Create New OAuth2 Client', 'wp-oauth2-pkce'); ?></h2>
            
            <form method="post" class="oauth2-client-form">
                <?php wp_nonce_field('create_client', 'oauth2_nonce'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="client_name"><?php _e('Client Name', 'wp-oauth2-pkce'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="client_name" name="client_name" class="regular-text" required>
                            <p class="description"><?php _e('A human-readable name for this OAuth2 client.', 'wp-oauth2-pkce'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="redirect_uri"><?php _e('Redirect URI', 'wp-oauth2-pkce'); ?></label>
                        </th>
                        <td>
                            <input type="url" id="redirect_uri" name="redirect_uri" class="regular-text" required>
                            <p class="description"><?php _e('The URL where users will be redirected after authorization.', 'wp-oauth2-pkce'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="is_confidential"><?php _e('Client Type', 'wp-oauth2-pkce'); ?></label>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox" id="is_confidential" name="is_confidential" value="1">
                                <?php _e('Confidential Client', 'wp-oauth2-pkce'); ?>
                            </label>
                            <p class="description"><?php _e('Check this for server-side applications that can securely store a client secret. Leave unchecked for public clients (mobile apps, SPAs) using PKCE.', 'wp-oauth2-pkce'); ?></p>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(__('Create Client', 'wp-oauth2-pkce'), 'primary', 'create_client'); ?>
            </form>
            
            <h2><?php _e('Existing OAuth2 Clients', 'wp-oauth2-pkce'); ?></h2>
            
            <?php if (empty($clients)): ?>
                <p><?php _e('No OAuth2 clients found.', 'wp-oauth2-pkce'); ?></p>
            <?php else: ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Client Name', 'wp-oauth2-pkce'); ?></th>
                            <th><?php _e('Client ID', 'wp-oauth2-pkce'); ?></th>
                            <th><?php _e('Type', 'wp-oauth2-pkce'); ?></th>
                            <th><?php _e('Redirect URI', 'wp-oauth2-pkce'); ?></th>
                            <th><?php _e('Created', 'wp-oauth2-pkce'); ?></th>
                            <th><?php _e('Actions', 'wp-oauth2-pkce'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($clients as $client): ?>
                        <tr>
                            <td><strong><?php echo esc_html($client->name); ?></strong></td>
                            <td><code><?php echo esc_html($client->id); ?></code></td>
                            <td>
                                <?php if ($client->is_confidential): ?>
                                    <span class="dashicons dashicons-lock" title="<?php _e('Confidential', 'wp-oauth2-pkce'); ?>"></span>
                                    <?php _e('Confidential', 'wp-oauth2-pkce'); ?>
                                <?php else: ?>
                                    <span class="dashicons dashicons-unlock" title="<?php _e('Public (PKCE)', 'wp-oauth2-pkce'); ?>"></span>
                                    <?php _e('Public (PKCE)', 'wp-oauth2-pkce'); ?>
                                <?php endif; ?>
                            </td>
                            <td><?php echo esc_html($client->redirect_uri); ?></td>
                            <td><?php echo esc_html(mysql2date('Y/m/d g:i:s A', $client->created_at)); ?></td>
                            <td>
                                <a href="<?php echo wp_nonce_url(add_query_arg(['action' => 'delete', 'client_id' => $client->id]), 'delete_client_' . $client->id); ?>" 
                                   class="button button-small button-link-delete" 
                                   onclick="return confirm('<?php _e('Are you sure you want to delete this client?', 'wp-oauth2-pkce'); ?>')">
                                    <?php _e('Delete', 'wp-oauth2-pkce'); ?>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        
        <?php
    }
}
