# API Error Codes Reference

This document provides a comprehensive reference for all error codes returned by the Alumni Platform API.

## Error Response Format

All API errors are returned in a consistent JSON format:

```json
{
  "success": false,
  "error": {
    "code": "ERROR_CODE",
    "message": "Human readable error message",
    "details": { /* optional additional details */ },
    "trace_id": "req_abc123xyz" /* request tracking ID */
  }
}
```

### Error Response Fields

| Field | Type | Description |
|-------|------|-------------|
| `success` | boolean | Always `false` for errors |
| `error.code` | string | Machine-readable error code |
| `error.message` | string | Human-readable error description |
| `error.details` | object | Additional error context (optional) |
| `error.trace_id` | string | Request tracking ID for support |

---

## HTTP Status Codes

### 2xx Success Codes

| Code | Status | Description |
|------|--------|-------------|
| 200 | OK | Request successful |
| 201 | Created | Resource created successfully |
| 204 | No Content | Request successful, no content returned |

### 4xx Client Error Codes

| Code | Status | Description |
|------|--------|-------------|
| 400 | Bad Request | Invalid request parameters |
| 401 | Unauthorized | Authentication required |
| 403 | Forbidden | Access denied |
| 404 | Not Found | Resource not found |
| 405 | Method Not Allowed | HTTP method not supported |
| 406 | Not Acceptable | Content negotiation failed |
| 409 | Conflict | Resource conflict |
| 410 | Gone | Resource has been removed |
| 415 | Unsupported Media Type | Invalid content type |
| 422 | Unprocessable Entity | Validation failed |
| 423 | Locked | Resource is locked |
| 429 | Too Many Requests | Rate limit exceeded |

### 5xx Server Error Codes

| Code | Status | Description |
|------|--------|-------------|
| 500 | Internal Server Error | Unexpected server error |
| 501 | Not Implemented | Feature not implemented |
| 502 | Bad Gateway | Invalid response from upstream |
| 503 | Service Unavailable | Server temporarily unavailable |
| 504 | Gateway Timeout | Upstream timeout |
| 507 | Insufficient Storage | Storage limit exceeded |

---

## Authentication Errors

### AUTHENTICATION_FAILED

**HTTP Status:** 401  
**Message:** "Authentication failed"

**Details:**
```json
{
  "reason": "invalid_credentials",
  "attempts_remaining": 3
}
```

**Common Causes:**
- Invalid email/password combination
- Expired token
- Malformed authorization header

**Solutions:**
- Verify credentials
- Refresh the token
- Check header format: `Bearer {token}`

---

### TOKEN_EXPIRED

**HTTP Status:** 401  
**Message:** "Access token has expired"

**Details:**
```json
{
  "token_type": "Bearer",
  "expires_in": 0,
  "refresh_token_expires_in": 1296000
}
```

**Solutions:**
- Use the refresh token endpoint to get a new access token
- Re-authenticate if refresh token is also expired

---

### TOKEN_INVALID

**HTTP Status:** 401  
**Message:** "Invalid access token"

**Details:**
```json
{
  "reason": "malformed_token"
}
```

**Solutions:**
- Ensure the token is properly formatted
- Check for extra spaces or characters

---

### TOKEN_REVOKED

**HTTP Status:** 401  
**Message:** "Token has been revoked"

**Details:**
```json
{
  "reason": "user_logout",
  "revoked_at": "2024-01-15T10:30:00Z"
}
```

**Solutions:**
- Re-authenticate to get a new token

---

### UNAUTHORIZED_ACCESS

**HTTP Status:** 401  
**Message:** "You are not authorized to access this resource"

**Details:**
```json
{
  "required_scope": "admin",
  "current_scope": "user"
}
```

**Solutions:**
- Request additional permissions
- Contact administrator for access

---

## Authorization Errors

### FORBIDDEN

**HTTP Status:** 403  
**Message:** "Access denied"

**Details:**
```json
{
  "reason": "insufficient_permissions",
  "required_role": "admin",
  "current_role": "user"
}
```

---

### FORBIDDEN_RESOURCE

**HTTP Status:** 403  
**Message:** "You don't have permission to access this resource"

**Details:**
```json
{
  "resource_type": "User",
  "resource_id": 123,
  "ownership_type": "not_owner"
}
```

---

### FORBIDDEN_TENANT

**HTTP Status:** 403  
**Message:** "Access denied for this tenant"

**Details:**
```json
{
  "current_tenant": "institution-a.edu",
  "resource_tenant": "institution-b.edu"
}
```

---

## Validation Errors

### VALIDATION_ERROR

**HTTP Status:** 422  
**Message:** "The given data was invalid"

**Details:**
```json
{
  "errors": {
    "email": ["The email field is required."],
    "password": ["The password must be at least 8 characters."],
    "graduation_year": ["The graduation year must be a 4-digit number."]
  },
  "failed_rules": {
    "email": ["required", "email"],
    "password": ["required", "min:8"]
  }
}
```

**Common Validation Rules:**
- `required` - Field is required
- `email` - Must be valid email format
- `min:value` - Minimum length/value
- `max:value` - Maximum length/value
- `date` - Must be valid date
- `unique` - Must be unique in database
- `exists` - Must exist in database

---

### UNPROCESSABLE_ENTITY

**HTTP Status:** 422  
**Message:** "The request could not be processed"

**Details:**
```json
{
  "reason": "business_rule_violation",
  "rule": "max_connections_reached",
  "current_count": 500,
  "max_allowed": 500
}
```

---

## Resource Errors

### RESOURCE_NOT_FOUND

**HTTP Status:** 404  
**Message:** "The requested resource was not found"

**Details:**
```json
{
  "resource_type": "User",
  "resource_id": 999,
  "searched_in": "database"
}
```

---

### RESOURCE_EXISTS

**HTTP Status:** 409  
**Message:** "Resource already exists"

**Details:**
```json
{
  "resource_type": "User",
  "duplicate_field": "email",
  "duplicate_value": "user@example.com"
}
```

---

### RESOURCE_DELETED

**HTTP Status:** 410  
**Message:** "The resource has been deleted"

**Details:**
```json
{
  "resource_type": "Post",
  "resource_id": 123,
  "deleted_at": "2024-01-15T10:30:00Z"
}
```

---

### RESOURCE_LOCKED

**HTTP Status:** 423  
**Message:** "The resource is currently locked"

**Details:**
```json
{
  "resource_type": "Post",
  "resource_id": 123,
  "locked_by": 456,
  "locked_at": "2024-01-15T10:30:00Z",
  "expires_at": "2024-01-15T11:00:00Z"
}
```

---

## Rate Limiting Errors

### RATE_LIMIT_EXCEEDED

**HTTP Status:** 429  
**Message:** "API rate limit exceeded"

**Details:**
```json
{
  "limit": 1000,
  "remaining": 0,
  "reset_at": "2024-01-15T11:00:00Z",
  "retry_after": 3600
}
```

**Rate Limit Headers:**
```http
X-RateLimit-Limit: 1000
X-RateLimit-Remaining: 0
X-RateLimit-Reset: 1705316400
Retry-After: 3600
```

---

### RATE_LIMIT_WINDOW_EXCEEDED

**HTTP Status:** 429  
**Message:** "Rate limit window exceeded"

**Details:**
```json
{
  "window_size": 3600,
  "requests_in_window": 1001,
  "max_requests": 1000
}
```

---

## Tenant Errors

### TENANT_NOT_FOUND

**HTTP Status:** 404  
**Message:** "Tenant not found"

**Details:**
```json
{
  "domain": "invalid.institution.edu",
  "searched_for": "tenant_domain"
}
```

---

### TENANT_INACTIVE

**HTTP Status:** 403  
**Message:** "Tenant account is inactive"

**Details:**
```json
{
  "tenant_domain": "institution.edu",
  "status": "suspended",
  "reason": "non_payment"
}
```

---

### TENANT_EXPIRED

**HTTP Status:** 403  
**Message:** "Tenant subscription has expired"

**Details:**
```json
{
  "tenant_domain": "institution.edu",
  "expired_at": "2024-01-01T00:00:00Z",
  "renewal_url": "https://alumnate.edu/renew"
}
```

---

## File Upload Errors

### FILE_UPLOAD_ERROR

**HTTP Status:** 400  
**Message:** "File upload failed"

**Details:**
```json
{
  "reason": "upload_failed",
  "error_code": "UPLOAD_ERR_INI_SIZE",
  "max_size": "10MB"
}
```

---

### FILE_TYPE_NOT_ALLOWED

**HTTP Status:** 400  
**Message:** "File type not allowed"

**Details:**
```json
{
  "file_type": "exe",
  "allowed_types": ["jpg", "png", "pdf", "doc", "docx"]
}
```

---

### FILE_TOO_LARGE

**HTTP Status:** 400  
**Message:** "File size exceeds limit"

**Details:**
```json
{
  "file_size": "15MB",
  "max_size": "10MB",
  "uploaded_file": "document.pdf"
}
```

---

## Analytics Errors

### ANALYTICS_ERROR

**HTTP Status:** 500  
**Message:** "Analytics processing error"

**Details:**
```json
{
  "error_type": "data_processing",
  "query": "SELECT * FROM analytics_events",
  "reason": "database_timeout"
}
```

---

### INSUFFICIENT_DATA

**HTTP Status:** 422  
**Message:** "Insufficient data for analysis"

**Details:**
```json
{
  "required_records": 100,
  "available_records": 25,
  "timeframe": "30_days"
}
```

---

### INVALID_TIMEFRAME

**HTTP Status:** 400  
**Message:** "Invalid time range specified"

**Details:**
```json
{
  "reason": "start_date_after_end_date",
  "start_date": "2024-02-01",
  "end_date": "2024-01-01"
}
```

---

### ANALYSIS_IN_PROGRESS

**HTTP Status:** 409  
**Message:** "Analysis is still processing"

**Details:**
```json
{
  "analysis_id": "analysis_123",
  "estimated_completion": "2024-01-15T11:00:00Z"
}
```

---

## Webhook Errors

### WEBHOOK_DELIVERY_FAILED

**HTTP Status:** 500  
**Message:** "Webhook delivery failed"

**Details:**
```json
{
  "webhook_id": 123,
  "endpoint": "https://example.com/webhook",
  "error": "connection_timeout",
  "attempts": 3,
  "next_attempt": "2024-01-15T11:00:00Z"
}
```

---

### WEBHOOK_INVALID_SIGNATURE

**HTTP Status:** 401  
**Message:** "Invalid webhook signature"

**Details:**
```json
{
  "expected_signature": "sha256=...",
  "received_signature": "sha256=...",
  "timestamp_age": 3600
}
```

---

## Payment/Donation Errors

### PAYMENT_FAILED

**HTTP Status:** 402  
**Message:** "Payment processing failed"

**Details:**
```json
{
  "error_code": "card_declined",
  "decline_reason": "insufficient_funds",
  "gateway_response": "Do not honor"
}
```

---

### DONATION_LIMIT_EXCEEDED

**HTTP Status:** 403  
**Message:** "Donation limit exceeded"

**Details:**
```json
{
  "donation_type": "annual",
  "current_total": 10000,
  "max_allowed": 10000,
  "limit_type": "per_donor"
}
```

---

## Server Errors

### SERVER_ERROR

**HTTP Status:** 500  
**Message:** "An unexpected error occurred"

**Details:**
```json
{
  "error_id": "err_abc123",
  "component": "database",
  "severity": "critical"
}
```

**Solutions:**
- Retry the request
- Contact support with the error_id

---

### MAINTENANCE_MODE

**HTTP Status:** 503  
**Message:** "Service is under maintenance"

**Details:**
```json
{
  "scheduled_until": "2024-01-15T12:00:00Z",
  "reason": "database_upgrade",
  "affected_endpoints": ["api"]
}
```

---

### SERVICE_UNAVAILABLE

**HTTP Status:** 503  
**Message:** "Service temporarily unavailable"

**Details:**
```json
{
  "reason": "high_load",
  "estimated_wait": 60,
  "queue_position": 5
}
```

---

## Error Handling Best Practices

### 1. Always Check Success Field

```javascript
const response = await api.request('/users');

if (response.success) {
  // Handle success
  const data = response.data;
} else {
  // Handle error
  console.error('Error:', response.error.code, response.error.message);
}
```

### 2. Implement Retry Logic for 5xx Errors

```javascript
async function fetchWithRetry(url, options, maxRetries = 3) {
  for (let attempt = 1; attempt <= maxRetries; attempt++) {
    try {
      const response = await fetch(url, options);
      
      if (response.status >= 500) {
        throw new Error(`Server error: ${response.status}`);
      }
      
      return response;
    } catch (error) {
      if (attempt === maxRetries) {
        throw error;
      }
      
      // Exponential backoff
      await new Promise(r => setTimeout(r, Math.pow(2, attempt) * 1000));
    }
  }
}
```

### 3. Handle Rate Limiting

```javascript
async function handleRateLimit(response) {
  const resetTime = new Date(response.headers['X-RateLimit-Reset'] * 1000);
  const waitTime = resetTime - new Date();
  
  console.log(`Rate limited. Retry after ${waitTime}ms`);
  
  await new Promise(r => setTimeout(r, waitTime + 1000));
}
```

### 4. Validate Before Sending

```javascript
function validateUserInput(data) {
  const errors = [];
  
  if (!data.email) {
    errors.push({ field: 'email', message: 'Email is required' });
  }
  
  if (data.password && data.password.length < 8) {
    errors.push({ field: 'password', message: 'Password must be at least 8 characters' });
  }
  
  return errors;
}
```

---

## Getting Help

If you encounter errors not listed here:

1. Check the `trace_id` in the error response
2. Review your request parameters
- Search existing issues in our [GitHub repository](https://github.com/alumnate/api/issues)
- Contact API Support: api-support@alumnate.edu
- Include the error code, message, trace_id, and request details

---

## Changelog

| Date | Version | Changes |
|------|---------|---------|
| 2024-01-15 | 1.0.0 | Initial error code documentation |
| 2024-01-20 | 1.1.0 | Added analytics error codes |
| 2024-02-01 | 1.2.0 | Added webhook error codes |
