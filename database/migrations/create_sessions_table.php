<?php

/**
 * Migration: create sessions table for SwiftAuth.
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

        Schema::create($prefix . 'Sessions', function (Blueprint $table) {
            $table->id('id_session');
            $table->unsignedBigInteger('id_user');
            $table->string('session_id')->unique();
            $table->string('ip_address')->nullable();
            $table->string('user_agent', 1024)->nullable();
            $table->string('device_name', 255)->nullable();
            $table->string('platform', 255)->nullable();
            $table->string('browser', 255)->nullable();
            $table->timestamp('last_activity');
            $table->timestamps();

            // Performance indexes
            $table->index('id_user');
            $table->index(['id_user', 'last_activity']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $prefix = (string) config('swift-auth.table_prefix', 'swift-auth_');

        Schema::dropIfExists($prefix . 'Sessions');
    }
};
