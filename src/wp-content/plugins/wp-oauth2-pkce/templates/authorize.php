<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php _e('OAuth2 Authorization', 'wp-oauth2-pkce'); ?> - <?php bloginfo('name'); ?></title>
    <?php wp_head(); ?>
</head>
<body <?php body_class('oauth2-authorize'); ?>>

<div class="oauth2-container">
    <div class="oauth2-header">
        <h1><?php bloginfo('name'); ?></h1>
        <h2><?php _e('Authorization Request', 'wp-oauth2-pkce'); ?></h2>
    </div>
    
    <div class="oauth2-content">
        <div class="client-info">
            <h3><?php printf(__('"%s" would like to access your account', 'wp-oauth2-pkce'), esc_html($client->getName())); ?></h3>
            
            <div class="user-info">
                <?php $current_user = wp_get_current_user(); ?>
                <p><?php printf(__('Logged in as: %s', 'wp-oauth2-pkce'), '<strong>' . esc_html($current_user->display_name) . '</strong>'); ?></p>
            </div>
            
            <?php if (!empty($scopes)): ?>
            <div class="scopes">
                <h4><?php _e('This application is requesting access to:', 'wp-oauth2-pkce'); ?></h4>
                <ul class="scope-list">
                    <?php foreach ($scopes as $scope): ?>
                        <li class="scope-item">
                            <span class="scope-icon">✓</span>
                            <span class="scope-name"><?php echo esc_html(get_scope_description($scope->getIdentifier())); ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Debug: Show current request info -->
        <div style="background: #f0f0f0; padding: 10px; margin: 10px 0; font-size: 12px;">
            <strong>Debug Info:</strong><br>
            REQUEST_METHOD: <?php echo $_SERVER['REQUEST_METHOD']; ?><br>
            REQUEST_URI: <?php echo esc_html($_SERVER['REQUEST_URI']); ?><br>
            Form Action: <?php echo esc_html($_SERVER['REQUEST_URI']); ?>
        </div>
        
        <form method="POST" action="<?php echo esc_url($_SERVER['REQUEST_URI']); ?>" class="oauth2-form" id="oauth2-form">
            <?php wp_nonce_field('oauth2_authorize', 'oauth2_nonce'); ?>
            
            <!-- Add a simple test input -->
            <input type="hidden" name="test_field" value="test_value">
            
            <div class="form-actions">
                <input type="submit" name="approve" value="<?php _e('Authorize', 'wp-oauth2-pkce'); ?>" 
                       class="button button-primary button-large" />
                
                <input type="submit" name="deny" value="<?php _e('Deny', 'wp-oauth2-pkce'); ?>" 
                       class="button button-secondary button-large" />
            </div>
            
            <div class="oauth2-info">
                <p class="description">
                    <?php _e('By clicking "Authorize", you allow this application to access your account using the permissions listed above.', 'wp-oauth2-pkce'); ?>
                </p>
            </div>
        </form>
    </div>
</div>

<?php wp_footer(); ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('OAuth2 authorization page loaded');
    
    const form = document.querySelector('.oauth2-form');
    if (!form) {
        console.error('OAuth2 form not found');
        return;
    }
    
    console.log('OAuth2 form found:', form);
    
    // ONLY add form submit handler for debugging - no button handlers
    form.addEventListener('submit', function(e) {
        console.log('Form submit event triggered!');
        console.log('Form action:', this.action);
        console.log('Form method:', this.method);
        
        // Show which button was clicked
        const formData = new FormData(this);
        for (let [key, value] of formData.entries()) {
            console.log(key + ': ' + value);
        }
        
        // Don't prevent default - let form submit naturally
        console.log('Allowing form to submit...');
        return true;
    });
});
</script>

</body>
</html>

<?php
// Helper method to get scope descriptions
function get_scope_description($scope) {
    $descriptions = [
        'read' => __('Read your profile information', 'wp-oauth2-pkce'),
        'write' => __('Modify your profile information', 'wp-oauth2-pkce'),
        'profile' => __('Access your basic profile information', 'wp-oauth2-pkce')
    ];
    
    return isset($descriptions[$scope]) ? $descriptions[$scope] : $scope;
}
?>
