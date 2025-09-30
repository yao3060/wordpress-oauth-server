<?php
/**
 * Simple test script to verify plugin loads without fatal errors
 */

// Simulate WordPress environment
define('ABSPATH', dirname(__FILE__) . '/../../../');
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);

// Load WordPress
require_once ABSPATH . 'wp-config.php';
require_once ABSPATH . 'wp-load.php';

echo "Testing OAuth2 PKCE Plugin...\n";

// Test if classes can be loaded
try {
    // Load Composer autoloader
    require_once ABSPATH . 'vendor/autoload.php';
    
    // Load plugin files
    require_once __DIR__ . '/includes/class-oauth2-entities.php';
    require_once __DIR__ . '/includes/class-oauth2-repositories.php';
    
    echo "✓ Plugin classes loaded successfully\n";
    
    // Test entity creation
    $client = new ClientEntity();
    $client->setIdentifier('test-client');
    $client->setName('Test Client');
    $client->setRedirectUri(['http://localhost:3000/callback']);
    $client->setConfidential(false);
    
    echo "✓ ClientEntity created: " . $client->getName() . "\n";
    echo "✓ Client is " . ($client->isConfidential() ? 'confidential' : 'public') . "\n";
    
    // Test repository creation
    $clientRepo = new ClientRepository();
    $authCodeRepo = new AuthCodeRepository();
    $accessTokenRepo = new AccessTokenRepository();
    
    echo "✓ Repositories created successfully\n";
    
    // Test PKCE entity
    $authCode = new AuthCodeEntity();
    $authCode->setIdentifier('test-code');
    $authCode->setCodeChallenge('test-challenge');
    $authCode->setCodeChallengeMethod('S256');
    
    echo "✓ PKCE AuthCode entity created\n";
    echo "✓ Code challenge: " . $authCode->getCodeChallenge() . "\n";
    echo "✓ Challenge method: " . $authCode->getCodeChallengeMethod() . "\n";
    
    echo "\n🎉 All tests passed! Plugin is ready to use.\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    exit(1);
} catch (Error $e) {
    echo "❌ Fatal Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    exit(1);
}
?>
