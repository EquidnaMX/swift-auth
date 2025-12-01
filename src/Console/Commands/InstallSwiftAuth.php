<?php

/**
 * Artisan command to install SwiftAuth into a Laravel application.
 *
 * PHP 8.2+
 *
 * @package   Equidna\SwiftAuth\Console\Commands
 * @author    Gabriel Ruelas <gruelas@gruelas.com>
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/EquidnaMX/swift_auth Package repository
 */

namespace Equidna\SwiftAuth\Console\Commands;
use Illuminate\Console\Command;

/**
 * Installs SwiftAuth by publishing configuration, views, migrations, icons, and models.
 *
 * Allows developers to choose the desired frontend stack (Blade, React+TS, React+JS).
 */
class InstallSwiftAuth extends Command
{
    /**
     * The name and signature of the console command.
     *
     */
    protected $signature = 'swift-auth:install';

    /**
     * The console command description.
     */
    protected $description = 'Install SwiftAuth: configure, migrate and publish files';

    /**
     * Executes the console command.
     *
     * @return void
     */
    public function handle(): void
    {
        $this->info('Starting SwiftAuth installation...');

        $this->call('vendor:publish', [
            '--provider' => 'Equidna\\SwiftAuth\\Providers\\SwiftAuthServiceProvider',
            '--tag' => 'swift-auth:config'
        ]);

        $choice = $this->choice(
            'Which frontend do you want to use?',
            [
                'React + TypeScript',
                'React + JavaScript',
                'Blade',
            ],
            0
        );

        if ($choice === 'Blade') {
            $this->installBlade();
        } elseif ($choice === 'React + TypeScript') {
            $this->installTypeScript();
        } else {
            $this->installJavaScript();
        }

        $this->info('Importing migrations...');

        $this->call('vendor:publish', [
            '--provider' => 'Equidna\\SwiftAuth\\Providers\\SwiftAuthServiceProvider',
            '--tag' => 'swift-auth:migrations'
        ]);

        $this->call('migrate');

        $this->info('No automatic admin seeded. To create an administrator run:');
        $this->info('  php artisan swift-auth:create-admin "Admin Name" "admin@example.com"');
        $this->info('Set `SWIFT_ADMIN_NAME` and `SWIFT_ADMIN_EMAIL` in the environment for non-interactive creation.');

        $this->info('Importing icons...');
        $this->call('vendor:publish', [
            '--provider' => 'Equidna\\SwiftAuth\\Providers\\SwiftAuthServiceProvider',
            '--tag' => 'swift-auth:icons'
        ]);

        $this->info('Importing models...');
        $this->call('vendor:publish', [
            '--provider' => 'Equidna\\SwiftAuth\\Providers\\SwiftAuthServiceProvider',
            '--tag' => 'swift-auth:models'
        ]);

        $this->info('Installation completed.');
    }

    /**
     * Publishes Blade views.
     *
     * @return void
     */
    protected function installBlade(): void
    {
        $this->info('Installing Blade views...');
        $this->call('vendor:publish', [
            '--provider' => 'Equidna\\SwiftAuth\\Providers\\SwiftAuthServiceProvider',
            '--tag' => 'swift-auth:views'
        ]);
    }

    /**
     * Publishes React views with JavaScript.
     *
     * Displays a reminder to run npm commands.
     *
     * @return void
     */
    protected function installJavaScript(): void
    {
        $this->info('Installing React views with JavaScript...');
        $this->call('vendor:publish', [
            '--provider' => 'Equidna\\SwiftAuth\\Providers\\SwiftAuthServiceProvider',
            '--tag' => 'swift-auth:js-react'
        ]);

        $this->warn('Remember to run: npm install && npm run dev');
    }

    /**
     * Publishes React views with TypeScript.
     *
     * Displays a reminder to run npm commands.
     *
     * @return void
     */
    protected function installTypeScript(): void
    {
        $this->info('Installing React views with TypeScript...');
        $this->call('vendor:publish', [
            '--provider' => 'Equidna\\SwiftAuth\\Providers\\SwiftAuthServiceProvider',
            '--tag' => 'swift-auth:ts-react'
        ]);

        $this->warn('Remember to run: npm install && npm run dev');
    }
}
