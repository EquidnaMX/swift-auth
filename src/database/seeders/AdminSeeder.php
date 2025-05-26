<?php

namespace Teleurban\SwiftAuth\database\seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Teleurban\SwiftAuth\Models\User;
use Teleurban\SwiftAuth\Models\Role;
use Illuminate\Support\Facades\Config;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::firstOrCreate(
            ['email' => Config::get('swift-auth.admin_user.email')],
            [
                'name' => Config::get('swift-auth.admin_user.name'),
                'password' => Hash::make(Config::get('swift-auth.admin_user.password')),
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
