<?php

use League\OAuth2\Server\Repositories\ClientRepositoryInterface;
use League\OAuth2\Server\Repositories\UserRepositoryInterface;
use League\OAuth2\Server\Repositories\AuthCodeRepositoryInterface;
use League\OAuth2\Server\Repositories\AccessTokenRepositoryInterface;
use League\OAuth2\Server\Repositories\RefreshTokenRepositoryInterface;
use League\OAuth2\Server\Repositories\ScopeRepositoryInterface;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\UserEntityInterface;

/**
 * Client Repository
 */
class ClientRepository implements ClientRepositoryInterface {
    
    public function getClientEntity(string $clientIdentifier): ?\League\OAuth2\Server\Entities\ClientEntityInterface {
        global $wpdb;
        
        $table = $wpdb->prefix . 'oauth2_clients';
        $client_data = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE id = %s",
            $clientIdentifier
        ));
        
        if (!$client_data) {
            return null;
        }
        
        $client = new ClientEntity();
        $client->setIdentifier($client_data->id);
        $client->setName($client_data->name);
        $client->setRedirectUri(explode(',', $client_data->redirect_uri));
        $client->setConfidential((bool) $client_data->is_confidential);
        
        return $client;
    }
    
    public function validateClient(string $clientIdentifier, string|null $clientSecret, string|null $grantType): bool {
        global $wpdb;
        
        $table = $wpdb->prefix . 'oauth2_clients';
        $client_data = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE id = %s",
            $clientIdentifier
        ));
        
        if (!$client_data) {
            return false;
        }
        
        // For PKCE, public clients don't need a secret
        if (!$client_data->is_confidential && $grantType === 'authorization_code') {
            return true;
        }
        
        // For confidential clients, verify the secret
        if ($client_data->is_confidential) {
            return password_verify($clientSecret, $client_data->secret);
        }
        
        return false;
    }
}

/**
 * User Repository
 */
class UserRepository implements UserRepositoryInterface {
    
    public function getUserEntityByUserCredentials(string $username, string $password, string $grantType, \League\OAuth2\Server\Entities\ClientEntityInterface $clientEntity): ?\League\OAuth2\Server\Entities\UserEntityInterface {
        // This is used for password grant type, not needed for PKCE
        return null;
    }
}

/**
 * Authorization Code Repository with PKCE support
 */
class AuthCodeRepository implements AuthCodeRepositoryInterface {
    
    public function getNewAuthCode(): \League\OAuth2\Server\Entities\AuthCodeEntityInterface {
        return new AuthCodeEntity();
    }
    
    public function persistNewAuthCode(\League\OAuth2\Server\Entities\AuthCodeEntityInterface $authCodeEntity): void {
        global $wpdb;
        
        $table = $wpdb->prefix . 'oauth2_authorization_codes';
        
        $scopes = array_map(function($scope) {
            return $scope->getIdentifier();
        }, $authCodeEntity->getScopes());
        
        // Cast to our custom AuthCodeEntity to access PKCE methods
        $codeChallenge = null;
        $codeChallengeMethod = null;
        if ($authCodeEntity instanceof AuthCodeEntity) {
            $codeChallenge = $authCodeEntity->getCodeChallenge();
            $codeChallengeMethod = $authCodeEntity->getCodeChallengeMethod();
        }
        
        $wpdb->insert($table, [
            'authorization_code' => $authCodeEntity->getIdentifier(),
            'client_id' => $authCodeEntity->getClient()->getIdentifier(),
            'user_id' => $authCodeEntity->getUserIdentifier(),
            'redirect_uri' => $authCodeEntity->getRedirectUri(),
            'expires' => $authCodeEntity->getExpiryDateTime()->format('Y-m-d H:i:s'),
            'scope' => implode(' ', $scopes),
            'code_challenge' => $codeChallenge,
            'code_challenge_method' => $codeChallengeMethod
        ]);
    }
    
    public function revokeAuthCode(string $codeId): void {
        global $wpdb;
        
        $table = $wpdb->prefix . 'oauth2_authorization_codes';
        $wpdb->delete($table, ['authorization_code' => $codeId]);
    }
    
    public function isAuthCodeRevoked(string $codeId): bool {
        global $wpdb;
        
        $table = $wpdb->prefix . 'oauth2_authorization_codes';
        $result = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE authorization_code = %s",
            $codeId
        ));
        
        return $result == 0;
    }
}

/**
 * Access Token Repository
 */
class AccessTokenRepository implements AccessTokenRepositoryInterface {
    
    public function getNewToken(\League\OAuth2\Server\Entities\ClientEntityInterface $clientEntity, array $scopes, $userIdentifier = null): \League\OAuth2\Server\Entities\AccessTokenEntityInterface {
        $accessToken = new AccessTokenEntity();
        $accessToken->setClient($clientEntity);
        foreach ($scopes as $scope) {
            $accessToken->addScope($scope);
        }
        // In client credentials grant, there is no user; avoid passing null to setter
        if ($userIdentifier !== null) {
            $accessToken->setUserIdentifier((string)$userIdentifier);
        }
        
        return $accessToken;
    }
    
    public function persistNewAccessToken(\League\OAuth2\Server\Entities\AccessTokenEntityInterface $accessTokenEntity): void {
        global $wpdb;
        
        $table = $wpdb->prefix . 'oauth2_access_tokens';
        
        $scopes = array_map(function($scope) {
            return $scope->getIdentifier();
        }, $accessTokenEntity->getScopes());
        
        $wpdb->insert($table, [
            'access_token' => $accessTokenEntity->getIdentifier(),
            'client_id' => $accessTokenEntity->getClient()->getIdentifier(),
            'user_id' => $accessTokenEntity->getUserIdentifier(),
            'expires' => $accessTokenEntity->getExpiryDateTime()->format('Y-m-d H:i:s'),
            'scope' => implode(' ', $scopes)
        ]);
    }
    
    public function revokeAccessToken(string $tokenId): void {
        global $wpdb;
        
        $table = $wpdb->prefix . 'oauth2_access_tokens';
        $wpdb->delete($table, ['access_token' => $tokenId]);
    }
    
    public function isAccessTokenRevoked(string $tokenId): bool {
        global $wpdb;
        
        $table = $wpdb->prefix . 'oauth2_access_tokens';
        $result = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE access_token = %s AND expires > NOW()",
            $tokenId
        ));
        
        return $result == 0;
    }
}

/**
 * Refresh Token Repository
 */
class RefreshTokenRepository implements RefreshTokenRepositoryInterface {
    
    public function getNewRefreshToken(): \League\OAuth2\Server\Entities\RefreshTokenEntityInterface {
        return new RefreshTokenEntity();
    }
    
    public function persistNewRefreshToken(\League\OAuth2\Server\Entities\RefreshTokenEntityInterface $refreshTokenEntity): void {
        global $wpdb;
        
        $table = $wpdb->prefix . 'oauth2_refresh_tokens';
        
        $wpdb->insert($table, [
            'refresh_token' => $refreshTokenEntity->getIdentifier(),
            'client_id' => $refreshTokenEntity->getAccessToken()->getClient()->getIdentifier(),
            'user_id' => $refreshTokenEntity->getAccessToken()->getUserIdentifier(),
            'expires' => $refreshTokenEntity->getExpiryDateTime()->format('Y-m-d H:i:s'),
            'scope' => implode(' ', array_map(function($scope) {
                return $scope->getIdentifier();
            }, $refreshTokenEntity->getAccessToken()->getScopes()))
        ]);
    }
    
    public function revokeRefreshToken(string $tokenId): void {
        global $wpdb;
        
        $table = $wpdb->prefix . 'oauth2_refresh_tokens';
        $wpdb->delete($table, ['refresh_token' => $tokenId]);
    }
    
    public function isRefreshTokenRevoked(string $tokenId): bool {
        global $wpdb;
        
        $table = $wpdb->prefix . 'oauth2_refresh_tokens';
        $result = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE refresh_token = %s AND expires > NOW()",
            $tokenId
        ));
        
        return $result == 0;
    }
}

/**
 * Scope Repository
 */
class ScopeRepository implements ScopeRepositoryInterface {
    
    public function getScopeEntityByIdentifier(string $identifier): ?\League\OAuth2\Server\Entities\ScopeEntityInterface {
        $scopes = [
            'read' => 'Read access',
            'write' => 'Write access',
            'profile' => 'Profile access'
        ];
        
        if (!array_key_exists($identifier, $scopes)) {
            return null;
        }
        
        $scope = new ScopeEntity();
        $scope->setIdentifier($identifier);
        
        return $scope;
    }
    
    public function finalizeScopes(array $scopes, string $grantType, \League\OAuth2\Server\Entities\ClientEntityInterface $clientEntity, string|null $userIdentifier = null, string|null $authCodeId = null): array {
        // Return the scopes as-is for now
        return $scopes;
    }
}
