<?php

/**
 * Migration: create users table for SwiftAuth.
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
        $prefix = config('swift-auth.table_prefix', '');

        Schema::create($prefix . 'Users', function (Blueprint $table) {
            $table->id('id_user');
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();

            // Performance indexes
            $table->index('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $prefix = config('swift-auth.table_prefix', '');
        Schema::dropIfExists($prefix . 'Users');
    }
};
