<?php
/**
 * Example PKCE OAuth2 Client Implementation
 * 
 * This example demonstrates how to implement a PKCE OAuth2 client
 * that works with the WordPress OAuth2 PKCE Server.
 */

class PKCEClient {
    
    private $client_id;
    private $redirect_uri;
    private $authorization_endpoint;
    private $token_endpoint;
    private $userinfo_endpoint;
    
    public function __construct($client_id, $redirect_uri, $base_url) {
        $this->client_id = $client_id;
        $this->redirect_uri = $redirect_uri;
        $this->authorization_endpoint = $base_url . '/oauth2/authorize';
        $this->token_endpoint = $base_url . '/oauth2/token';
        $this->userinfo_endpoint = $base_url . '/oauth2/userinfo';
    }
    
    /**
     * Generate PKCE code verifier and challenge
     */
    public function generatePKCE() {
        // Generate code verifier (43-128 characters)
        $code_verifier = $this->base64UrlEncode(random_bytes(32));
        
        // Generate code challenge using S256 method
        $code_challenge = $this->base64UrlEncode(hash('sha256', $code_verifier, true));
        
        return [
            'code_verifier' => $code_verifier,
            'code_challenge' => $code_challenge,
            'code_challenge_method' => 'S256'
        ];
    }
    
    /**
     * Get authorization URL
     */
    public function getAuthorizationUrl($scopes = ['read', 'profile'], $state = null) {
        $pkce = $this->generatePKCE();
        
        // Store code verifier in session for later use
        session_start();
        $_SESSION['oauth2_code_verifier'] = $pkce['code_verifier'];
        $_SESSION['oauth2_state'] = $state ?: bin2hex(random_bytes(16));
        
        $params = [
            'response_type' => 'code',
            'client_id' => $this->client_id,
            'redirect_uri' => $this->redirect_uri,
            'scope' => implode(' ', $scopes),
            'state' => $_SESSION['oauth2_state'],
            'code_challenge' => $pkce['code_challenge'],
            'code_challenge_method' => $pkce['code_challenge_method']
        ];
        
        return $this->authorization_endpoint . '?' . http_build_query($params);
    }
    
    /**
     * Exchange authorization code for access token
     */
    public function exchangeCodeForToken($authorization_code, $state = null) {
        session_start();
        
        // Verify state parameter
        if ($state && $state !== $_SESSION['oauth2_state']) {
            throw new Exception('Invalid state parameter');
        }
        
        // Get stored code verifier
        $code_verifier = $_SESSION['oauth2_code_verifier'] ?? null;
        if (!$code_verifier) {
            throw new Exception('Code verifier not found in session');
        }
        
        $params = [
            'grant_type' => 'authorization_code',
            'client_id' => $this->client_id,
            'code' => $authorization_code,
            'redirect_uri' => $this->redirect_uri,
            'code_verifier' => $code_verifier
        ];
        
        $response = $this->makeRequest($this->token_endpoint, $params);
        
        // Clean up session
        unset($_SESSION['oauth2_code_verifier']);
        unset($_SESSION['oauth2_state']);
        
        return $response;
    }
    
    /**
     * Get user information using access token
     */
    public function getUserInfo($access_token) {
        $headers = [
            'Authorization: Bearer ' . $access_token,
            'Accept: application/json'
        ];
        
        return $this->makeRequest($this->userinfo_endpoint, null, $headers);
    }
    
    /**
     * Refresh access token
     */
    public function refreshToken($refresh_token) {
        $params = [
            'grant_type' => 'refresh_token',
            'client_id' => $this->client_id,
            'refresh_token' => $refresh_token
        ];
        
        return $this->makeRequest($this->token_endpoint, $params);
    }
    
    /**
     * Make HTTP request
     */
    private function makeRequest($url, $params = null, $headers = []) {
        $ch = curl_init();
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false, // For development only
            CURLOPT_HTTPHEADER => array_merge([
                'Content-Type: application/x-www-form-urlencoded',
                'User-Agent: PKCE-Client/1.0'
            ], $headers)
        ]);
        
        if ($params) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        }
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new Exception('cURL error: ' . $error);
        }
        
        $decoded_response = json_decode($response, true);
        
        if ($http_code >= 400) {
            $error_msg = $decoded_response['error_description'] ?? $decoded_response['error'] ?? 'HTTP ' . $http_code;
            throw new Exception('API error: ' . $error_msg);
        }
        
        return $decoded_response;
    }
    
    /**
     * Base64 URL encode
     */
    private function base64UrlEncode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}

// Example usage
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    
    // Configuration
    $client_id = 'your_client_id_here';
    $redirect_uri = 'http://localhost:8080/callback.php';
    $wordpress_base_url = 'http://localhost:8080'; // Your WordPress site URL
    
    $client = new PKCEClient($client_id, $redirect_uri, $wordpress_base_url);
    
    // Handle callback
    if (isset($_GET['code'])) {
        try {
            // Exchange code for token
            $token_response = $client->exchangeCodeForToken($_GET['code'], $_GET['state'] ?? null);
            
            echo "<h2>Token Response:</h2>";
            echo "<pre>" . json_encode($token_response, JSON_PRETTY_PRINT) . "</pre>";
            
            // Get user info
            if (isset($token_response['access_token'])) {
                $user_info = $client->getUserInfo($token_response['access_token']);
                
                echo "<h2>User Info:</h2>";
                echo "<pre>" . json_encode($user_info, JSON_PRETTY_PRINT) . "</pre>";
            }
            
        } catch (Exception $e) {
            echo "<h2>Error:</h2>";
            echo "<p style='color: red;'>" . htmlspecialchars($e->getMessage()) . "</p>";
        }
        
    } else if (isset($_GET['error'])) {
        echo "<h2>Authorization Error:</h2>";
        echo "<p style='color: red;'>" . htmlspecialchars($_GET['error_description'] ?? $_GET['error']) . "</p>";
        
    } else {
        // Start authorization flow
        $auth_url = $client->getAuthorizationUrl(['read', 'profile']);
        
        echo "<h2>PKCE OAuth2 Client Example</h2>";
        echo "<p>Click the link below to start the OAuth2 authorization flow:</p>";
        echo "<p><a href='" . htmlspecialchars($auth_url) . "'>Authorize with WordPress</a></p>";
        
        echo "<h3>Configuration:</h3>";
        echo "<ul>";
        echo "<li><strong>Client ID:</strong> " . htmlspecialchars($client_id) . "</li>";
        echo "<li><strong>Redirect URI:</strong> " . htmlspecialchars($redirect_uri) . "</li>";
        echo "<li><strong>WordPress URL:</strong> " . htmlspecialchars($wordpress_base_url) . "</li>";
        echo "</ul>";
        
        echo "<h3>Instructions:</h3>";
        echo "<ol>";
        echo "<li>Create an OAuth2 client in your WordPress admin panel</li>";
        echo "<li>Set the client as 'Public' (for PKCE support)</li>";
        echo "<li>Set the redirect URI to match the one above</li>";
        echo "<li>Update the \$client_id variable in this file</li>";
        echo "<li>Click the authorization link above</li>";
        echo "</ol>";
    }
}
?>
