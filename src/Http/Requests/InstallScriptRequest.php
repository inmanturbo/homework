<?php

namespace Inmanturbo\Homework\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InstallScriptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'secret' => 'required|string',
            'redirect_uri' => 'required|url',
        ];
    }

    public function generateScript(): string
    {
        $clientId = $this->route('clientId');
        $secret = $this->input('secret');
        $redirectUri = $this->input('redirect_uri');
        $baseUrl = config('app.url');

        if (! str_ends_with($baseUrl, '/')) {
            $baseUrl .= '/';
        }

        $script = <<<'BASH'
#!/bin/bash
set -e

echo "🚀 Installing WorkOS client configuration..."

# Colors for output
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

CLIENT_ID="{{CLIENT_ID}}"
SECRET="{{SECRET}}"
REDIRECT_URI="{{REDIRECT_URI}}"
BASE_URL="{{BASE_URL}}"

# Check if we're in a Laravel project
if [ ! -f "artisan" ]; then
    echo "❌ Error: This doesn't appear to be a Laravel project (artisan file not found)"
    exit 1
fi

echo -e "${BLUE}📝 Updating .env file...${NC}"

# Create .env if it doesn't exist
if [ ! -f ".env" ]; then
    echo -e "${YELLOW}Creating .env file...${NC}"
    touch .env
fi

# Function to add or update env var
update_env() {
    local key=$1
    local value=$2

    if grep -q "^${key}=" .env; then
        # Update existing
        if [[ "$OSTYPE" == "darwin"* ]]; then
            sed -i '' "s|^${key}=.*|${key}=${value}|" .env
        else
            sed -i "s|^${key}=.*|${key}=${value}|" .env
        fi
        echo -e "${GREEN}  ✓ Updated ${key}${NC}"
    else
        # Append new
        echo "${key}=${value}" >> .env
        echo -e "${GREEN}  ✓ Added ${key}${NC}"
    fi
}

update_env "WORKOS_CLIENT_ID" "$CLIENT_ID"
update_env "WORKOS_API_KEY" "$SECRET"
update_env "WORKOS_REDIRECT_URL" "$REDIRECT_URI"
update_env "WORKOS_BASE_URL" "$BASE_URL"

echo -e "${BLUE}📝 Updating config/services.php...${NC}"

# Add base_url to workos config if not present
if [ -f "config/services.php" ]; then
    if ! grep -q "'base_url' => env('WORKOS_BASE_URL')" config/services.php; then
        # Use PHP to safely add base_url to the workos config array after redirect_url
        php -r "
            \$file = 'config/services.php';
            \$content = file_get_contents(\$file);

            // Add base_url after redirect_url line
            \$pattern = \"/('redirect_url'\\s*=>\\s*env\\('WORKOS_REDIRECT_URL'\\),)/\";
            \$replacement = \"\\\$1\\n        'base_url' => env('WORKOS_BASE_URL'),\";

            \$content = preg_replace(\$pattern, \$replacement, \$content);
            file_put_contents(\$file, \$content);
        "
        echo -e "${GREEN}  ✓ Added base_url to workos config${NC}"
    else
        echo -e "${GREEN}  ✓ base_url already exists in workos config${NC}"
    fi
else
    echo -e "${YELLOW}  ⚠ config/services.php not found, skipping${NC}"
fi

echo -e "${BLUE}📝 Creating WorkOsServiceProvider...${NC}"

# Create the service provider
mkdir -p app/Providers

cat > app/Providers/WorkOsServiceProvider.php << 'EOF'
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use WorkOS\WorkOS;

class WorkOsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        WorkOS::setApiKey(config('services.workos.secret'));

        $baseUrl = config('services.workos.base_url');
        if ($baseUrl && $baseUrl !== 'https://api.workos.com/') {
            WorkOS::setApiBaseUrl($baseUrl);
        }
    }
}
EOF

echo -e "${GREEN}  ✓ Created app/Providers/WorkOsServiceProvider.php${NC}"

echo -e "${BLUE}📝 Registering service provider...${NC}"

# Register in bootstrap/providers.php (Laravel 11+) or config/app.php (Laravel 10)
if [ -f "bootstrap/providers.php" ]; then
    # Laravel 11+
    if ! grep -q "WorkOsServiceProvider::class" bootstrap/providers.php; then
        # Use PHP to safely add the provider
        php -r "
            \$file = 'bootstrap/providers.php';
            \$content = file_get_contents(\$file);
            \$content = str_replace(
                '];',
                '    App\\\\Providers\\\\WorkOsServiceProvider::class,' . PHP_EOL . '];',
                \$content
            );
            file_put_contents(\$file, \$content);
        "
        echo -e "${GREEN}  ✓ Added to bootstrap/providers.php${NC}"
    else
        echo -e "${GREEN}  ✓ Already registered in bootstrap/providers.php${NC}"
    fi
elif [ -f "config/app.php" ]; then
    # Laravel 10
    if ! grep -q "WorkOsServiceProvider::class" config/app.php; then
        # Use PHP to safely add the provider
        php -r "
            \$file = 'config/app.php';
            \$content = file_get_contents(\$file);
            \$pattern = '/(App\\\\\\\\Providers\\\\\\\\RouteServiceProvider::class,)/';
            \$replacement = \"\\\$1\\n        App\\\\\\\\Providers\\\\\\\\WorkOsServiceProvider::class,\";
            \$content = preg_replace(\$pattern, \$replacement, \$content);
            file_put_contents(\$file, \$content);
        "
        echo -e "${GREEN}  ✓ Added to config/app.php${NC}"
    else
        echo -e "${GREEN}  ✓ Already registered in config/app.php${NC}"
    fi
else
    echo -e "${YELLOW}  ⚠ Could not find bootstrap/providers.php or config/app.php${NC}"
    echo -e "${YELLOW}    Please manually register App\\Providers\\WorkOsServiceProvider::class${NC}"
fi

echo ""
echo -e "${GREEN}✅ WorkOS client installation complete!${NC}"
echo ""
echo -e "${BLUE}Configuration:${NC}"
echo -e "  Client ID: ${CLIENT_ID}"
echo -e "  Redirect URI: ${REDIRECT_URI}"
echo -e "  Base URL: ${BASE_URL}"
echo ""
echo -e "${YELLOW}Next steps:${NC}"
echo -e "  1. Clear config cache: ${GREEN}php artisan config:clear${NC}"
echo -e "  2. Test authentication: Visit your app and try logging in"
echo ""
BASH;

        $script = str_replace('{{CLIENT_ID}}', $clientId, $script);
        $script = str_replace('{{SECRET}}', $secret, $script);
        $script = str_replace('{{REDIRECT_URI}}', $redirectUri, $script);
        $script = str_replace('{{BASE_URL}}', $baseUrl, $script);

        return $script;
    }
}
