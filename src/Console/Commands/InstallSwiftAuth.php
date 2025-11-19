<?php

namespace Equidna\SwifthAuth\Console\Commands;

use Illuminate\Console\Command;

/**
 * Class InstallSwiftAuth
 *
 * This command installs SwiftAuth by publishing configuration files,
 * views, migrations, icons, and models. It also allows the developer
 * to choose the desired frontend stack.
 */
class InstallSwiftAuth extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'swift-auth:install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Instala SwiftAuth: configura, migra y publica archivos';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(): void
    {
        $this->info('Iniciando instalación de SwiftAuth...');

        $this->call('vendor:publish', [
            '--provider' => 'Equidna\SwifthAuth\Providers\SwiftAuthServiceProvider',
            '--tag' => 'swift-auth:config'
        ]);

        $choice = $this->choice(
            '¿Qué frontend deseas utilizar?',
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

        $this->info('Importando migraciones...');

        $this->call('vendor:publish', [
            '--provider' => 'Equidna\SwifthAuth\Providers\SwiftAuthServiceProvider',
            '--tag' => 'swift-auth:migrations'
        ]);

        $this->call('migrate');

        $this->info('No automatic admin seeded. To create an administrator run:');
        $this->info('  php artisan swift-auth:create-admin "Admin Name" "admin@example.com"');
        $this->info('Set `SWIFT_ADMIN_NAME` and `SWIFT_ADMIN_EMAIL` in the environment for non-interactive creation.');

        $this->info('Importando iconos...');
        $this->call('vendor:publish', [
            '--provider' => 'Equidna\SwifthAuth\Providers\SwiftAuthServiceProvider',
            '--tag' => 'swift-auth:icons'
        ]);

        $this->info('Importando modelos...');
        $this->call('vendor:publish', [
            '--provider' => 'Equidna\SwifthAuth\Providers\SwiftAuthServiceProvider',
            '--tag' => 'swift-auth:models'
        ]);

        $this->info('Instalación completada.');
    }

    /**
     * Publish Blade views.
     *
     * @return void
     */
    protected function installBlade(): void
    {
        $this->info('Instalando vistas Blade...');
        $this->call('vendor:publish', [
            '--provider' => 'Equidna\SwifthAuth\Providers\SwiftAuthServiceProvider',
            '--tag' => 'swift-auth:views'
        ]);
    }

    /**
     * Publish React views with JavaScript.
     * Displays a reminder to run npm commands.
     *
     * @return void
     */
    protected function installJavaScript(): void
    {
        $this->info('Instalando vistas en React con JavaScript...');
        $this->call('vendor:publish', [
            '--provider' => 'Equidna\SwifthAuth\Providers\SwiftAuthServiceProvider',
            '--tag' => 'swift-auth:js-react'
        ]);

        $this->warn('Recuerda ejecutar: npm install && npm run dev');
    }

    /**
     * Publish React views with TypeScript.
     * Displays a reminder to run npm commands.
     *
     * @return void
     */
    protected function installTypeScript(): void
    {
        $this->info('Instalando vistas en React con TypeScript...');
        $this->call('vendor:publish', [
            '--provider' => 'Equidna\SwifthAuth\Providers\SwiftAuthServiceProvider',
            '--tag' => 'swift-auth:ts-react'
        ]);

        $this->warn('Recuerda ejecutar: npm install && npm run dev');
    }
}
