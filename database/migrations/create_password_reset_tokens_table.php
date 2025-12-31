<?php

/**
 * Migration: create password reset tokens table for SwiftAuth.
 *
 * PHP 8.1+
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
        $tableName = $prefix . 'PasswordResetTokens';

        if (Schema::hasTable($tableName)) {
            return;
        }

        Schema::create($tableName, function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();

            // Performance indexes
            $table->index('token');
            $table->index(['email', 'token']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $prefix = (string) config('swift-auth.table_prefix', 'swift-auth_');
        Schema::dropIfExists($prefix . 'PasswordResetTokens');
    }
};
