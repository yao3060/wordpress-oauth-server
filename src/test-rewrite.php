<?php
/**
 * Test rewrite rules for OAuth2 endpoints
 */

// Load WordPress
require_once __DIR__ . '/wp-load.php';

echo "Testing OAuth2 rewrite rules...\n\n";

// Get current rewrite rules
$rewrite_rules = get_option('rewrite_rules');

echo "Looking for oauth2 rules:\n";
foreach ($rewrite_rules as $pattern => $replacement) {
    if (strpos($pattern, 'oauth2') !== false) {
        echo "Pattern: $pattern\n";
        echo "Replacement: $replacement\n\n";
    }
}

// Test URL parsing
$test_urls = [
    '/oauth2/authorize',
    '/oauth2/token',
    '/oauth2/userinfo'
];

foreach ($test_urls as $url) {
    echo "Testing URL: $url\n";
    
    // Simulate the URL
    $_SERVER['REQUEST_URI'] = $url;
    
    // Parse the URL
    $wp_rewrite = new WP_Rewrite();
    $query = $wp_rewrite->rewrite_rules();
    
    echo "Query vars would be:\n";
    
    // Check what query vars would be set
    foreach ($query as $pattern => $replacement) {
        if (preg_match("#^$pattern#", ltrim($url, '/'))) {
            echo "  Matched pattern: $pattern\n";
            echo "  Replacement: $replacement\n";
            
            // Parse the replacement
            parse_str($replacement, $vars);
            print_r($vars);
        }
    }
    
    echo "\n";
}

echo "Done.\n";
?>
