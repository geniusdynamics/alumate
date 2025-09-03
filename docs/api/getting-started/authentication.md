# API Authentication Guide

This guide explains how to authenticate with the Alumni Platform API using OAuth2 Bearer tokens.

## Overview

The Alumni Platform API uses Bearer Token authentication. You need to:

1. **Register** an application to get API credentials (if developing a service)
2. **Obtain** an access token through user authentication
3. **Include** the token in all API requests
4. **Handle** token expiration and refresh

## Authentication Methods

### 1. User Authentication (OAuth2)

Most API calls require user authentication. The flow is:

```
1. User logs in via your app
2. Your app requests authorization
3. Returns access token for API calls
4. Use Bearer token for subsequent requests
```

#### Login Endpoint

```http
POST /api/auth/login
Content-Type: application/json
```

**Request Body:**
```json
{
  "email": "johndoe@institution.edu",
  "password": "securepassword123",
  "remember": false
}
```

**Success Response (200):**
```json
{
  "success": true,
  "data": {
    "user": {
      "id": 123,
      "email": "johndoe@institution.edu",
      "name": "John Doe",
      "graduation_year": 2023,
      "role": "graduate"
    },
    "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...",
    "token_type": "Bearer",
    "expires_in": 3600
  }
}
```

**Error Responses:**
```json
// Invalid credentials
{
  "success": false,
  "error": {
    "code": "AUTHENTICATION_FAILED",
    "message": "Invalid credentials"
  }
}

// Rate limited
{
  "success": false,
  "error": {
    "code": "RATE_LIMIT_EXCEEDED",
    "message": "Too many login attempts"
  }
}
```

#### Register New User

```http
POST /api/auth/register
Content-Type: application/json
```

**Request Body:**
```json
{
  "name": "Jane Smith",
  "email": "jane.smith@institution.edu",
  "password": "securepassword123",
  "graduation_year": 2023,
  "degree": "Bachelor of Science in Computer Science"
}
```

#### Get Authenticated User

Returns details of currently authenticated user:

```http
GET /api/user
Authorization: Bearer {token}
```

**Response (200):**
```json
{
  "success": true,
  "data": {
    "id": 123,
    "name": "John Doe",
    "email": "johndoe@institution.edu",
    "graduation_year": 2023,
    "role": "graduate",
    "profile_complete": true,
    "last_login": "2023-12-01T10:30:00Z"
  }
}
```

### 2. Token Management

#### Refresh Token

```http
POST /api/auth/refresh
Content-Type: application/json
Authorization: Bearer {your-current-token}
```

**Response (200):**
```json
{
  "success": true,
  "data": {
    "token": "new-jwt-token...",
    "token_type": "Bearer",
    "expires_in": 3600
  }
}
```

#### Logout

```http
POST /api/auth/logout
Authorization: Bearer {token}
```

## Making Authenticated Requests

### Include Token in Headers

All authenticated endpoints require the `Authorization` header:

```http
GET /api/alumni
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...
X-Tenant-Domain: your-tenant-domain.com
```

### Tenant Domain Header

For multi-tenant installations:
```http
X-Tenant-Domain: alumniof.institution.edu
```

## JavaScript/Node.js Example

```javascript
const API_BASE = 'https://api.alumnate.edu/v1';

// Login
async function login(email, password) {
  const response = await fetch(`${API_BASE}/auth/login`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      email: email,
      password: password
    })
  });

  const data = await response.json();

  if (data.success) {
    localStorage.setItem('api_token', data.data.token);
    return data.data;
  } else {
    throw new Error(data.error.message);
  }
}

// Make authenticated request
async function getUserProfile() {
  const token = localStorage.getItem('api_token');

  const response = await fetch(`${API_BASE}/user`, {
    method: 'GET',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json'
    }
  });

  return await response.json();
}

// Token refresh
async function refreshToken() {
  const token = localStorage.getItem('api_token');

  try {
    const response = await fetch(`${API_BASE}/auth/refresh`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json'
      }
    });

    const data = await response.json();

    if (data.success) {
      localStorage.setItem('api_token', data.data.token);
      return data.data;
    }
  } catch (error) {
    // Redirect to login on refresh failure
    localStorage.removeItem('api_token');
    window.location.href = '/login';
  }
}
```

## PHP Example

```php
class AlumniApiClient
{
    private string $baseUrl = 'https://api.alumnate.edu/v1';
    private ?string $token = null;

    public function __construct(private string $tenantDomain)
    {
    }

    public function login(string $email, string $password): array
    {
        $response = $this->makeRequest('POST', '/auth/login', [
            'email' => $email,
            'password' => $password
        ]);

        if ($response['success']) {
            $this->token = $response['data']['token'];
            return $response['data'];
        }

        throw new Exception($response['error']['message']);
    }

    public function getUser(): array
    {
        return $this->makeAuthorizedRequest('GET', '/user');
    }

    public function refreshToken(): array
    {
        $response = $this->makeAuthorizedRequest('POST', '/auth/refresh');

        if ($response['success']) {
            $this->token = $response['data']['token'];
            return $response['data'];
        }

        throw new Exception('Token refresh failed');
    }

    private function makeRequest(string $method, string $endpoint, array $data = []): array
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->baseUrl . $endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            "X-Tenant-Domain: {$this->tenantDomain}"
        ]);

        if (!empty($data)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return json_decode($result, true);
    }

    private function makeAuthorizedRequest(string $method, string $endpoint, array $data = []): array
    {
        if (!$this->token) {
            throw new Exception('No authentication token available');
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->baseUrl . $endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->token,
            "X-Tenant-Domain: {$this->tenantDomain}"
        ]);

        if (!empty($data)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return json_decode($result, true);
    }
}

// Usage
$api = new AlumniApiClient('alumniof.institution.edu');

$userData = $api->login('john@institution.edu', 'password123');
$user = $api->getUser();
```

## Python Example

```python
import requests
import json
from typing import Optional, Dict, Any

class AlumniAPIClient:
    def __init__(self, base_url: str, tenant_domain: str):
        self.base_url = base_url
        self.tenant_domain = tenant_domain
        self.token: Optional[str] = None
        self.session = requests.Session()
        self.session.headers.update({
            'Content-Type': 'application/json',
            'X-Tenant-Domain': tenant_domain
        })

    def login(self, email: str, password: str) -> Dict[str, Any]:
        """Authenticate user and get access token"""
        response = self.session.post(f'{self.base_url}/auth/login', json={
            'email': email,
            'password': password
        })

        response.raise_for_status()
        data = response.json()

        if data['success']:
            self.token = data['data']['token']
            self.session.headers['Authorization'] = f'Bearer {self.token}'
            return data['data']
        else:
            raise Exception(data['error']['message'])

    def get_user(self) -> Dict[str, Any]:
        """Get authenticated user profile"""
        response = self.session.get(f'{self.base_url}/user')
        response.raise_for_status()
        return response.json()

    def refresh_token(self) -> Dict[str, Any]:
        """Refresh access token"""
        try:
            response = self.session.post(f'{self.base_url}/auth/refresh')
            response.raise_for_status()
            data = response.json()

            if data['success']:
                self.token = data['data']['token']
                self.session.headers['Authorization'] = f'Bearer {self.token}'
                return data['data']
        except requests.exceptions.RequestException:
            # Token refresh failed, clear session
            self.token = None
            self.session.headers.pop('Authorization', None)
            raise Exception('Token refresh failed')

    def logout(self) -> None:
        """Log out user"""
        try:
            self.session.post(f'{self.base_url}/auth/logout')
        finally:
            self.token = None
            self.session.headers.pop('Authorization', None)

# Usage
client = AlumniAPIClient('https://api.alumnate.edu/v1', 'alumniof.institution.edu')

# Login
user_data = client.login('john@institution.edu', 'password123')
print(f"Logged in as: {user_data['user']['name']}")

# Get user profile
profile = client.get_user()
print(f"Profile: {profile}")

# Refresh token (if needed)
# new_token = client.refresh_token()

# Logout
client.logout()
```

## Integration Examples

### Postman Collection

Import our [Postman collection](./postman/Alumni_Platform.postman_collection.json) for easy API testing.

### Environment Variables

Set up these environment variables in your application:

```bash
ALUMNI_API_BASE_URL=https://api.alumnate.edu/v1
ALUMNI_API_TENANT_DOMAIN=your-tenant-domain.com
ALUMNI_API_CLIENT_ID=your-client-id
ALUMNI_API_CLIENT_SECRET=your-client-secret
```

## Error Handling

### Token Expiration

When you receive a 401 Unauthorized error, attempt to refresh the token:

```javascript
async function handleApiRequest(url, options = {}) {
  try {
    const response = await fetch(url, {
      ...options,
      headers: {
        ...options.headers,
        'Authorization': `Bearer ${getStoredToken()}`,
        'X-Tenant-Domain': getTenantDomain()
      }
    });

    if (response.status === 401) {
      // Token expired, try to refresh
      await refreshToken();

      // Retry the original request with new token
      return fetch(url, {
        ...options,
        headers: {
          ...options.headers,
          'Authorization': `Bearer ${getStoredToken()}`
        }
      });
    }

    return response;
  } catch (error) {
    console.error('API request failed:', error);
    throw error;
  }
}
```

## Security Best Practices

1. **Secure Token Storage**: Never store tokens in localStorage in production apps. Use secure HTTP-only cookies or secure token storage.
2. **Token Rotation**: Refresh tokens periodically and on response to high-value operations.
3. **Session Timeout**: Implement automatic logout on prolonged inactivity.
4. **HTTPS Only**: Always use HTTPS in production environments.
5. **Rate Limiting**: Respect API rate limits and implement client-side rate limiting.
6. **Error Handling**: Don't expose sensitive error details to end users.

### Production Security

```javascript
// Secure token storage example
class SecureTokenStorage {
  static storeToken(token: string): void {
    // Use secure cookie instead of localStorage
    document.cookie = `api_token=${token}; secure; samesite=strict; path=/`;
  }

  static getToken(): string | null {
    const cookies = document.cookie.split(';');
    const tokenCookie = cookies.find(cookie => cookie.trim().startsWith('api_token='));
    return tokenCookie ? tokenCookie.split('=')[1] : null;
  }

  static removeToken(): void {
    document.cookie = 'api_token=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/';
  }
}
```

## Support

### Authentication Issues

**Common Problems:**
- Invalid tokens (401)
- Expired tokens (401)
- Missing tenant domain header (422)
- Rate limiting (429)

**Troubleshooting:**
1. Verify token is not expired
2. Check tenant domain is correct
3. Ensure HTTPS is used in production
4. Check for network connectivity issues
5. Verify API credentials are valid

For additional help, contact developer support at developer-support@alumnate.edu.