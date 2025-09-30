<?php
/**
 * Simple test script to verify plugin classes load without fatal errors
 */

echo "Testing OAuth2 PKCE Plugin Classes...\n";

try {
    // Load Composer autoloader
    require_once dirname(__FILE__) . '/../../../vendor/autoload.php';
    
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
    echo "✓ Redirect URIs: " . implode(', ', $client->getRedirectUri()) . "\n";
    
    // Test user entity
    $user = new UserEntity();
    $user->setIdentifier('123');
    $user->setUsername('testuser');
    $user->setEmail('test@example.com');
    
    echo "✓ UserEntity created: " . $user->getUsername() . " (" . $user->getEmail() . ")\n";
    
    // Test PKCE entity
    $authCode = new AuthCodeEntity();
    $authCode->setIdentifier('test-code');
    $authCode->setCodeChallenge('test-challenge');
    $authCode->setCodeChallengeMethod('S256');
    
    echo "✓ PKCE AuthCode entity created\n";
    echo "✓ Code challenge: " . $authCode->getCodeChallenge() . "\n";
    echo "✓ Challenge method: " . $authCode->getCodeChallengeMethod() . "\n";
    
    // Test access token entity
    $accessToken = new AccessTokenEntity();
    $accessToken->setIdentifier('test-token');
    
    echo "✓ AccessToken entity created\n";
    
    // Test refresh token entity
    $refreshToken = new RefreshTokenEntity();
    $refreshToken->setIdentifier('test-refresh-token');
    
    echo "✓ RefreshToken entity created\n";
    
    // Test scope entity
    $scope = new ScopeEntity();
    $scope->setIdentifier('read');
    
    echo "✓ Scope entity created: " . $scope->getIdentifier() . "\n";
    
    echo "\n🎉 All entity tests passed!\n";
    
    // Test repositories (without database operations)
    echo "\nTesting repositories...\n";
    
    $clientRepo = new ClientRepository();
    $userRepo = new UserRepository();
    $authCodeRepo = new AuthCodeRepository();
    $accessTokenRepo = new AccessTokenRepository();
    $refreshTokenRepo = new RefreshTokenRepository();
    $scopeRepo = new ScopeRepository();
    
    echo "✓ All repositories created successfully\n";
    
    // Test scope repository methods that don't need database
    $readScope = $scopeRepo->getScopeEntityByIdentifier('read');
    if ($readScope) {
        echo "✓ Scope 'read' found: " . $readScope->getIdentifier() . "\n";
    }
    
    $invalidScope = $scopeRepo->getScopeEntityByIdentifier('invalid');
    if ($invalidScope === null) {
        echo "✓ Invalid scope correctly returns null\n";
    }
    
    echo "\n🎉 All tests passed! Plugin classes are working correctly.\n";
    echo "\nNext steps:\n";
    echo "1. Activate the plugin in WordPress admin\n";
    echo "2. Go to Settings > OAuth2 PKCE to create clients\n";
    echo "3. Test the OAuth2 endpoints\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
} catch (Error $e) {
    echo "❌ Fatal Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
?>
