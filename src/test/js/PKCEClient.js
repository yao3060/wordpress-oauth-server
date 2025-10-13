class PKCEClient {
    constructor(clientId, redirectUri, baseUrl) {
        this.clientId = clientId;
        this.redirectUri = redirectUri;
        this.authorizationEndpoint = baseUrl + '/oauth2/authorize/';
        this.tokenEndpoint = baseUrl + '/oauth2/token';
        this.userinfoEndpoint = baseUrl + '/oauth2/userinfo';
    }
    
    // Generate PKCE code verifier and challenge
    async generatePKCE() {
        const codeVerifier = this.base64URLEncode(crypto.getRandomValues(new Uint8Array(32)));
        const encoder = new TextEncoder();
        const data = encoder.encode(codeVerifier);
        const digest = await crypto.subtle.digest('SHA-256', data);
        const codeChallenge = this.base64URLEncode(new Uint8Array(digest));
        
        return {
            codeVerifier,
            codeChallenge,
            codeChallengeMethod: 'S256'
        };
    }
    
    // Base64 URL encode
    base64URLEncode(buffer) {
        return btoa(String.fromCharCode(...buffer))
            .replace(/\+/g, '-')
            .replace(/\//g, '_')
            .replace(/=/g, '');
    }
    
    // Get authorization URL
    async getAuthorizationUrl(scopes = ['read', 'profile'], prompt = null) {
        const pkce = await this.generatePKCE();
        const state = this.base64URLEncode(crypto.getRandomValues(new Uint8Array(16)));
        
        // Store for later use
        localStorage.setItem('oauth2_code_verifier', pkce.codeVerifier);
        localStorage.setItem('oauth2_state', state);
        
        const params = new URLSearchParams({
            response_type: 'code',
            client_id: this.clientId,
            redirect_uri: this.redirectUri,
            scope: scopes.join(' '),
            state: state,
            code_challenge: pkce.codeChallenge,
            code_challenge_method: pkce.codeChallengeMethod
        });
        
        // Add prompt parameter if specified (for silent login)
        if (prompt) {
            params.set('prompt', prompt);
        }
        
        return `${this.authorizationEndpoint}?${params}`;
    }
    
    // Exchange authorization code for access token
    async exchangeCodeForToken(authorizationCode, state) {
        const storedState = localStorage.getItem('oauth2_state');
        if (state !== storedState) {
            throw new Error('Invalid state parameter');
        }
        
        const codeVerifier = localStorage.getItem('oauth2_code_verifier');
        if (!codeVerifier) {
            throw new Error('Code verifier not found');
        }
        
        const params = new URLSearchParams({
            grant_type: 'authorization_code',
            client_id: this.clientId,
            code: authorizationCode,
            redirect_uri: this.redirectUri,
            code_verifier: codeVerifier
        });

        console.log('exchangeCodeForToken', this.tokenEndpoint, params.toString());
        
        const response = await fetch(this.tokenEndpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: params
        });
        
        // Clean up
        localStorage.removeItem('oauth2_code_verifier');
        localStorage.removeItem('oauth2_state');
        
        if (!response.ok) {
            const error = await response.json();
            throw new Error(error.error_description || error.error || 'Token exchange failed');
        }
        
        const data = await response.json();
        // Store tokens for refresh testing
        if (data.access_token) localStorage.setItem('oauth2_access_token', data.access_token);
        if (data.refresh_token) localStorage.setItem('oauth2_refresh_token', data.refresh_token);
        if (data.expires_in) localStorage.setItem('oauth2_expires_in', String(data.expires_in));
        updateTokenStatus();
        return data;
    }
    
    // Get user information
    async getUserInfo(accessToken) {
        const response = await fetch(this.userinfoEndpoint, {
            headers: {
                'Authorization': `Bearer ${accessToken}`,
                'Accept': 'application/json'
            }
        });
        
        if (!response.ok) {
            const error = await response.json();
            throw new Error(error.error_description || error.error || 'Failed to get user info');
        }
        
        return await response.json();
    }
}
