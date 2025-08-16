<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Single Sign-On (SSO) Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains configuration options for SSO integration with
    | institutional systems. It supports SAML 2.0, OAuth 2.0, and OpenID
    | Connect protocols for seamless authentication.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Default SSO Settings
    |--------------------------------------------------------------------------
    |
    | These are the default settings that will be applied to all SSO
    | configurations unless overridden at the provider level.
    |
    */

    'defaults' => [
        'auto_provision' => env('SSO_AUTO_PROVISION', false),
        'auto_update' => env('SSO_AUTO_UPDATE', false),
        'session_timeout' => env('SSO_SESSION_TIMEOUT', 3600), // 1 hour
        'remember_me' => env('SSO_REMEMBER_ME', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | SAML 2.0 Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration options specific to SAML 2.0 authentication.
    |
    */

    'saml' => [
        'sp' => [
            'entityId' => env('SAML_SP_ENTITY_ID', config('app.url')),
            'assertionConsumerService' => [
                'url' => env('SAML_SP_ACS_URL'),
                'binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST',
            ],
            'singleLogoutService' => [
                'url' => env('SAML_SP_SLS_URL'),
                'binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
            ],
            'NameIDFormat' => 'urn:oasis:names:tc:SAML:1.1:nameid-format:emailAddress',
            'x509cert' => env('SAML_SP_X509_CERT'),
            'privateKey' => env('SAML_SP_PRIVATE_KEY'),
        ],
        'security' => [
            'nameIdEncrypted' => false,
            'authnRequestsSigned' => false,
            'logoutRequestSigned' => false,
            'logoutResponseSigned' => false,
            'signMetadata' => false,
            'wantAssertionsSigned' => false,
            'wantNameId' => true,
            'wantAssertionsEncrypted' => false,
            'wantNameIdEncrypted' => false,
            'requestedAuthnContext' => true,
            'requestedAuthnContextComparison' => 'exact',
            'wantXMLValidation' => true,
            'relaxDestinationValidation' => false,
            'destinationStrictlyMatches' => false,
            'allowRepeatAttributeName' => false,
            'rejectUnsolicitedResponsesWithInResponseTo' => false,
            'signatureAlgorithm' => 'http://www.w3.org/2001/04/xmldsig-more#rsa-sha256',
            'digestAlgorithm' => 'http://www.w3.org/2001/04/xmlenc#sha256',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | OAuth 2.0 / OpenID Connect Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration options for OAuth 2.0 and OpenID Connect providers.
    |
    */

    'oauth' => [
        'default_scopes' => ['openid', 'profile', 'email'],
        'state_lifetime' => 300, // 5 minutes
        'pkce' => true, // Use PKCE for security
        'response_type' => 'code',
        'response_mode' => 'query',
    ],

    /*
    |--------------------------------------------------------------------------
    | Attribute Mapping
    |--------------------------------------------------------------------------
    |
    | Default attribute mappings from external identity providers to
    | internal user model fields.
    |
    */

    'attribute_mapping' => [
        'saml' => [
            'name' => 'http://schemas.xmlsoap.org/ws/2005/05/identity/claims/name',
            'email' => 'http://schemas.xmlsoap.org/ws/2005/05/identity/claims/emailaddress',
            'first_name' => 'http://schemas.xmlsoap.org/ws/2005/05/identity/claims/givenname',
            'last_name' => 'http://schemas.xmlsoap.org/ws/2005/05/identity/claims/surname',
            'phone' => 'http://schemas.xmlsoap.org/ws/2005/05/identity/claims/mobilephone',
            'department' => 'http://schemas.xmlsoap.org/ws/2005/05/identity/claims/department',
            'title' => 'http://schemas.xmlsoap.org/ws/2005/05/identity/claims/title',
            'groups' => 'http://schemas.xmlsoap.org/claims/Group',
        ],
        'oidc' => [
            'name' => 'name',
            'email' => 'email',
            'first_name' => 'given_name',
            'last_name' => 'family_name',
            'phone' => 'phone_number',
            'picture' => 'picture',
            'locale' => 'locale',
            'groups' => 'groups',
        ],
        'oauth2' => [
            'name' => 'name',
            'email' => 'email',
            'first_name' => 'given_name',
            'last_name' => 'family_name',
            'avatar' => 'avatar_url',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Role Mapping
    |--------------------------------------------------------------------------
    |
    | Default role mappings from external identity provider roles/groups
    | to internal application roles.
    |
    */

    'role_mapping' => [
        'default_role' => 'Graduate', // Default role for new users
        'admin_roles' => [
            'admin',
            'administrator',
            'super_admin',
            'system_admin',
        ],
        'institution_admin_roles' => [
            'institution_admin',
            'school_admin',
            'university_admin',
        ],
        'student_roles' => [
            'student',
            'current_student',
        ],
        'alumni_roles' => [
            'alumni',
            'graduate',
            'alumnus',
        ],
        'employer_roles' => [
            'employer',
            'recruiter',
            'hr',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | User Provisioning
    |--------------------------------------------------------------------------
    |
    | Configuration for just-in-time user provisioning and updates.
    |
    */

    'provisioning' => [
        'create_missing_users' => env('SSO_CREATE_MISSING_USERS', false),
        'update_existing_users' => env('SSO_UPDATE_EXISTING_USERS', false),
        'sync_roles' => env('SSO_SYNC_ROLES', true),
        'sync_attributes' => env('SSO_SYNC_ATTRIBUTES', true),
        'required_attributes' => ['email', 'name'],
        'default_status' => 'active',
        'email_verification' => false, // Skip email verification for SSO users
    ],

    /*
    |--------------------------------------------------------------------------
    | Session Management
    |--------------------------------------------------------------------------
    |
    | Configuration for SSO session management and single logout.
    |
    */

    'session' => [
        'sso_session_key' => 'sso_session_id',
        'provider_session_key' => 'sso_provider',
        'logout_redirect' => env('SSO_LOGOUT_REDIRECT', '/'),
        'login_redirect' => env('SSO_LOGIN_REDIRECT', '/dashboard'),
        'single_logout' => env('SSO_SINGLE_LOGOUT', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Settings
    |--------------------------------------------------------------------------
    |
    | Security-related configuration for SSO authentication.
    |
    */

    'security' => [
        'validate_issuer' => true,
        'validate_audience' => true,
        'validate_signature' => true,
        'validate_timestamps' => true,
        'clock_skew' => 300, // 5 minutes
        'max_auth_age' => 3600, // 1 hour
        'require_encrypted_assertions' => false,
        'require_signed_assertions' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging and Debugging
    |--------------------------------------------------------------------------
    |
    | Configuration for SSO-related logging and debugging.
    |
    */

    'logging' => [
        'enabled' => env('SSO_LOGGING_ENABLED', true),
        'level' => env('SSO_LOG_LEVEL', 'info'),
        'channel' => env('SSO_LOG_CHANNEL', 'single'),
        'log_requests' => env('SSO_LOG_REQUESTS', false),
        'log_responses' => env('SSO_LOG_RESPONSES', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Error Handling
    |--------------------------------------------------------------------------
    |
    | Configuration for SSO error handling and user experience.
    |
    */

    'error_handling' => [
        'show_detailed_errors' => env('SSO_SHOW_DETAILED_ERRORS', false),
        'fallback_to_local_auth' => env('SSO_FALLBACK_TO_LOCAL', true),
        'error_redirect' => env('SSO_ERROR_REDIRECT', '/login'),
        'max_retry_attempts' => 3,
    ],

];
