# inmanturbo/homework

[![Tests](https://github.com/inmanturbo/homework/workflows/Tests/badge.svg)](https://github.com/inmanturbo/homework/actions)
[![Fix PHP code style issues](https://github.com/inmanturbo/homework/workflows/Fix%20PHP%20code%20style%20issues/badge.svg)](https://github.com/inmanturbo/homework/actions)
[![Latest Stable Version](https://poser.pugx.org/inmanturbo/homework/v/stable)](https://packagist.org/packages/inmanturbo/homework)
[![Total Downloads](https://poser.pugx.org/inmanturbo/homework/downloads)](https://packagist.org/packages/inmanturbo/homework)
[![License](https://poser.pugx.org/inmanturbo/homework/license)](https://packagist.org/packages/inmanturbo/homework)

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

Create a first-party OAuth client (will auto-approve):

```bash
php artisan passport:client --public
```

Note: First-party clients (those without a `user_id`) will automatically bypass the authorization screen.

### Client Application Configuration

In your client Laravel application using the WorkOS SDK, configure it to use your OAuth server:

```php
// In AppServiceProvider or similar
use Laravel\WorkOS\WorkOS;

public function boot()
{
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

### First-Party Client Auto-Approval

The package includes the `AutoApproveFirstPartyClients` middleware that automatically approves authorization requests for first-party clients. This means:

- Users are not shown an authorization screen for your own applications
- The OAuth flow completes transparently
- Third-party clients still see the authorization prompt

### Preserving Intended URLs

The package preserves Laravel's intended URL functionality. When users are redirected to login from a protected route, they'll be returned to their originally requested page after authentication:

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
│       ├── Controllers/
│       │   └── UserManagementController.php
│       └── Middleware/
│           └── AutoApproveFirstPartyClients.php
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
- **UserManagementController**: Implements WorkOS UserManagement API endpoints
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
- Laravel Passport ^12.0
- Laravel WorkOS ^0.1.0

## License

MIT

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## Support

For issues and questions, please use the GitHub issue tracker.

## Credits

This package was created to provide a self-hosted alternative to WorkOS authentication while maintaining full compatibility with the Laravel WorkOS SDK.