<?php

/**
 * Migration: create remember tokens table for SwiftAuth.
 *
 * PHP 8.2+
 *
 * @package Equidna\SwiftAuth\Database\Migrations
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
        $prefix = (string) config('swift-auth.table_prefix', 'swift-auth_');

        Schema::create($prefix . 'RememberTokens', function (Blueprint $table) {
            $table->id('id_remember_token');
            $table->unsignedBigInteger('id_user');
            $table->string('selector')->unique();
            $table->string('hashed_token');
            $table->timestamp('expires_at');
            $table->timestamp('last_used_at')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('user_agent', 1024)->nullable();
            $table->string('device_name', 255)->nullable();
            $table->string('platform', 255)->nullable();
            $table->string('browser', 255)->nullable();
            $table->timestamps();

            // Performance indexes
            $table->index('id_user');
            $table->index('hashed_token');
            $table->index('expires_at');
            $table->index(['id_user', 'expires_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $prefix = (string) config('swift-auth.table_prefix', 'swift-auth_');

        Schema::dropIfExists($prefix . 'RememberTokens');
    }
};
