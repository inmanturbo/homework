# inmanturbo/homework

[![Tests](https://github.com/inmanturbo/homework/workflows/Tests/badge.svg)](https://github.com/inmanturbo/homework/actions)
[![Check PHP code style](https://github.com/inmanturbo/homework/workflows/Check%20PHP%20code%20style/badge.svg)](https://github.com/inmanturbo/homework/actions)

A Laravel Passport extension that provides WorkOS UserManagement API compatibility, allowing you to use Laravel Passport as a drop-in replacement for WorkOS authentication services.

## Quick Start

```bash
# 1. Install the package
composer require inmanturbo/homework

# 2. Install Passport (if not already installed)
php artisan passport:install

# 3. Create a WorkOS-compatible OAuth client
php artisan homework:create-client http://your-app.test/authenticate --name="My App"

# 4. Run the auto-install script in your client project (or manually copy env vars)
# 5. Done! Your Laravel app is now a WorkOS-compatible OAuth provider
```

## Table of Contents

- [Quick Start](#quick-start)
- [Overview](#overview)
- [Features](#features)
- [Related Packages](#related-packages)
- [Requirements](#requirements)
- [Installation](#installation)
- [Usage](#usage)
  - [Setting Up OAuth Clients](#setting-up-oauth-clients)
  - [Client Application Setup](#client-application-setup)
  - [Authentication Flow](#authentication-flow)
  - [Authorization Flow](#authorization-flow)
  - [Customizing User Responses](#customizing-user-responses)
  - [Customizing Authentication Responses](#customizing-authentication-responses)
  - [Token Claims, Permissions & Metadata](#token-claims-permissions--metadata)
  - [Organization Selection Flow](#organization-selection-flow)
- [Response Format](#response-format)
- [Testing](#testing)
- [Contributing](#contributing)
- [License](#license)

## Overview

This package extends Laravel Passport to provide WorkOS-compatible OAuth endpoints, enabling you to self-host your authentication service while maintaining full compatibility with the Laravel WorkOS SDK client applications.

**Key Benefits:**
- Self-host your authentication instead of relying on WorkOS.com
- Full control over user data and authentication flow
- No vendor lock-in while maintaining SDK compatibility
- Support for advanced features like multi-organization selection

## Features

- **WorkOS UserManagement API Compatibility**: Implements WorkOS UserManagement authentication endpoints
- **OAuth 2.0 Authorization Code Flow**: Full implementation using Laravel Passport
- **Optional Auto-Approval**: Choose between standard OAuth authorization flow or automatic approval for first-party clients
- **RSA Key Support (RS256)**: JWKS endpoint for JWT token verification
- **Organization Support**: Multi-organization selection flow without database migrations
- **Customizable Responses**: Flexible user and authentication response transformations
- **Headless Views**: Customize authorization and organization selection screens
- **Easy Client Creation**: Artisan command and service for quick OAuth client setup
- **Auto-Install Script**: One-liner bash script to automatically configure client applications
- **Session Management**: Preserves intended URLs through OAuth flow
- **Drop-in Replacement**: Works seamlessly with existing Laravel WorkOS integrations

## Related Packages

**[Homework Organizations](https://github.com/inmanturbo/homework-organizations)** - Database-per-organization multi-tenancy for Laravel applications. Provides automatic organization database switching, storage isolation, and queue context propagation. Perfect companion for building multi-organization SaaS applications with Homework OAuth authentication.

## Requirements

- PHP 8.2 or higher
- Laravel 11.x or 12.x
- Laravel Passport 13.x
- Laravel WorkOS ^0.5.0 (for organization support on client apps)

## Installation

### Step 1: Install the Package

Via Composer:

```bash
composer require inmanturbo/homework
```

Or for local development, add to your `composer.json`:

```json
{
    "repositories": [
        {
            "type": "path",
            "url": "/path/to/homework",
            "options": {
                "symlink": true
            }
        }
    ],
    "require": {
        "inmanturbo/homework": "dev-main"
    }
}
```

### Step 2: Install Laravel Passport

If you haven't already installed Passport:

```bash
php artisan passport:install
```

That's it! The package automatically registers all necessary routes and views.

## Usage

### Setting Up OAuth Clients

#### Option 1: Using Artisan Command (Easiest)

Create a first-party WorkOS client using the provided Artisan command:

```bash
php artisan homework:create-client http://your-app.test/authenticate --name="My Application"
```

This will output ready-to-copy environment variables and an auto-install script:

```
✅ WorkOS client created successfully!

  Client ID ............................. 0199b362-5239-723c-bbd7-1c61e492699a
  Client Secret ..................... XrObuSKCZ3HwkcAV6DWAu00K2CAnLhGjklhSk8IU

📋 Copy these environment variables to your client application:

WORKOS_CLIENT_ID=0199b362-5239-723c-bbd7-1c61e492699a
WORKOS_API_KEY=XrObuSKCZ3HwkcAV6DWAu00K2CAnLhGjklhSk8IU
WORKOS_REDIRECT_URL=http://your-app.test/authenticate
WORKOS_BASE_URL=http://your-oauth-server.test/

🚀 To auto-install in your client project, paste and run:

/bin/bash -c "$(curl -fsSL 'http://your-oauth-server.test/workos_client/{client_id}/install?secret={secret}&redirect_uri=http%3A%2F%2Fyour-app.test%2Fauthenticate')"
```

**Auto-Install Script:**

The one-liner bash command will automatically:
- Add/update environment variables in your `.env` file
- Add `base_url` to your `config/services.php` workos configuration
- Create `app/Providers/WorkOsServiceProvider.php` with proper WorkOS SDK initialization
- Register the service provider in your application

Simply cd into your client Laravel project and paste the command!

#### Option 2: Using the ClientService

Programmatically create a first-party WorkOS-compatible client:

```php
use Inmanturbo\Homework\Services\ClientService;

$config = ClientService::createFirstPartyWorkOsClient(
    redirectUris: 'http://your-app.test/authenticate',
    name: 'My Application'
);

// Output the configuration
print_r($config['env_config']);
```

This will return all the configuration details you need:

```php
[
    'client_id' => '0199b362-5239-723c-bbd7-1c61e492699a',
    'client_secret' => 'XrObuSKCZ3HwkcAV6DWAu00K2CAnLhGjklhSk8IU',
    'redirect_uris' => ['http://your-app.test/authenticate'],
    'base_url' => 'http://your-oauth-server.test/',
    'env_config' => [
        'WORKOS_CLIENT_ID' => '0199b362-5239-723c-bbd7-1c61e492699a',
        'WORKOS_API_KEY' => 'XrObuSKCZ3HwkcAV6DWAu00K2CAnLhGjklhSk8IU',
        'WORKOS_REDIRECT_URL' => 'http://your-app.test/authenticate',
        'WORKOS_BASE_URL' => 'http://your-oauth-server.test/',
    ]
]
```

**Multiple Redirect URIs:**

```php
$config = ClientService::createFirstPartyWorkOsClient(
    redirectUris: [
        'http://your-app.test/authenticate',
        'http://localhost:3000/authenticate',
    ],
    name: 'My Application'
);
```

#### Option 3: Using Passport's Artisan Commands

You can also use Laravel Passport's built-in Artisan commands:

**Public Client (No Client Secret)**

Public clients are suitable for SPAs and mobile apps that cannot securely store secrets:

```bash
php artisan passport:client --public --name="My Client App"
```

When prompted, enter your redirect URI (e.g., `http://your-app.test/authenticate`).

**Confidential Client (With Client Secret)**

Confidential clients are suitable for server-side applications that can securely store secrets:

```bash
php artisan passport:client --name="My Server App"
```

When prompted:
1. Enter your redirect URI (e.g., `http://your-app.test/authenticate`)
2. Choose whether to enable device authorization flow (usually `no`)

The command will output:
- **Client ID**: Use this as `WORKOS_CLIENT_ID`
- **Client Secret**: Use this as `WORKOS_API_KEY`

**Note**: First-party clients (those without a `user_id`) can automatically bypass the authorization screen when using the optional auto-approval features (see Authorization Flow section below).

### Client Application Configuration

Configure your client application's `.env` to point to your OAuth server:

```env
WORKOS_CLIENT_ID=your_client_id
WORKOS_API_KEY=your_client_secret
WORKOS_REDIRECT_URL=http://your-app.test/authenticate
WORKOS_BASE_URL=http://your-oauth-server.test/
```

Then configure the WorkOS SDK to use your OAuth server in `app/Providers/AppServiceProvider.php`:

```php
use WorkOS\WorkOS;

public function boot()
{
    WorkOS::setApiKey(config('services.workos.secret'));

    $baseUrl = config('services.workos.base_url');
    if ($baseUrl && $baseUrl !== 'https://api.workos.com/') {
        WorkOS::setApiBaseUrl($baseUrl);
    }
}
```

### Authentication Flow

The package provides these WorkOS-compatible endpoints:

- `GET /user_management/authorize` - OAuth authorization endpoint (redirects to Passport's /oauth/authorize)
- `POST /user_management/authenticate` - Token exchange endpoint
- `POST /user_management/authenticate_with_refresh_token` - Refresh token endpoint
- `GET /user_management/users/{userId}` - Get user information
- `GET /sso/jwks/{clientId}` - JWKS endpoint for token verification

### Authorization Flow

By default, the package uses the standard OAuth authorization flow where users see an authorization screen asking them to approve access. You have three options for customizing this behavior:

#### Option 1: Standard OAuth Flow (Default)

No configuration needed. Users will see the authorization screen on first login.

#### Option 2: Auto-Approval Using Client Model (Recommended)

Use the provided Client model or trait to automatically approve first-party clients:

**Using the Provided Client Model:**

```php
use Inmanturbo\Homework\Models\Client;
use Laravel\Passport\Passport;

// In your AppServiceProvider or a custom service provider
public function boot()
{
    Passport::useClientModel(Client::class);
}
```

**Using Your Own Client Model:**

If you already have a custom Client model, add the trait:

```php
namespace App\Models;

use Inmanturbo\Homework\Concerns\SkipsAuthorizationForFirstPartyClients;
use Laravel\Passport\Client as PassportClient;

class Client extends PassportClient
{
    use SkipsAuthorizationForFirstPartyClients;

    // Your custom model code...
}
```

Then configure Passport to use your model:

```php
use App\Models\Client;
use Laravel\Passport\Passport;

public function boot()
{
    Passport::useClientModel(Client::class);
}
```

#### Option 3: Auto-Approval Using Middleware

Alternatively, use the provided middleware (kept for backward compatibility):

```php
use Inmanturbo\Homework\Http\Middleware\AutoApproveFirstPartyClients;

// In your AppServiceProvider or a custom service provider
public function boot()
{
    // Register the middleware alias
    $this->app['router']->aliasMiddleware(
        'auto-approve-first-party',
        AutoApproveFirstPartyClients::class
    );

    // Add to web middleware group
    $this->app['router']->pushMiddlewareToGroup(
        'web',
        AutoApproveFirstPartyClients::class
    );
}
```

**How Auto-Approval Works:**

When using either the Client model approach (Option 2) or middleware approach (Option 3):
- Users are not shown an authorization screen for your own applications (first-party clients)
- The OAuth flow completes transparently
- Third-party clients (those with a `user_id`) still see the authorization prompt
- First-party clients are identified by having an empty `user_id` field

**Which approach should you use?**

- **Option 1 (Standard Flow)**: Best for maximum security and transparency, or if you want users to explicitly approve access
- **Option 2 (Client Model/Trait)**: Cleanest approach, uses Passport's native functionality, recommended for most use cases
- **Option 3 (Middleware)**: Provides more control over the approval logic, useful if you need custom approval rules

### Custom Authorization View (Optional)

The package includes a modern, dark-mode-enabled authorization view that you can optionally use. This view is displayed when users need to approve access (when not using auto-approval, or for third-party clients).

#### Using the Provided Authorization View

Configure Passport to use the package's authorization view:

```php
use Laravel\Passport\Passport;

// In your AppServiceProvider or a custom service provider
public function boot()
{
    Passport::authorizationView('homework::auth.authorize');
}
```

**View Features:**
- Modern, clean design
- Dark mode support (respects system preference and localStorage)
- Responsive layout
- Clear permission display with icons
- Professional authorization/cancel buttons
- Shows signed-in user email

#### Customizing the View

To use your own authorization view, simply don't configure Passport to use the homework view, and create your own view according to [Passport's documentation](https://laravel.com/docs/passport#customizing-the-authorization-view).

Alternatively, you can override the package's view by creating a file at `resources/views/vendor/homework/auth/authorize.blade.php` in your application - Laravel will automatically use your version instead of the package's version.

### Customizing User Responses

The package provides a flexible way to customize how user data is returned in WorkOS-compatible responses. This is useful for adding support for organizations, custom avatars, or any other user-related data.

#### Using the workosUser() Method (Recommended)

The simplest way to customize the user response is by adding a `workosUser()` method to your User model:

```php
use Laravel\WorkOS\User as WorkOsUser;

class User extends Authenticatable
{
    public function workosUser(): WorkOsUser
    {
        return new WorkOsUser(
            id: (string) $this->id,
            organizationId: $this->organization_id, // Support for organizations
            firstName: $this->first_name,
            lastName: $this->last_name,
            email: $this->email,
            avatar: $this->avatar_url,
        );
    }
}
```

The default `UserResponse` will:
1. Check if your User model has a `workosUser()` method
2. If found, use it to create the WorkOS User object
3. If not found, create one automatically from common attributes
4. Automatically include `organization_id` in the response if present
5. Handle avatar URL detection from `avatar_url`, `profile_picture_url`, or `avatar` attributes

#### Creating a Custom User Response

1. Create a class that implements `UserResponseContract`:

```php
namespace App\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Inmanturbo\Homework\Contracts\UserResponseContract;

class CustomUserResponse implements UserResponseContract
{
    public function transform(Authenticatable $user): array
    {
        return [
            'object' => 'user',
            'id' => (string) $user->id,
            'email' => $user->email,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email_verified' => ! is_null($user->email_verified_at),
            'profile_picture_url' => $user->avatar_url,
            'created_at' => $user->created_at->toISOString(),
            'updated_at' => $user->updated_at->toISOString(),

            // Add custom fields
            'organization_id' => $user->organization_id,
            'role' => $user->role,
        ];
    }
}
```

2. Bind your custom class in a service provider:

**Option 1: Direct Binding**

```php
use App\Services\CustomUserResponse;
use Inmanturbo\Homework\Contracts\UserResponseContract;

public function register()
{
    $this->app->bind(UserResponseContract::class, CustomUserResponse::class);
}
```

**Option 2: Using Homework Facade (Recommended)**

```php
use App\Services\CustomUserResponse;
use Inmanturbo\Homework\Homework;

public function boot()
{
    Homework::useUserResponse(CustomUserResponse::class);
}
```

The custom user response will now be used for:
- `/user_management/authenticate` endpoint
- `/user_management/authenticate_with_refresh_token` endpoint
- `/user_management/users/{userId}` endpoint

#### Extending the Default Response

You can also extend the default `UserResponse` class:

```php
namespace App\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Inmanturbo\Homework\Support\UserResponse;

class CustomUserResponse extends UserResponse
{
    public function transform(Authenticatable $user): array
    {
        $response = parent::transform($user);

        // Add additional fields
        $response['organization_id'] = $user->organization_id ?? null;
        $response['metadata'] = $user->metadata ?? [];

        return $response;
    }
}
```

### Customizing Authentication Responses

For advanced use cases, you can customize the entire authentication response structure (including top-level fields like `organization_id`, `impersonator`, etc.):

#### Creating a Custom Authentication Response

1. Create a class that implements `AuthenticationResponseContract`:

```php
namespace App\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Inmanturbo\Homework\Contracts\AuthenticationResponseContract;
use Inmanturbo\Homework\Contracts\UserResponseContract;

class CustomAuthenticationResponse implements AuthenticationResponseContract
{
    public function __construct(
        protected UserResponseContract $userResponse
    ) {
    }

    public function build(Authenticatable $user, array $tokenData): array
    {
        $userResponse = $this->userResponse->transform($user);

        // Extract organization_id to top level (WorkOS SDK requirement)
        $organizationId = $userResponse['organization_id'] ?? null;
        unset($userResponse['organization_id']);

        return [
            'access_token' => $tokenData['access_token'],
            'refresh_token' => $tokenData['refresh_token'],
            'access_token_expires_at' => time() + ($tokenData['expires_in'] ?? 3600),
            'refresh_token_expires_at' => time() + (30 * 24 * 3600),
            'organization_id' => $organizationId,
            'user' => $userResponse,

            // Add custom top-level fields
            'impersonator' => $user->impersonator ?? null,
        ];
    }
}
```

2. Bind your custom class in a service provider:

**Option 1: Direct Binding**

```php
use App\Services\CustomAuthenticationResponse;
use Inmanturbo\Homework\Contracts\AuthenticationResponseContract;

public function register()
{
    $this->app->bind(AuthenticationResponseContract::class, CustomAuthenticationResponse::class);
}
```

**Option 2: Using Homework Facade (Recommended)**

```php
use App\Services\CustomAuthenticationResponse;
use Inmanturbo\Homework\Homework;

public function boot()
{
    Homework::useAuthenticationResponse(CustomAuthenticationResponse::class);
}
```

**Note:** The `organization_id` must be at the top level of the response for the WorkOS SDK to properly extract it. The default `AuthenticationResponse` handles this automatically.

### Token Claims, Permissions & Metadata

The package supports adding custom claims, permissions, roles, and metadata to authentication tokens, following WorkOS patterns. This allows you to enrich tokens with authorization data without implementing the actual authorization logic in the package.

#### WorkOS org_id Pattern

Following WorkOS conventions:
- If a user belongs to **exactly one organization**, `org_id` is included in the response
- If a user belongs to **zero or multiple organizations**, `org_id` is omitted (keeping the server stateless)
- For multi-org users, organization selection happens on the client side

#### Creating a Token Claims Provider

1. Create a class that implements `TokenClaimsProviderContract`:

```php
namespace App\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Inmanturbo\Homework\Contracts\TokenClaimsProviderContract;

class UserTokenClaimsProvider implements TokenClaimsProviderContract
{
    /**
     * Get the organization IDs for the user.
     * Return array of organization IDs the user belongs to.
     */
    public function getOrganizations(Authenticatable $user): array
    {
        // Example: Query user's organizations
        return $user->organizations()->pluck('id')->toArray();

        // Or return a single organization
        // return $user->organization_id ? [$user->organization_id] : [];
    }

    /**
     * Get the permissions for the user.
     * Return null to omit permissions from response.
     */
    public function getPermissions(Authenticatable $user): ?array
    {
        // Example: Get from a permissions system
        return $user->getAllPermissions()->pluck('name')->toArray();

        // Or return null if not using permissions
        // return null;
    }

    /**
     * Get the roles for the user.
     * Return null to omit roles from response.
     */
    public function getRoles(Authenticatable $user): ?array
    {
        // Example: Get user roles
        return $user->roles()->pluck('name')->toArray();

        // Or return null if not using roles
        // return null;
    }

    /**
     * Get custom metadata for the user.
     * These will be merged into the user object.
     * Return null or empty array to omit.
     */
    public function getMetadata(Authenticatable $user): ?array
    {
        return [
            'department' => $user->department,
            'employee_id' => $user->employee_id,
            'custom_claim' => 'custom_value',
        ];

        // Or return null if no custom metadata
        // return null;
    }
}
```

2. Bind your provider in a service provider:

```php
use App\Services\UserTokenClaimsProvider;
use Inmanturbo\Homework\Homework;

public function boot()
{
    Homework::useTokenClaimsProvider(UserTokenClaimsProvider::class);
}
```

#### Response Structure

When a `TokenClaimsProvider` is bound, the authentication response will include:

**Single Organization User:**
```json
{
  "access_token": "...",
  "refresh_token": "...",
  "org_id": "org_123",
  "user": {
    "id": "user_123",
    "email": "user@example.com",
    "permissions": ["read:users", "write:posts"],
    "roles": ["admin", "member"],
    "department": "Engineering",
    "employee_id": "EMP001"
  }
}
```

**Multi-Organization User (stateless):**
```json
{
  "access_token": "...",
  "refresh_token": "...",
  "user": {
    "id": "user_123",
    "email": "user@example.com",
    "permissions": ["read:users", "write:posts"],
    "roles": ["admin", "member"],
    "department": "Engineering"
  }
}
```

Note: No `org_id` is included when the user belongs to multiple organizations, keeping the server stateless.

#### Implementation Notes

- **Optional**: All methods can return `null` to omit that claim type
- **Flexible**: You decide how to fetch permissions/roles (database, cache, external API, etc.)
- **Stateless**: The package doesn't manage permissions - it just includes them if provided
- **Custom Metadata**: Any additional fields are merged directly into the user object
- **Performance**: Consider caching expensive queries in your provider implementation

### Organization Selection Flow

The package supports multi-organization users with an optional organization selection step after login. This is useful when users belong to multiple organizations and need to choose which one to access.

#### Enabling Organization Selection

1. Create a class that implements `OrganizationProviderContract`:

```php
namespace App\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Inmanturbo\Homework\Contracts\OrganizationProviderContract;

class UserOrganizationProvider implements OrganizationProviderContract
{
    public function getOrganizationsForUser(Authenticatable $user): array
    {
        return [
            [
                'id' => 'org_123',
                'name' => 'Acme Corp',
                'logo_url' => 'https://example.com/logos/acme.png', // optional
            ],
            [
                'id' => 'org_456',
                'name' => 'Tech Startup Inc',
            ],
        ];

        // Or from a relationship:
        // return $user->organizations->map(fn($org) => [
        //     'id' => $org->id,
        //     'name' => $org->name,
        //     'logo_url' => $org->logo_url, // optional - defaults to building icon
        // ])->toArray();
    }
}
```

2. Bind the provider using `Homework::useOrganizationProvider()`:

```php
use App\Services\UserOrganizationProvider;
use Inmanturbo\Homework\Homework;

public function boot()
{
    Homework::useOrganizationProvider(UserOrganizationProvider::class);
}
```

3. Add the middleware to your web middleware group:

```php
use Inmanturbo\Homework\Http\Middleware\RequireOrganizationSelection;

public function boot()
{
    $this->app['router']->pushMiddlewareToGroup(
        'web',
        RequireOrganizationSelection::class
    );
}
```

**How it works:**
- If a user belongs to multiple organizations, they'll see a selection screen after login
- The selected `organization_id` is stored in the session and cache
- The `organization_id` is automatically included in the authentication response
- If a user belongs to 0 or 1 organization, the selection step is skipped

#### Customizing the Organization Selection View

Similar to Passport's authorization view, you can customize the organization selection screen:

**Option 1: Using a Custom View Callback (Headless)**

```php
use Inmanturbo\Homework\Homework;

public function boot()
{
    Homework::organizationSelectionView(fn ($data) => view('auth.select-organization', $data));

    // $data contains: organizations, user, state, clientId, redirectUri, responseType
}
```

**Option 2: Override the Package View**

Create a file at `resources/views/vendor/homework/auth/select-organization.blade.php` - Laravel will automatically use your version instead of the package's version.

**Default View Features:**
- Modern, clean design matching the authorization view
- Dark mode support (respects system preference and localStorage)
- Responsive layout
- Organization cards with icons
- Shows signed-in user email

### Preserving Intended URLs (Client Application)

In your client application, use Laravel's `intended()` redirect to preserve the URL users were trying to access:

```php
// In your routes/auth.php
Route::get('authenticate', function (AuthKitAuthenticationRequest $request) {
    return tap(redirect()->intended(route('dashboard')), fn () => $request->authenticate());
})->middleware(['guest']);
```

## Response Format

The package returns WorkOS-compatible user responses:

```json
{
    "access_token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
    "refresh_token": "def50200642ba2e04e3e1b5c6...",
    "access_token_expires_at": 1735412345,
    "refresh_token_expires_at": 1738004345,
    "user": {
        "object": "user",
        "id": "1",
        "email": "user@example.com",
        "first_name": "John",
        "last_name": "Doe",
        "email_verified": true,
        "profile_picture_url": null,
        "created_at": "2025-01-01T00:00:00.000Z",
        "updated_at": "2025-01-01T00:00:00.000Z"
    }
}
```

## Architecture

### Directory Structure

```
homework/
├── .github/
│   └── workflows/
│       ├── tests.yml
│       └── pint.yml
├── src/
│   ├── Concerns/
│   │   └── SkipsAuthorizationForFirstPartyClients.php
│   ├── Console/
│   │   └── Commands/
│   │       └── CreateWorkOsClientCommand.php
│   ├── Contracts/
│   │   ├── AuthenticationResponseContract.php
│   │   ├── OrganizationProviderContract.php
│   │   ├── TokenClaimsProviderContract.php
│   │   └── UserResponseContract.php
│   ├── Http/
│   │   ├── Middleware/
│   │   │   ├── AutoApproveFirstPartyClients.php
│   │   │   └── RequireOrganizationSelection.php
│   │   └── Requests/
│   │       ├── AuthenticateRequest.php
│   │       ├── GetUserRequest.php
│   │       ├── InstallScriptRequest.php
│   │       └── JwksRequest.php
│   ├── Models/
│   │   └── Client.php
│   ├── Services/
│   │   └── ClientService.php
│   ├── Support/
│   │   ├── AuthenticationResponse.php
│   │   └── UserResponse.php
│   ├── Homework.php
│   └── HomeworkServiceProvider.php
├── routes/
│   └── workos.php
├── resources/
│   └── views/
│       └── auth/
│           ├── authorize.blade.php
│           └── select-organization.blade.php
├── tests/
│   ├── Feature/
│   │   ├── MiddlewareTest.php
│   │   ├── TokenClaimsProviderTest.php
│   │   └── UserManagementTest.php
│   ├── Unit/
│   │   └── ServiceProviderTest.php
│   ├── Pest.php
│   └── TestCase.php
├── CLAUDE.md
├── composer.json
├── phpunit.xml
├── pint.json
└── README.md
```

### Key Components

- **Homework**: Central configuration class providing static helper methods for binding contracts: `useOrganizationProvider()`, `useUserResponse()`, `useAuthenticationResponse()`, `useTokenClaimsProvider()`, and configuring headless views with `organizationSelectionView()` (similar to Passport)
- **HomeworkServiceProvider**: Registers routes, loads views, and binds contracts
- **ClientService**: Static service for easily creating WorkOS-compatible OAuth clients
- **CreateWorkOsClientCommand**: Artisan command for creating OAuth clients with auto-install script output
- **AuthenticateRequest**: Handles OAuth authentication for both authorization code and refresh token flows
- **GetUserRequest**: Handles user retrieval by ID with proper authentication
- **InstallScriptRequest**: Generates bash installation script for automatic client configuration
- **JwksRequest**: Provides JWKS endpoint for JWT token verification using RSA keys (RS256)
- **UserResponseContract**: Interface for customizing user response transformation
- **UserResponse**: Default implementation with WorkOS User integration and avatar support
- **AuthenticationResponseContract**: Interface for customizing complete authentication response
- **AuthenticationResponse**: Default implementation handling top-level organization_id
- **TokenClaimsProviderContract**: Interface for providing custom token claims (organizations, permissions, roles, metadata)
- **OrganizationProviderContract**: Interface for providing user organizations
- **RequireOrganizationSelection**: Middleware for multi-organization selection flow
- **Client Model**: Custom Passport Client model with first-party auto-approval (optional)
- **SkipsAuthorizationForFirstPartyClients Trait**: Reusable trait for adding auto-approval to any Client model (optional)
- **AutoApproveFirstPartyClients Middleware**: Alternative middleware approach for auto-approval (optional)
- **Authorization View**: Modern, dark-mode-enabled authorization screen (optional)
- **Organization Selection View**: Dark-mode organization selection screen with headless support (optional)

## Testing

You can test the OAuth flow using curl:

```bash
# Test authorization endpoint
curl -I "http://your-oauth-server.test/oauth/authorize?client_id=your_client_id&redirect_uri=http://your-app.test/authenticate&state=test_state"

# Test token exchange
curl -X POST "http://your-oauth-server.test/user_management/authenticate" \
  -H "Content-Type: application/json" \
  -d '{
    "grant_type": "authorization_code",
    "client_id": "your_client_id",
    "code": "authorization_code_here"
  }'
```

## Migration from WorkOS

To migrate from WorkOS to this self-hosted solution:

1. Install this package on your OAuth server
2. Create OAuth clients matching your WorkOS application IDs
3. Update your client application's `WORKOS_BASE_URL` to point to your OAuth server
4. Your existing Laravel WorkOS integration will now use your local OAuth server

## Benefits

- **Complete Control**: All authentication happens in your application
- **No External Dependencies**: No need for WorkOS account or API calls
- **Development Friendly**: Easy to test and develop locally
- **Cost Effective**: No WorkOS subscription required
- **Data Privacy**: User data stays in your own database
- **Customizable**: Full control over authentication flow and UI

## License

MIT

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## Support

For issues and questions, please use the GitHub issue tracker.

## Credits

This package was created to provide a self-hosted alternative to WorkOS authentication while maintaining full compatibility with the Laravel WorkOS SDK.
