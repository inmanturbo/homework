# Homework Package - Development Log

A Laravel package providing WorkOS-compatible OAuth functionality using Laravel Passport.

## Overview

This package enables Laravel applications to act as a WorkOS-compatible OAuth provider using Laravel Passport as the underlying OAuth2 server. It provides endpoints, views, and customization points that match the WorkOS API contract.

## Development Phases

### Phase 1: Core OAuth Flow
- Basic WorkOS-compatible endpoints (`/user_management/authorize`, `/user_management/authenticate`, etc.)
- Auto-approval for first-party clients via custom Client model
- Authorization view matching WorkOS UX
- Request classes for handling authentication flows

### Phase 2: Client Management
- `ClientService` for creating first-party WorkOS clients
- `homework:create-client` Artisan command for easy client setup
- Automatic configuration output for client apps

### Phase 3: RSA Key Support (JWKS)
- Updated JWKS endpoint to use Passport's RSA keys (RS256)
- Extracts modulus (n) and exponent (e) from `oauth-public.key`
- Base64url encoding for JWKS format
- `/sso/jwks/{clientId}` endpoint

### Phase 4: WorkOS User Integration & Organization Support
- `Laravel\WorkOS\User` object integration
- `UserResponseContract` for customizable user transformations
- Default `UserResponse` implementation with:
  - Automatic name splitting (first/last)
  - Avatar detection from multiple attributes
  - Organization ID support
  - Optional `workosUser()` method on User model

### Phase 5: Multi-Organization Selection Flow
**Goal:** Support WorkOS-like organization selection without database migrations

**Implementation:**
- Created `OrganizationProviderContract` interface for flexible organization sources
- Built organization selection middleware (`RequireOrganizationSelection`)
- Dark-mode organization selection UI (`select-organization.blade.php`)
- Cache-based organization persistence through OAuth flow
- Organization ID flows from selection → cache → token exchange → client app

**Key Files:**
- `src/Contracts/OrganizationProviderContract.php`
- `src/Http/Middleware/RequireOrganizationSelection.php`
- `resources/views/auth/select-organization.blade.php`
- `routes/workos.php` - Organization selection handler

**Flow:**
1. User authenticates
2. Middleware checks if user has 2+ organizations (via `OrganizationProviderContract`)
3. If yes, shows organization selection screen
4. Selected org stored in session + cache (keyed by user ID)
5. OAuth flow completes with authorization code
6. Client exchanges code for token
7. `UserResponse` retrieves org from cache
8. Response includes `organization_id` at top level
9. Client receives `Laravel\WorkOS\User` with `organizationId` populated

### Phase 6: Authentication Response Customization
**Goal:** Provide clean architecture for customizing entire authentication response

**Problem:**
- `organization_id` needed at top level of response (WorkOS SDK requirement)
- Previous approach only allowed user object customization
- Resulted in hacky extraction/movement of fields

**Solution:**
Created two-level contract system:
1. `UserResponseContract` - Transforms user object
2. `AuthenticationResponseContract` - Builds complete authentication response

**New Files:**
- `src/Contracts/AuthenticationResponseContract.php`
- `src/Support/AuthenticationResponse.php`

**Benefits:**
- Clean separation of concerns
- Full control over response structure
- Easy to add top-level fields (organization_id, impersonator, etc.)
- Developers can bind custom implementations independently

**Response Structure:**
```json
{
  "access_token": "...",
  "refresh_token": "...",
  "access_token_expires_at": 1234567890,
  "refresh_token_expires_at": 1234567890,
  "organization_id": "org_123",
  "user": {
    "object": "user",
    "id": "...",
    "email": "...",
    "first_name": "...",
    "last_name": "...",
    "email_verified": true,
    "profile_picture_url": null,
    "created_at": "...",
    "updated_at": "..."
  }
}
```

## File Structure

```
src/
├── Console/Commands/
│   └── CreateWorkOsClientCommand.php
├── Contracts/
│   ├── AuthenticationResponseContract.php
│   ├── OrganizationProviderContract.php
│   └── UserResponseContract.php
├── Http/
│   ├── Middleware/
│   │   ├── AutoApproveFirstPartyClients.php
│   │   └── RequireOrganizationSelection.php
│   └── Requests/
│       ├── AuthenticateRequest.php
│       ├── GetUserRequest.php
│       └── JwksRequest.php
├── Models/
│   └── Client.php
├── Services/
│   └── ClientService.php
├── Support/
│   ├── AuthenticationResponse.php
│   └── UserResponse.php
└── HomeworkServiceProvider.php

resources/
└── views/
    └── auth/
        ├── authorize.blade.php
        └── select-organization.blade.php

routes/
└── workos.php
```

## Requirements

- PHP 8.1+
- Laravel 11.x
- Laravel Passport 12.x
- Laravel WorkOS ^0.5.0 (for organization support)

## Testing

The package includes tests for:
- Service provider registration
- Middleware functionality
- Route registration
- Contract bindings

Run tests: `vendor/bin/pest`

## Customization Points

1. **User Response**: Bind custom `UserResponseContract` implementation
2. **Authentication Response**: Bind custom `AuthenticationResponseContract` implementation
3. **Organization Provider**: Bind custom `OrganizationProviderContract` implementation
4. **Client Model**: Extend or replace the `Client` model
5. **Views**: Publish and customize authorization and organization selection views

## Notes

- Organization selection uses cache (5-minute TTL) to persist selection through OAuth flow
- Cache key format: `org_selection:{user_id}`
- Cache is automatically cleared after first use to prevent reuse
- No database migrations required for organization support
- First-party clients (without `user_id`) are auto-approved
