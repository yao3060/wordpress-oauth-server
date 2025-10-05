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

    /**
     * Override JWT conversion to include issuer (iss) from settings
     */
    private function convertToJWT(): \Lcobucci\JWT\Token
    {
        // Build JWT configuration similar to trait's initJwtConfiguration
        $privateKeyContents = $this->privateKey->getKeyContents();
        if ($privateKeyContents === '') {
            throw new \RuntimeException('Private key is empty');
        }

        $jwtConfiguration = \Lcobucci\JWT\Configuration::forAsymmetricSigner(
            new \Lcobucci\JWT\Signer\Rsa\Sha256(),
            \Lcobucci\JWT\Signer\Key\InMemory::plainText($privateKeyContents, $this->privateKey->getPassPhrase() ?? ''),
            \Lcobucci\JWT\Signer\Key\InMemory::plainText('empty', 'empty')
        );

        // Issuer from WP option (fallback to site_url)
        $issuer = function_exists('get_option') ? get_option('oauth2_issuer', function_exists('site_url') ? site_url() : '') : '';

        // Build token with iss added
        return $jwtConfiguration->builder()
            ->permittedFor($this->getClient()->getIdentifier())
            ->identifiedBy($this->getIdentifier())
            ->issuedAt(new \DateTimeImmutable())
            ->canOnlyBeUsedAfter(new \DateTimeImmutable())
            ->expiresAt($this->getExpiryDateTime())
            ->relatedTo($this->getSubjectIdentifier())
            ->issuedBy($issuer)
            ->withClaim('scopes', $this->getScopes())
            ->getToken($jwtConfiguration->signer(), $jwtConfiguration->signingKey());
    }
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
