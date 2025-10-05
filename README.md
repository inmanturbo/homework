# inmanturbo/homework

[![Tests](https://github.com/inmanturbo/homework/workflows/Tests/badge.svg)](https://github.com/inmanturbo/homework/actions)
[![Check PHP code style](https://github.com/inmanturbo/homework/workflows/Check%20PHP%20code%20style/badge.svg)](https://github.com/inmanturbo/homework/actions)

A Laravel Passport extension that provides WorkOS UserManagement API compatibility, allowing you to use Laravel Passport as a drop-in replacement for WorkOS authentication services.

## Overview

This package extends Laravel Passport to provide WorkOS-compatible OAuth endpoints, enabling you to self-host your authentication service while maintaining full compatibility with the Laravel WorkOS SDK client applications.

## Features

- **WorkOS UserManagement API Compatibility**: Implements WorkOS UserManagement authentication endpoints
- **OAuth 2.0 Authorization Code Flow**: Full implementation using Laravel Passport
- **Optional Auto-Approval**: Choose between standard OAuth authorization flow or automatic approval for first-party clients
- **JWT Token Support**: Compatible with WorkOS token format
- **Session Management**: Preserves intended URLs through OAuth flow
- **Drop-in Replacement**: Works seamlessly with existing Laravel WorkOS integrations
- **Flexible Integration**: Multiple approaches for customizing the authorization flow

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

This will output ready-to-copy environment variables:

```
✅ WorkOS client created successfully!

  Client ID ............................. 0199b362-5239-723c-bbd7-1c61e492699a
  Client Secret ..................... XrObuSKCZ3HwkcAV6DWAu00K2CAnLhGjklhSk8IU

📋 Copy these environment variables to your client application:

WORKOS_CLIENT_ID=0199b362-5239-723c-bbd7-1c61e492699a
WORKOS_API_KEY=XrObuSKCZ3HwkcAV6DWAu00K2CAnLhGjklhSk8IU
WORKOS_REDIRECT_URL=http://your-app.test/authenticate
WORKOS_BASE_URL=http://your-oauth-server.test/
```

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

```php
use App\Services\CustomUserResponse;
use Inmanturbo\Homework\Contracts\UserResponseContract;

public function register()
{
    $this->app->bind(UserResponseContract::class, CustomUserResponse::class);
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
│   ├── HomeworkServiceProvider.php
│   └── Http/
│       ├── Middleware/
│       │   └── AutoApproveFirstPartyClients.php
│       └── Requests/
│           ├── AuthenticateRequest.php
│           ├── GetUserRequest.php
│           └── JwksRequest.php
├── routes/
│   └── workos.php
├── resources/
│   └── views/
│       └── auth/
│           └── authorize.blade.php
├── tests/
│   ├── Feature/
│   │   ├── MiddlewareTest.php
│   │   └── UserManagementTest.php
│   ├── Unit/
│   │   └── ServiceProviderTest.php
│   ├── Pest.php
│   └── TestCase.php
├── composer.json
├── phpunit.xml
├── pint.json
└── README.md
```

### Key Components

- **HomeworkServiceProvider**: Registers routes and loads views
- **ClientService**: Static service for easily creating WorkOS-compatible OAuth clients
- **AuthenticateRequest**: Handles OAuth authentication for both authorization code and refresh token flows
- **GetUserRequest**: Handles user retrieval by ID with proper authentication
- **JwksRequest**: Provides JWKS endpoint for JWT token verification using RSA keys
- **UserResponseContract**: Interface for customizing user response transformation
- **UserResponse**: Default implementation with avatar support and extensible design
- **Client Model**: Custom Passport Client model with first-party auto-approval (optional)
- **SkipsAuthorizationForFirstPartyClients Trait**: Reusable trait for adding auto-approval to any Client model (optional)
- **AutoApproveFirstPartyClients Middleware**: Alternative middleware approach for auto-approval (optional)
- **Authorization View**: Modern, dark-mode-enabled authorization screen (optional)

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

## Requirements

- PHP ^8.2
- Laravel ^11.0 or ^12.0
- Laravel Passport ^13.0
- Laravel WorkOS ^0.1.0

## License

MIT

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## Support

For issues and questions, please use the GitHub issue tracker.

## Credits

This package was created to provide a self-hosted alternative to WorkOS authentication while maintaining full compatibility with the Laravel WorkOS SDK.
