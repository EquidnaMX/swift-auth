<?php

namespace Teleurban\SwiftAuth\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Teleurban\SwiftAuth\Models\Role;
use Teleurban\SwiftAuth\Models\User;

/**
 * Class InstallSwiftAuth
 *
 * This command stores a record for the admin user from the env file data or user given data
 */
class CreateAdminUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'swift-auth:create-admin {--default} {name} {email} {password}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Crea un usuario administrador con los datos definidos en el archivo .env o por los datos proporcionados por el usuario';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(): void
    {
        if ($this->option('default')) {
            $userName = Config::get('swift-auth.admin_user.name');
            $email = Config::get('swift-auth.admin_user.email');
            $password = Config::get('swift-auth.admin_user.password');

            $this->createAdminUser($userName, $email, $password);
            return;
        }

        $userName = $this->argument('name') ?? $this->ask('Nombre del usuario administrador');
        $email = $this->argument('email') ?? $this->ask('Correo electrónico del usuario');
        $password = $this->argument('password') ?? $this->secret('Contraseña del usuario');

        if (!$this->confirm("¿Deseas crear el usuario administrador '{$userName}' con el correo '{$email}'?", true)) {
            $this->info('Operación cancelada.');
            return;
        }

        $this->createAdminUser($userName, $email, $password);

        return;
    }

    private function createAdminUser(string $userName, string $email, string $textPassword): void
    {
        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => $userName,
                'password' => Hash::make($textPassword),
                'email_verified_at' => now(),
            ]
        );

        $role = Role::firstOrCreate(
            ['name' => 'root'],
            [
                'description' => 'Admin del sistema',
                'actions' => 'sw-admin',
            ]
        );

        $user->roles()->syncWithoutDetaching([$role->id_role]);
    }
}
