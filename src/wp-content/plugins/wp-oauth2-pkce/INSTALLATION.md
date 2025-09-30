# WordPress OAuth2 PKCE Server - Installation Guide

## Quick Start

### 1. Dependencies Already Installed ✅
The required dependencies are already installed in your WordPress project:
- `league/oauth2-server: ^9.2`
- `laminas/laminas-diactoros: ^3.0`

### 2. Plugin Files Ready ✅
All plugin files have been created in:
```
wp-content/plugins/wp-oauth2-pkce/
```

### 3. Activate the Plugin

1. **Access WordPress Admin**: Go to your WordPress admin panel
2. **Navigate to Plugins**: Go to `Plugins > Installed Plugins`
3. **Find OAuth2 PKCE**: Look for "WordPress OAuth2 PKCE Server"
4. **Activate**: Click "Activate"

### 4. Configure OAuth2 Clients

1. **Go to Settings**: Navigate to `Settings > OAuth2 PKCE`
2. **Create Client**: Fill in the form:
   - **Client Name**: e.g., "My Mobile App"
   - **Redirect URI**: e.g., `http://localhost:3000/callback`
   - **Client Type**: 
     - ✅ **Public** (for PKCE - mobile apps, SPAs)
     - ⚪ **Confidential** (for server-side apps with secrets)
3. **Save Credentials**: Copy the generated Client ID (and secret if confidential)

## OAuth2 Endpoints

After activation, these endpoints will be available:

| Endpoint | URL | Purpose |
|----------|-----|---------|
| **Authorization** | `https://your-site.com/oauth2/authorize` | Start OAuth2 flow |
| **Token** | `https://your-site.com/oauth2/token` | Exchange code for tokens |
| **UserInfo** | `https://your-site.com/oauth2/userinfo` | Get user information |

## Testing the Implementation

### Option 1: PHP Client Example
```bash
# Navigate to examples directory
cd wp-content/plugins/wp-oauth2-pkce/examples/

# Edit pkce-client-example.php and update:
# - $client_id with your generated client ID
# - $wordpress_base_url with your WordPress URL
# - $redirect_uri with your callback URL

# Run the example
php -S localhost:8080 pkce-client-example.php
```

### Option 2: JavaScript Client Example
```bash
# Open the HTML example
open wp-content/plugins/wp-oauth2-pkce/examples/pkce-client.html

# Or serve it with a simple HTTP server
cd wp-content/plugins/wp-oauth2-pkce/examples/
python3 -m http.server 3000
# Then open http://localhost:3000/pkce-client.html
```

## PKCE Flow Example

### 1. Generate PKCE Parameters
```javascript
// Client generates code verifier and challenge
const codeVerifier = generateRandomString(128);
const codeChallenge = base64URLEncode(sha256(codeVerifier));
```

### 2. Authorization Request
```
GET /oauth2/authorize?
    response_type=code&
    client_id=YOUR_CLIENT_ID&
    redirect_uri=YOUR_REDIRECT_URI&
    scope=read+profile&
    state=RANDOM_STATE&
    code_challenge=CODE_CHALLENGE&
    code_challenge_method=S256
```

### 3. Token Exchange
```
POST /oauth2/token
Content-Type: application/x-www-form-urlencoded

grant_type=authorization_code&
client_id=YOUR_CLIENT_ID&
code=AUTHORIZATION_CODE&
redirect_uri=YOUR_REDIRECT_URI&
code_verifier=CODE_VERIFIER
```

### 4. Access Protected Resources
```
GET /oauth2/userinfo
Authorization: Bearer ACCESS_TOKEN
```

## Troubleshooting

### Common Issues

1. **"Invalid client" error**
   - ✅ Verify client ID is correct
   - ✅ Check client exists in WordPress admin

2. **"Invalid redirect URI" error**
   - ✅ Ensure redirect URI exactly matches registered URI
   - ✅ Check for trailing slashes and protocol (http vs https)

3. **Database connection errors**
   - ✅ Make sure WordPress database is running
   - ✅ Check Docker containers if using Docker setup

4. **Plugin activation fails**
   - ✅ Check debug log: `wp-content/debug.log`
   - ✅ Verify Composer dependencies are installed

### Enable Debug Mode
Add to `wp-config.php`:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

### Check Debug Log
```bash
tail -f wp-content/debug.log
```

## Security Notes

### Production Checklist
- ✅ Use HTTPS for all OAuth2 endpoints
- ✅ Set appropriate token expiration times
- ✅ Validate redirect URIs strictly
- ✅ Use strong encryption keys
- ✅ Monitor access token usage

### PKCE Benefits
- 🔒 **No Client Secrets**: Eliminates secret exposure risk
- 🔒 **Dynamic Security**: Each request uses unique code challenge
- 🔒 **Replay Protection**: Code verifier can only be used once
- 🔒 **Mobile-Friendly**: Perfect for mobile apps and SPAs

## Support

If you encounter issues:
1. Check the troubleshooting section above
2. Review the debug log for error messages
3. Verify all dependencies are properly installed
4. Test with the provided examples first

## Next Steps

1. ✅ Activate the plugin
2. ✅ Create your first OAuth2 client
3. ✅ Test with the provided examples
4. ✅ Integrate with your application
5. ✅ Deploy to production with HTTPS

Happy coding! 🚀
