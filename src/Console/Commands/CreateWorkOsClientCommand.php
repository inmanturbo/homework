<?php

namespace Inmanturbo\Homework\Console\Commands;

use Illuminate\Console\Command;
use Inmanturbo\Homework\Services\ClientService;

class CreateWorkOsClientCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'homework:create-client
                            {redirect-uri : The redirect URI for the client}
                            {--name= : The name of the client}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a first-party WorkOS-compatible OAuth client';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $redirectUri = $this->argument('redirect-uri');
        $name = $this->option('name') ?? 'WorkOS Client';

        $config = ClientService::createFirstPartyWorkOsClient(
            redirectUris: $redirectUri,
            name: $name
        );

        $this->info('✅ WorkOS client created successfully!');
        $this->newLine();

        $this->components->twoColumnDetail('Client ID', $config['client_id']);
        $this->components->twoColumnDetail('Client Secret', $config['client_secret']);
        $this->newLine();

        $this->info('📋 Copy these environment variables to your client application:');
        $this->newLine();

        foreach ($config['env_config'] as $key => $value) {
            $this->line("{$key}={$value}");
        }

        $this->newLine();

        return self::SUCCESS;
    }
}
