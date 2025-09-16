# Google OAuth Setup Guide

## Prerequisites
1. Google Cloud Console account
2. A project in Google Cloud Console

## Setup Steps

### 1. Create OAuth 2.0 Credentials

1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Select your project or create a new one
3. Navigate to **APIs & Services** > **Credentials**
4. Click **+ CREATE CREDENTIALS** > **OAuth client ID**
5. If prompted, configure the OAuth consent screen first:
   - Choose "External" for public apps
   - Fill in app information
   - Add scopes: `email`, `profile`, `openid`
   - Add test users if in testing mode

### 2. Configure OAuth Client

1. Application type: **Web application**
2. Name: `EzShip API`
3. Authorized JavaScript origins:
   ```
   https://api.ezship.app
   http://localhost:3000 (for local development)
   ```
4. Authorized redirect URIs:
   ```
   https://api.ezship.app/api/google/callback
   http://localhost:8000/api/google/callback (for local development)
   ```
5. Click **CREATE**

### 3. Save Credentials

Copy your credentials:
- **Client ID**: `YOUR_GOOGLE_CLIENT_ID`
- **Client Secret**: `YOUR_GOOGLE_CLIENT_SECRET`

### 4. Configure Environment Variables

Add to your `.env` file:
```env
GOOGLE_CLIENT_ID=your_google_client_id_here
GOOGLE_CLIENT_SECRET=your_google_client_secret_here
GOOGLE_REDIRECT_URL=https://api.ezship.app/api/google/callback
```

For local development:
```env
GOOGLE_REDIRECT_URL=http://localhost:8000/api/google/callback
```

### 5. Update Production Environment

SSH to your server and update the environment:
```bash
cd /opt/ezship
nano .env.prod

# Add these lines:
GOOGLE_CLIENT_ID=your_google_client_id_here
GOOGLE_CLIENT_SECRET=your_google_client_secret_here
GOOGLE_REDIRECT_URL=https://api.ezship.app/api/google/callback
```

### 6. Restart Services

```bash
docker-compose -f docker-compose.ssl.yml restart app
```

## API Usage

### Login with Google

**Endpoint**: `POST /api/google/login`

**Request Body**:
```json
{
    "access_key": "GOOGLE_ACCESS_TOKEN_FROM_CLIENT"
}
```

**Success Response**:
```json
{
    "success": true,
    "data": {
        "token": "YOUR_API_TOKEN",
        "name": "User Name",
        "email": "user@example.com"
    },
    "message": "User login successfully."
}
```

**Error Responses**:

1. Google OAuth not configured:
```json
{
    "success": false,
    "message": "Google OAuth not configured",
    "data": {
        "error": "Please configure Google OAuth credentials"
    }
}
```

2. Invalid access token:
```json
{
    "success": false,
    "message": "Invalid access token",
    "data": {
        "error": "The provided Google access token is invalid"
    }
}
```

## Frontend Implementation

### Using Google Sign-In JavaScript

```html
<script src="https://accounts.google.com/gsi/client" async defer></script>
<div id="g_id_onload"
     data-client_id="YOUR_GOOGLE_CLIENT_ID"
     data-callback="handleCredentialResponse">
</div>
<div class="g_id_signin" data-type="standard"></div>
```

```javascript
function handleCredentialResponse(response) {
    // Send response.credential to your backend
    fetch('https://api.ezship.app/api/google/login', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            access_key: response.credential
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Store token and redirect
            localStorage.setItem('token', data.data.token);
            window.location.href = '/dashboard';
        }
    });
}
```

### Using React

```bash
npm install @react-oauth/google
```

```jsx
import { GoogleOAuthProvider, GoogleLogin } from '@react-oauth/google';

function App() {
    return (
        <GoogleOAuthProvider clientId="YOUR_GOOGLE_CLIENT_ID">
            <GoogleLogin
                onSuccess={credentialResponse => {
                    // Send credentialResponse.credential to backend
                    loginWithGoogle(credentialResponse.credential);
                }}
                onError={() => {
                    console.log('Login Failed');
                }}
            />
        </GoogleOAuthProvider>
    );
}
```

## Troubleshooting

### Error 500: Server Error
- Check Laravel logs: `docker logs ezship-app-prod`
- Verify Google credentials are set in `.env`
- Clear Laravel cache: `docker exec ezship-app-prod php artisan config:clear`

### Invalid Client Error
- Verify Client ID matches exactly
- Check authorized origins include your domain
- Ensure redirect URI matches exactly

### Token Validation Failed
- Access token might be expired
- Client ID mismatch between frontend and backend
- Wrong Google project selected

## Security Notes

1. **Never expose** your Client Secret in frontend code
2. **Always validate** tokens on the backend
3. **Use HTTPS** in production
4. **Implement rate limiting** for login endpoints
5. **Log authentication attempts** for security monitoring

## Testing

Test the endpoint with curl:
```bash
# Get a test token from Google OAuth Playground
# https://developers.google.com/oauthplayground/

curl -X POST https://api.ezship.app/api/google/login \
  -H "Content-Type: application/json" \
  -d '{"access_key": "YOUR_TEST_TOKEN"}'
```

## Additional Resources

- [Google OAuth 2.0 Documentation](https://developers.google.com/identity/protocols/oauth2)
- [Laravel Socialite Documentation](https://laravel.com/docs/9.x/socialite)
- [Google OAuth Playground](https://developers.google.com/oauthplayground/)