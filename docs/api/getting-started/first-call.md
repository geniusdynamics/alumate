# Making Your First API Call

This guide walks you through making your first successful API call to the Alumni Platform API.

## Prerequisites

Before you start, ensure you have:

1. **API Access**: Request access to the Alumni Platform API
2. **Tenant Domain**: Your institution's tenant domain (e.g., `alumniof.institution.edu`)
3. **Test Credentials**: User credentials for testing
4. **API Client**: cURL, Postman, or your preferred HTTP client

## Step 1: Authentication

First, authenticate to get an access token. Use this endpoint to log in:

### cURL Example

```bash
curl -X POST https://api.alumnate.edu/v1/auth/login \
  -H "Content-Type: application/json" \
  -H "X-Tenant-Domain: your-tenant-domain.com" \
  -d '{
    "email": "john.doe@institution.edu",
    "password": "your-password-here",
    "remember": false
  }'
```

### Postman Example

1. Create a new POST request to: `https://api.alumnate.edu/v1/auth/login`
2. In Headers tab:
   - `Content-Type`: `application/json`
   - `X-Tenant-Domain`: `your-tenant-domain.com`
3. In Body tab (raw JSON):
   ```json
   {
     "email": "john.doe@institution.edu",
     "password": "your-password-here",
     "remember": false
   }
   ```
4. Click "Send"

### Success Response

```json
{
  "success": true,
  "data": {
    "user": {
      "id": 123,
      "name": "John Doe",
      "email": "john.doe@institution.edu",
      "graduation_year": 2023,
      "role": "graduate"
    },
    "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...",
    "token_type": "Bearer",
    "expires_in": 3600
  }
}
```

**Save the `token` value** - you'll need it for all other API calls.

## Step 2: Make Your First Authenticated Request

Now that you have a token, make a simple authenticated request to get your user profile:

### cURL Example

```bash
curl -X GET https://api.alumnate.edu/v1/user \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "X-Tenant-Domain: your-tenant-domain.com" \
  -H "Content-Type: application/json"
```

Replace `YOUR_TOKEN_HERE` with the token from the login response.

### Postman Example

1. Create a new GET request to: `https://api.alumnate.edu/v1/user`
2. In Headers tab:
   - `Authorization`: `Bearer YOUR_TOKEN_HERE`
   - `X-Tenant-Domain`: `your-tenant-domain.com`
   - `Content-Type`: `application/json`
3. Click "Send"

### Success Response

```json
{
  "success": true,
  "data": {
    "id": 123,
    "name": "John Doe",
    "email": "john.doe@institution.edu",
    "graduation_year": 2023,
    "role": "graduate",
    "profile_complete": true,
    "last_login": "2023-12-01T10:30:00Z"
  }
}
```

## Step 3: Try Different Endpoints

### Get Alumni Directory

```bash
# Get first page of alumni directory
curl -X GET "https://api.alumnate.edu/v1/alumni?page=1&per_page=10" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "X-Tenant-Domain: your-tenant-domain.com" \
  -H "Content-Type: application/json"
```

**Expected Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 456,
      "name": "Jane Smith",
      "email": "jane.smith@company.com",
      "graduation_year": 2018,
      "degree": "MBA",
      "industry": "Technology",
      "position": "Senior Developer"
    }
  ],
  "meta": {
    "total": 1500,
    "per_page": 10,
    "current_page": 1,
    "last_page": 150,
    "from": 1,
    "to": 10
  }
}
```

### Create a Post

```bash
curl -X POST https://api.alumnate.edu/v1/posts \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "X-Tenant-Domain: your-tenant-domain.com" \
  -H "Content-Type: application/json" \
  -d '{
    "content": "Hello Alumni Platform API! This is my first post via API.",
    "tags": ["api", "introduction"],
    "privacy": "public"
  }'
```

### Get Job Listings

```bash
# Search for jobs in technology
curl -X GET "https://api.alumnate.edu/v1/jobs?search=developer&location=remote&page=1" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "X-Tenant-Domain: your-tenant-domain.com" \
  -H "Content-Type: application/json"
```

## Step 4: Handle Common Errors

### 401 Unauthorized - Token Issues

**Problem:** Your token is expired or invalid.

**Solution:** Refresh your token:

```bash
curl -X POST https://api.alumnate.edu/v1/auth/refresh \
  -H "Authorization: Bearer YOUR_CURRENT_TOKEN" \
  -H "X-Tenant-Domain: your-tenant-domain.com" \
  -H "Content-Type: application/json"
```

### 422 Unprocessable Entity - Validation Error

**Problem:** Request data doesn't meet validation requirements.

**Response Example:**
```json
{
  "success": false,
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "The given data was invalid.",
    "details": {
      "email": ["The email field is required."],
      "password": ["The password field must be at least 8 characters."]
    }
  }
}
```

**Solution:** Fix validation errors and retry.

### 429 Too Many Requests - Rate Limited

**Problem:** You're making too many requests.

**Response Example:**
```json
{
  "success": false,
  "error": {
    "code": "RATE_LIMIT_EXCEEDED",
    "message": "API rate limit exceeded. Try again later."
  }
}
```

**Solution:** Wait and retry. Check rate limit headers in responses.

**Rate Limit Headers:**
```http
X-RateLimit-Limit: 1000
X-RateLimit-Remaining: 998
X-RateLimit-Reset: 1634567890  # Unix timestamp when limit resets
```

## Complete Example Script

Here's a complete bash script to test multiple API endpoints:

```bash
#!/bin/bash

# Configuration
API_BASE="https://api.alumnate.edu/v1"
TENANT_DOMAIN="your-tenant-domain.com"
EMAIL="test.user@institution.edu"
PASSWORD="test-password"

echo "=== Alumni Platform API Test Script ==="

# Login and get token
echo "1. Logging in..."
LOGIN_RESPONSE=$(curl -s -X POST "$API_BASE/auth/login" \
  -H "Content-Type: application/json" \
  -H "X-Tenant-Domain: $TENANT_DOMAIN" \
  -d "{\"email\": \"$EMAIL\", \"password\": \"$PASSWORD\"}")

echo "Login Response: $LOGIN_RESPONSE"

# Extract token
TOKEN=$(echo $LOGIN_RESPONSE | grep -o '"token":"[^"]*' | cut -d'"' -f4)

if [ -z "$TOKEN" ]; then
  echo "❌ Login failed. Check credentials."
  exit 1
fi

echo "✅ Login successful. Token: ${TOKEN:0:20}..."

# Get user profile
echo ""
echo "2. Getting user profile..."
USER_RESPONSE=$(curl -s -X GET "$API_BASE/user" \
  -H "Authorization: Bearer $TOKEN" \
  -H "X-Tenant-Domain: $TENANT_DOMAIN" \
  -H "Content-Type: application/json")

echo "User Profile: $USER_RESPONSE"

# Get alumni directory (first 3)
echo ""
echo "3. Getting alumni directory..."
ALUMNI_RESPONSE=$(curl -s -X GET "$API_BASE/alumni?page=1&per_page=3" \
  -H "Authorization: Bearer $TOKEN" \
  -H "X-Tenant-Domain: $TENANT_DOMAIN" \
  -H "Content-Type: application/json")

echo "Alumni Directory (first 3): $ALUMNI_RESPONSE"

# Create a test post
echo ""
echo "4. Creating test post..."
POST_RESPONSE=$(curl -s -X POST "$API_BASE/posts" \
  -H "Authorization: Bearer $TOKEN" \
  -H "X-Tenant-Domain: $TENANT_DOMAIN" \
  -H "Content-Type: application/json" \
  -d '{
    "content": "Hello from the API! This is an automated test post.",
    "tags": ["api-test", "automated"],
    "privacy": "public"
  }')

echo "Create Post Response: $POST_RESPONSE"

# Get timeline posts
echo ""
echo "5. Getting timeline..."
TIMELINE_RESPONSE=$(curl -s -X GET "$API_BASE/timeline?page=1&per_page=5" \
  -H "Authorization: Bearer $TOKEN" \
  -H "X-Tenant-Domain: $TENANT_DOMAIN" \
  -H "Content-Type: application/json")

echo "Timeline Response: $TIMELINE_RESPONSE"

# Logout
echo ""
echo "6. Logging out..."
LOGOUT_RESPONSE=$(curl -s -X POST "$API_BASE/auth/logout" \
  -H "Authorization: Bearer $TOKEN" \
  -H "X-Tenant-Domain: $TENANT_DOMAIN" \
  -H "Content-Type: application/json")

echo "Logout Response: $LOGOUT_RESPONSE"

echo ""
echo "=== Test completed successfully! ==="
```

## Python Example

```python
import requests
import json

class AlumniAPITester:
    def __init__(self, base_url: str, tenant_domain: str):
        self.base_url = base_url
        self.tenant_domain = tenant_domain
        self.token = None

    def login(self, email: str, password: str):
        """Login and get access token"""
        response = requests.post(f'{self.base_url}/auth/login',
            json={
                'email': email,
                'password': password,
                'remember': False
            },
            headers={'X-Tenant-Domain': self.tenant_domain}
        )

        data = response.json()
        if data['success']:
            self.token = data['data']['token']
            print("✅ Login successful!")
            return True
        else:
            print(f"❌ Login failed: {data['error']['message']}")
            return False

    def get_user(self):
        """Get authenticated user profile"""
        if not self.token:
            print("❌ No token available. Please login first.")
            return

        response = requests.get(f'{self.base_url}/user',
            headers={
                'Authorization': f'Bearer {self.token}',
                'X-Tenant-Domain': self.tenant_domain,
                'Content-Type': 'application/json'
            }
        )

        data = response.json()
        if data['success']:
            print("✅ User Profile:")
            print(json.dumps(data['data'], indent=2))
            return data['data']
        else:
            print(f"❌ Error: {data['error']['message']}")

    def test_alumni_directory(self):
        """Test alumni directory endpoint"""
        if not self.token:
            print("❌ No token available. Please login first.")
            return

        response = requests.get(f'{self.base_url}/alumni?page=1&per_page=3',
            headers={
                'Authorization': f'Bearer {self.token}',
                'X-Tenant-Domain': self.tenant_domain,
                'Content-Type': 'application/json'
            }
        )

        data = response.json()
        if data['success']:
            print(f"✅ Found {data['meta']['total']} alumni members")
            print(f"Showing page {data['meta']['current_page']} of {data['meta']['last_page']}")
            return data['data']
        else:
            print(f"❌ Error: {data['error']['message']}")

# Usage
tester = AlumniAPITester('https://api.alumnate.edu/v1', 'your-tenant-domain.com')

# Login
if tester.login('john.doe@institution.edu', 'password123'):
    # Test various endpoints
    tester.get_user()
    tester.test_alumni_directory()

print("🎉 API testing completed!")
```

## Next Steps

Once you've successfully made your first API calls, you can:

1. **Explore More Endpoints**: Check the [API Reference](../reference/endpoints.md) for all available endpoints
2. **Build Integration**: Start building your application integration
3. **Handle Webhooks**: Set up webhook endpoints for real-time updates
4. **Manage Rate Limits**: Implement proper rate limiting in your application
5. **Monitor Performance**: Use the performance monitoring endpoints
6. **Access SDKs**: Consider using our official [SDK repositories](../../sdks/)

## Support

If you run into issues:

1. Check the [API Reference](../reference/) for correct endpoint usage
2. Review error responses and fix validation issues
3. Ensure your tenant domain is correct
4. Verify your token hasn't expired (3600 seconds / 1 hour)
5. Check rate limit headers on responses

For technical support, contact: developer-support@alumnate.edu