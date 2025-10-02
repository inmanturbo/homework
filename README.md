# inmanturbo/homework

[![Tests](https://github.com/inmanturbo/homework/workflows/Tests/badge.svg)](https://github.com/inmanturbo/homework/actions)
[![Check PHP code style](https://github.com/inmanturbo/homework/workflows/Check%20PHP%20code%20style/badge.svg)](https://github.com/inmanturbo/homework/actions)

A Laravel Passport extension that provides WorkOS UserManagement API compatibility, allowing you to use Laravel Passport as a drop-in replacement for WorkOS authentication services.

## Overview

This package extends Laravel Passport to provide WorkOS-compatible OAuth endpoints, enabling you to self-host your authentication service while maintaining full compatibility with the Laravel WorkOS SDK client applications.

## Features

- **WorkOS UserManagement API Compatibility**: Implements WorkOS UserManagement authentication endpoints
- **OAuth 2.0 Authorization Code Flow**: Full implementation using Laravel Passport
- **First-Party Client Auto-Approval**: Automatic authorization for first-party OAuth clients via middleware
- **JWT Token Support**: Compatible with WorkOS token format
- **Session Management**: Preserves intended URLs through OAuth flow
- **Drop-in Replacement**: Works seamlessly with existing Laravel WorkOS integrations

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

### Step 3: Configure Your Application

Add the following to your `.env` file:

```env
# WorkOS Configuration (pointed to your local OAuth server)
WORKOS_CLIENT_ID=your_client_id
WORKOS_API_KEY=your_client_secret
WORKOS_BASE_URL=http://your-oauth-server.test
```

### Step 4: Update Your AuthServiceProvider

Ensure Passport routes are registered in `app/Providers/AuthServiceProvider.php`:

```php
use Laravel\Passport\Passport;

public function boot()
{
    Passport::routes();
}
```

## Usage

### Setting Up OAuth Clients

#### Creating OAuth Clients

You can create OAuth clients using Laravel Passport's Artisan commands:

**Option 1: Public Client (No Client Secret)**

Public clients are suitable for SPAs and mobile apps that cannot securely store secrets:

```bash
php artisan passport:client --public --name="My Client App"
```

When prompted, enter your redirect URI (e.g., `http://your-app.test/authenticate`).

**Option 2: Confidential Client (With Client Secret)**

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

**Important**: First-party clients (those without a `user_id`) will automatically bypass the authorization screen, providing a seamless experience for your own applications.

### Client Application Configuration

#### Option 1: Configuration-Based Approach (Recommended)

The cleanest way is to use Laravel's configuration system. Add the WorkOS configuration to your `config/services.php`:

```php
// In config/services.php
'workos' => [
    'client_id' => env('WORKOS_CLIENT_ID'),
    'secret' => env('WORKOS_API_KEY'),
    'redirect_url' => env('WORKOS_REDIRECT_URL'),
    'base_url' => env('WORKOS_BASE_URL', 'https://api.workos.com/'), // Add this line
],
```

Then in your `.env` file:

```env
WORKOS_CLIENT_ID=your_client_id
WORKOS_API_KEY=your_client_secret
WORKOS_REDIRECT_URL=http://your-app.test/authenticate
WORKOS_BASE_URL=http://your-oauth-server.test/
```

And configure the WorkOS SDK in your `AppServiceProvider`:

```php
// In app/Providers/AppServiceProvider.php
use WorkOS\WorkOS;

public function boot()
{
    // Set the API key (client secret)
    WorkOS::setApiKey(config('services.workos.secret'));

    // Set the base URL to point to your OAuth server
    $baseUrl = config('services.workos.base_url');
    if ($baseUrl && $baseUrl !== 'https://api.workos.com/') {
        WorkOS::setApiBaseUrl($baseUrl);
    }
}
```

#### Option 2: Direct Configuration

Alternatively, you can set it directly based on environment:

```php
// In app/Providers/AppServiceProvider.php
use WorkOS\WorkOS;

public function boot()
{
    // Always set the API key
    WorkOS::setApiKey(env('WORKOS_API_KEY'));

    // Set base URL based on environment
    if (app()->environment('local')) {
        WorkOS::setApiBaseUrl('http://workos-passport.test/');
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

### First-Party Client Auto-Approval

The package includes the `AutoApproveFirstPartyClients` middleware that automatically approves authorization requests for first-party clients. This means:

- Users are not shown an authorization screen for your own applications
- The OAuth flow completes transparently
- Third-party clients still see the authorization prompt

### Preserving Intended URLs

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
│           ├── authorize.blade.php
│           └── login.blade.php
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

- **HomeworkServiceProvider**: Registers routes, views, and middleware
- **AuthenticateRequest**: Handles OAuth authentication for both authorization code and refresh token flows
- **GetUserRequest**: Handles user retrieval by ID with proper authentication
- **JwksRequest**: Provides JWKS endpoint for JWT token verification
- **AutoApproveFirstPartyClients**: Middleware for automatic first-party client approval
- **Custom Views**: OAuth authorization and login views with Tailwind CSS styling

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
