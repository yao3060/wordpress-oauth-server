# WordPress OAuth2 PKCE Server

A comprehensive OAuth2 server implementation for WordPress with PKCE (Proof Key for Code Exchange) support, built using the League OAuth2 Server library.

## Features

- ✅ **PKCE Support**: Full implementation of RFC 7636 for enhanced security
- ✅ **Authorization Code Grant**: Standard OAuth2 authorization code flow
- ✅ **Refresh Tokens**: Long-lived refresh tokens for seamless user experience
- ✅ **Public & Confidential Clients**: Support for both client types
- ✅ **WordPress Integration**: Native WordPress user authentication
- ✅ **Admin Interface**: Easy client management through WordPress admin
- ✅ **Secure**: RSA key encryption and proper token validation
- ✅ **RESTful Endpoints**: Standard OAuth2 endpoints for authorization, token, and userinfo

## Installation

1. **Install Dependencies**: Make sure `league/oauth2-server` is installed via Composer:
   ```bash
   cd /path/to/wordpress/
   composer require league/oauth2-server
   ```

2. **Upload Plugin**: Copy the plugin files to your WordPress plugins directory:
   ```
   wp-content/plugins/wp-oauth2-pkce/
   ```

3. **Activate Plugin**: Activate the plugin through the WordPress admin panel.

4. **Configure**: Go to **Settings > OAuth2 PKCE** to create your first OAuth2 client.

## OAuth2 Endpoints

After activation, the following endpoints will be available:

- **Authorization**: `https://your-site.com/oauth2/authorize`
- **Token**: `https://your-site.com/oauth2/token`
- **UserInfo**: `https://your-site.com/oauth2/userinfo`

## Creating OAuth2 Clients

### Via Admin Interface

1. Go to **Settings > OAuth2 PKCE** in your WordPress admin
2. Fill in the client details:
   - **Client Name**: Human-readable name for your application
   - **Redirect URI**: Where users will be redirected after authorization
   - **Client Type**: 
     - **Public**: For mobile apps, SPAs (uses PKCE)
     - **Confidential**: For server-side apps (uses client secret)

### Supported Grant Types

- `authorization_code` - Standard authorization code flow
- `refresh_token` - Refresh access tokens

### Supported Scopes

- `read` - Read user profile information
- `write` - Modify user profile information  
- `profile` - Access basic profile information

## PKCE Implementation

This implementation follows RFC 7636 specifications:

### Code Challenge Methods
- `S256` (SHA256) - Recommended and default
- `plain` - Supported for compatibility

### PKCE Flow

1. **Generate Code Verifier**: Random 43-128 character string
2. **Generate Code Challenge**: SHA256 hash of code verifier (base64url encoded)
3. **Authorization Request**: Include `code_challenge` and `code_challenge_method`
4. **Token Exchange**: Include `code_verifier` to verify the challenge

## Usage Examples

### PHP Client Example

```php
// Include the example client
require_once 'examples/pkce-client-example.php';

// Initialize client
$client = new PKCEClient(
    'your_client_id',
    'http://localhost:8080/callback.php',
    'http://your-wordpress-site.com'
);

// Get authorization URL
$auth_url = $client->getAuthorizationUrl(['read', 'profile']);

// Redirect user to authorization URL
header('Location: ' . $auth_url);
```

### JavaScript Client Example

```javascript
// Initialize PKCE client
const client = new PKCEClient(
    'your_client_id',
    'http://localhost:3000/callback.html',
    'http://your-wordpress-site.com'
);

// Start authorization flow
const authUrl = await client.getAuthorizationUrl(['read', 'profile']);
window.location.href = authUrl;

// Handle callback
const tokenResponse = await client.exchangeCodeForToken(code, state);
const userInfo = await client.getUserInfo(tokenResponse.access_token);
```

## Authorization Request

```
GET /oauth2/authorize?
    response_type=code&
    client_id=CLIENT_ID&
    redirect_uri=REDIRECT_URI&
    scope=read+profile&
    state=STATE&
    code_challenge=CODE_CHALLENGE&
    code_challenge_method=S256
```

## Token Request

```
POST /oauth2/token
Content-Type: application/x-www-form-urlencoded

grant_type=authorization_code&
client_id=CLIENT_ID&
code=AUTHORIZATION_CODE&
redirect_uri=REDIRECT_URI&
code_verifier=CODE_VERIFIER
```

## Token Response

```json
{
    "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...",
    "token_type": "Bearer",
    "expires_in": 3600,
    "refresh_token": "def50200...",
    "scope": "read profile"
}
```

## UserInfo Request

```
GET /oauth2/userinfo
Authorization: Bearer ACCESS_TOKEN
```

## UserInfo Response

```json
{
    "sub": "123",
    "name": "John Doe",
    "email": "john@example.com",
    "username": "johndoe",
    "profile": "http://your-site.com/author/johndoe"
}
```

## Security Considerations

### PKCE Benefits
- **No Client Secret Required**: Eliminates the risk of secret exposure in public clients
- **Dynamic Secrets**: Each authorization request uses a unique code challenge
- **Replay Attack Protection**: Code verifier can only be used once

### Best Practices
- Always use HTTPS in production
- Implement proper state parameter validation
- Use short-lived access tokens (1 hour default)
- Implement token refresh logic in clients
- Validate redirect URIs strictly

## Database Schema

The plugin creates the following tables:

### `wp_oauth2_clients`
- Client registration and configuration
- Stores hashed client secrets for confidential clients
- Redirect URI validation

### `wp_oauth2_authorization_codes`
- Temporary authorization codes with PKCE challenges
- 10-minute expiration for security

### `wp_oauth2_access_tokens`
- Active access tokens with scope and expiration
- Linked to WordPress users

### `wp_oauth2_refresh_tokens`
- Long-lived refresh tokens (1 month default)
- Enables seamless token renewal

## Troubleshooting

### Common Issues

1. **"Invalid client" error**
   - Verify client ID is correct
   - Check if client exists in database

2. **"Invalid redirect URI" error**
   - Ensure redirect URI exactly matches registered URI
   - Check for trailing slashes and protocol differences

3. **"Invalid code verifier" error**
   - Verify PKCE implementation in client
   - Check code verifier is properly stored and retrieved

4. **"Authorization code expired" error**
   - Authorization codes expire after 10 minutes
   - Ensure timely token exchange

### Debug Mode

Enable WordPress debug mode to see detailed error messages:

```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

## Development

### Requirements
- PHP 7.4+
- WordPress 5.0+
- OpenSSL extension
- league/oauth2-server ^9.2

### File Structure
```
wp-oauth2-pkce/
├── wp-oauth2-pkce.php          # Main plugin file
├── includes/
│   ├── class-wp-oauth2-pkce.php    # Core plugin class
│   ├── class-oauth2-server.php     # OAuth2 server implementation
│   ├── class-oauth2-entities.php   # OAuth2 entities
│   ├── class-oauth2-repositories.php # Data repositories
│   └── class-oauth2-admin.php      # Admin interface
├── templates/
│   └── authorize.php               # Authorization form template
├── assets/
│   ├── css/                        # Stylesheets
│   └── js/                         # JavaScript files
├── examples/
│   ├── pkce-client-example.php     # PHP client example
│   └── pkce-client.html           # JavaScript client example
└── keys/                           # RSA keys (auto-generated)
```

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests if applicable
5. Submit a pull request

## License

This project is licensed under the MIT License.

## Support

For issues and questions:
1. Check the troubleshooting section
2. Review the examples
3. Enable debug mode for detailed errors
4. Check WordPress and PHP error logs
