<?php

use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\ResourceServer;
use League\OAuth2\Server\Grant\AuthCodeGrant;
use League\OAuth2\Server\Grant\RefreshTokenGrant;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\RequestTypes\AuthorizationRequest;
use Psr\Http\Message\ServerRequestInterface;
use Laminas\Diactoros\ServerRequestFactory;
use Laminas\Diactoros\Response;
use Laminas\Diactoros\Stream;

class OAuth2_Server {
    
    private $authorization_server;
    private $resource_server;
    private $private_key_path;
    private $public_key_path;
    
    public function __construct() {
        $this->setup_keys();
        $this->setup_authorization_server();
        $this->setup_resource_server();
    }
    
    /**
     * Setup encryption keys from environment variables
     */
    private function setup_keys() {
        // Get keys from environment variables
        $private_key = $_ENV['OAUTH_PRIVATE_KEY'] ?? getenv('OAUTH_PRIVATE_KEY');
        $public_key = $_ENV['OAUTH_PUBLIC_KEY'] ?? getenv('OAUTH_PUBLIC_KEY');
        
        if (!$private_key || !$public_key) {
            throw new Exception('OAuth RSA keys not found in environment variables. Please set OAUTH_PRIVATE_KEY and OAUTH_PUBLIC_KEY in .env file.');
        }
        
        $this->private_key_path = $private_key;
        $this->public_key_path = $public_key;
    
    }

    
    /**
     * Setup authorization server with PKCE support
     */
    private function setup_authorization_server() {
        // Initialize repositories
        $client_repository = new ClientRepository();
        $scope_repository = new ScopeRepository();
        $auth_code_repository = new AuthCodeRepository();
        $access_token_repository = new AccessTokenRepository();
        $refresh_token_repository = new RefreshTokenRepository();
        
        // Setup authorization server
        $this->authorization_server = new AuthorizationServer(
            $client_repository,
            $access_token_repository,
            $scope_repository,
            $this->private_key_path,
            wp_generate_password(32, false) // Encryption key
        );
        
        // Enable authorization code grant with PKCE
        $auth_code_grant = new AuthCodeGrant(
            $auth_code_repository,
            $refresh_token_repository,
            new DateInterval('PT10M') // Authorization codes expire after 10 minutes
        );
        
        // PKCE is automatically supported in League OAuth2 Server v9+
        // No additional configuration needed
        
        // Set token TTLs
        $auth_code_grant->setRefreshTokenTTL(new DateInterval('P1M')); // 1 month
        
        $this->authorization_server->enableGrantType(
            $auth_code_grant,
            new DateInterval('PT1H') // Access tokens expire after 1 hour
        );
        
        // Enable refresh token grant
        $refresh_grant = new RefreshTokenGrant($refresh_token_repository);
        $refresh_grant->setRefreshTokenTTL(new DateInterval('P1M'));
        
        $this->authorization_server->enableGrantType(
            $refresh_grant,
            new DateInterval('PT1H')
        );
    }
    
    /**
     * Setup resource server
     */
    private function setup_resource_server() {
        $access_token_repository = new AccessTokenRepository();
        
        $this->resource_server = new ResourceServer(
            $access_token_repository,
            $this->public_key_path
        );
    }
    
    /**
     * Handle authorization request
     */
    public function handle_authorization_request() {
        // Debug: Log that we reached this function
        error_log('OAuth2: handle_authorization_request called');
        error_log('OAuth2: REQUEST_METHOD = ' . $_SERVER['REQUEST_METHOD']);
        error_log('OAuth2: REQUEST_URI = ' . $_SERVER['REQUEST_URI']);
        
        try {
            $request = ServerRequestFactory::fromGlobals();
            $response = new Response();
            
            // Validate the authorization request
            $auth_request = $this->authorization_server->validateAuthorizationRequest($request);
            
            // Check if user is logged in
            if (!is_user_logged_in()) {
                // Redirect to login with return URL
                $login_url = wp_login_url(add_query_arg($_GET, site_url('/oauth2/authorize')));
                wp_redirect($login_url);
                exit;
            }
            
            // Get current user
            $current_user = wp_get_current_user();
            
            // Create user entity
            $user_entity = new UserEntity();
            $user_entity->setIdentifier($current_user->ID);
            $user_entity->setUsername($current_user->user_login);
            $user_entity->setEmail($current_user->user_email);
            
            // Set the user on the authorization request
            $auth_request->setUser($user_entity);
            
            // Check if this is a POST request (user approved/denied)
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                // Debug: Log POST data
                error_log('OAuth2 POST data: ' . print_r($_POST, true));
                
                // Verify nonce for security
                if (!wp_verify_nonce($_POST['oauth2_nonce'] ?? '', 'oauth2_authorize')) {
                    wp_die('Security check failed. Please try again.');
                }
                
                // Set authorization approval based on which button was clicked
                $approved = isset($_POST['approve']); // If approve button was clicked, it will be present
                error_log('OAuth2 Authorization approved: ' . ($approved ? 'YES' : 'NO'));
                error_log('OAuth2 Approve value: ' . (isset($_POST['approve']) ? $_POST['approve'] : 'NOT SET'));
                error_log('OAuth2 Deny value: ' . (isset($_POST['deny']) ? $_POST['deny'] : 'NOT SET'));
                
                $auth_request->setAuthorizationApproved($approved);
                
                // Complete the authorization request
                $response = $this->authorization_server->completeAuthorizationRequest($auth_request, $response);
                
                // Debug: Log response
                error_log('OAuth2 Response status: ' . $response->getStatusCode());
                error_log('OAuth2 Response headers: ' . print_r($response->getHeaders(), true));
                
                // Redirect user back to client
                if ($response->getStatusCode() === 302) {
                    $location = $response->getHeaderLine('Location');
                    error_log('OAuth2 Redirecting to: ' . $location);
                    wp_redirect($location);
                    exit;
                } else {
                    // If not a redirect, output the response
                    http_response_code($response->getStatusCode());
                    foreach ($response->getHeaders() as $name => $values) {
                        foreach ($values as $value) {
                            header(sprintf('%s: %s', $name, $value), false);
                        }
                    }
                    echo $response->getBody();
                    exit;
                }
            } else {
                // Show authorization form
                $this->show_authorization_form($auth_request);
            }
            
        } catch (OAuthServerException $exception) {
            $this->handle_oauth_exception($exception);
        } catch (Exception $exception) {
            wp_die('Authorization error: ' . $exception->getMessage());
        }
    }
    
    /**
     * Handle token request
     */
    public function handle_token_request() {
        try {
            $request = ServerRequestFactory::fromGlobals();
            $response = new Response();
            
            // Return the HTTP response
            $response = $this->authorization_server->respondToAccessTokenRequest($request, $response);
            
            // Send the response
            http_response_code($response->getStatusCode());
            
            foreach ($response->getHeaders() as $name => $values) {
                foreach ($values as $value) {
                    header(sprintf('%s: %s', $name, $value), false);
                }
            }
            
            echo $response->getBody();
            exit;
            
        } catch (OAuthServerException $exception) {
            $this->handle_oauth_exception($exception);
        } catch (Exception $exception) {
            http_response_code(500);
            echo json_encode(['error' => 'server_error', 'error_description' => $exception->getMessage()]);
            exit;
        }
    }
    
    /**
     * Handle userinfo request
     */
    public function handle_userinfo_request() {
        try {
            $request = ServerRequestFactory::fromGlobals();
            $response = new Response();
            
            // Validate the access token
            $request = $this->resource_server->validateAuthenticatedRequest($request);
            
            // Get user info
            $user_id = $request->getAttribute('oauth_user_id');
            $user = get_user_by('id', $user_id);
            
            if (!$user) {
                throw new Exception('User not found');
            }
            
            $user_info = [
                'sub' => $user->ID,
                'name' => $user->display_name,
                'email' => $user->user_email,
                'username' => $user->user_login,
                'profile' => get_author_posts_url($user->ID)
            ];
            
            header('Content-Type: application/json');
            echo json_encode($user_info);
            exit;
            
        } catch (OAuthServerException $exception) {
            $this->handle_oauth_exception($exception);
        } catch (Exception $exception) {
            http_response_code(500);
            echo json_encode(['error' => 'server_error', 'error_description' => $exception->getMessage()]);
            exit;
        }
    }
    
    /**
     * Show authorization form
     */
    private function show_authorization_form($auth_request) {
        $client = $auth_request->getClient();
        $scopes = $auth_request->getScopes();
        
        // Get template
        include WP_OAUTH2_PKCE_PLUGIN_DIR . 'templates/authorize.php';
        exit;
    }
    
    /**
     * Handle OAuth exceptions
     */
    private function handle_oauth_exception(OAuthServerException $exception) {
        $response = new Response();
        $response = $exception->generateHttpResponse($response);
        
        http_response_code($response->getStatusCode());
        
        foreach ($response->getHeaders() as $name => $values) {
            foreach ($values as $value) {
                header(sprintf('%s: %s', $name, $value), false);
            }
        }
        
        echo $response->getBody();
        exit;
    }
}
