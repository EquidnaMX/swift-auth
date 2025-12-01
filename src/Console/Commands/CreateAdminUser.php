<?php

/**
 * Artisan command to create an administrator user for SwiftAuth.
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
use Illuminate\Support\Facades\Hash;
use Equidna\SwiftAuth\Models\Role;
use Equidna\SwiftAuth\Models\User;

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
     */
    protected $signature = 'swift-auth:create-admin'
        . ' {name? : Admin name}'
        . ' {email? : Admin email}';
    /**
     * The console command description.
     *
     */
    protected $description = 'Create an administrator user using .env values or user-provided data';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(): void
    {

        // Require name and email to be provided via CLI arguments or environment.
        $userName = $this->argument('name') ?? env('SWIFT_ADMIN_NAME');
        $email = $this->argument('email') ?? env('SWIFT_ADMIN_EMAIL');

        if (empty($userName) || empty($email)) {
            $this->info('Aborting: provide admin name and email via command arguments.');
            $this->info('Or set SWIFT_ADMIN_NAME and SWIFT_ADMIN_EMAIL in the environment.');
            return;
        }

        if (!$this->confirm("Do you want to create the admin user '{$userName}' with email '{$email}'?", true)) {
            $this->info('Operation cancelled.');
            return;
        }

        try {
            $randomPassword = bin2hex(random_bytes(16));
        } catch (\Exception $e) {
            $randomPassword = bin2hex(openssl_random_pseudo_bytes(16));
        }

        $this->createAdminUser($userName, $email, $randomPassword, false);
        $this->info('Admin user created. Request a password reset for the new account to set a secure password.');
    }

    private function createAdminUser(
        string $userName,
        string $email,
        string $textPassword,
        bool $verifyEmail = true
    ): void {
        $driver = config('swift-auth.hash_driver');
        $hashed = $driver ? Hash::driver($driver)->make($textPassword) : Hash::make($textPassword);

        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => $userName,
                'password' => $hashed,
                'email_verified_at' => $verifyEmail ? now() : null,
            ]
        );

        $role = Role::firstOrCreate(
            ['name' => 'root'],
            [
                'description' => 'System administrator',
                'actions' => ['sw-admin'], // Now stored as JSON array
            ]
        );

        $user->roles()->syncWithoutDetaching([$role->id_role]);

        logger()->info('Admin user created via CLI', [
            'user_id' => $user->getKey(),
            'email' => $user->email,
            'role' => 'root',
        ]);
    }
}
