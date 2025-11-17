<?php

/**
 * Migration: create users table for SwiftAuth.
 *
 * PHP 8.1+
 *
 * @package Equidna\SwifthAuth\Database\Migrations
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $prefix = config('swift-auth.table_prefix', '');

        Schema::create($prefix . 'Users', function (Blueprint $table) {
            $table->id('id_user');
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });
        Schema::create($prefix . 'PasswordResetTokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $prefix = config('swift-auth.table_prefix', '');
        Schema::dropIfExists($prefix . 'Users');
        Schema::dropIfExists($prefix . 'PasswordResetTokens');
    }
};
