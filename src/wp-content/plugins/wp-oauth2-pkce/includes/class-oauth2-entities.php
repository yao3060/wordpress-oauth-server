<?php

use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\UserEntityInterface;
use League\OAuth2\Server\Entities\AuthCodeEntityInterface;
use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use League\OAuth2\Server\Entities\RefreshTokenEntityInterface;
use League\OAuth2\Server\Entities\ScopeEntityInterface;
use League\OAuth2\Server\Entities\Traits\EntityTrait;
use League\OAuth2\Server\Entities\Traits\TokenEntityTrait;
use League\OAuth2\Server\Entities\Traits\AuthCodeTrait;
use League\OAuth2\Server\Entities\Traits\AccessTokenTrait;
use League\OAuth2\Server\Entities\Traits\RefreshTokenTrait;

/**
 * Client Entity
 */
class ClientEntity implements ClientEntityInterface {
    use EntityTrait;
    
    protected $name;
    protected $redirectUri;
    protected $isConfidential;
    
    public function getName(): string {
        return $this->name ?? '';
    }
    
    public function setName($name): void {
        $this->name = $name;
    }
    
    public function getRedirectUri(): array {
        return is_array($this->redirectUri) ? $this->redirectUri : [$this->redirectUri];
    }
    
    public function setRedirectUri($redirectUri): void {
        $this->redirectUri = $redirectUri;
    }
    
    public function isConfidential(): bool {
        return (bool) $this->isConfidential;
    }
    
    public function setConfidential($isConfidential): void {
        $this->isConfidential = $isConfidential;
    }
}

/**
 * User Entity
 */
class UserEntity implements UserEntityInterface {
    use EntityTrait;
    
    protected $username;
    protected $email;
    
    public function getIdentifier(): string {
        return (string) $this->identifier;
    }
    
    public function setIdentifier($identifier): void {
        $this->identifier = $identifier;
    }
    
    public function getUsername(): string {
        return $this->username ?? '';
    }
    
    public function setUsername($username): void {
        $this->username = $username;
    }
    
    public function getEmail(): string {
        return $this->email ?? '';
    }
    
    public function setEmail($email): void {
        $this->email = $email;
    }
}

/**
 * Authorization Code Entity with PKCE support
 */
class AuthCodeEntity implements AuthCodeEntityInterface {
    use EntityTrait, TokenEntityTrait, AuthCodeTrait;
    
    protected $codeChallenge;
    protected $codeChallengeMethod;
    
    public function getCodeChallenge(): ?string {
        return $this->codeChallenge;
    }
    
    public function setCodeChallenge($codeChallenge): void {
        $this->codeChallenge = $codeChallenge;
    }
    
    public function getCodeChallengeMethod(): ?string {
        return $this->codeChallengeMethod;
    }
    
    public function setCodeChallengeMethod($codeChallengeMethod): void {
        $this->codeChallengeMethod = $codeChallengeMethod;
    }
}

/**
 * Access Token Entity
 */
class AccessTokenEntity implements AccessTokenEntityInterface {
    use EntityTrait, TokenEntityTrait, AccessTokenTrait;
}

/**
 * Refresh Token Entity
 */
class RefreshTokenEntity implements RefreshTokenEntityInterface {
    use EntityTrait, RefreshTokenTrait;
}

/**
 * Scope Entity
 */
class ScopeEntity implements ScopeEntityInterface {
    use EntityTrait;
    
    public function jsonSerialize(): mixed {
        return $this->getIdentifier();
    }
}
