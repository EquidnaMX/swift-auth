<?php

/**
 * Create UserTokens table for API authentication.
 *
 * This migration creates the table for storing API tokens with abilities/scopes,
 * expiration, and usage tracking. Uses SwiftAuth table prefix from configuration.
 *
 * PHP 8.2+
 *
 * @package   Equidna\SwiftAuth\Database\Migrations
 * @author    Gabriel Ruelas <gruelas@gruelas.com>
 * @license   https://opensource.org/licenses/MIT MIT License
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Runs the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        $prefix = (string) config('swift-auth.table_prefix', '');
        $usersTable = $prefix . 'Users';
        $tokensTable = $prefix . 'UserTokens';

        Schema::create(
            $tokensTable,
            function (Blueprint $table) use ($usersTable): void {
                $table->id('id_user_token');
                $table->unsignedBigInteger('id_user');
                $table->string('name');
                $table->string('hashed_token', 64)->unique();
                $table->json('abilities')->nullable();
                $table->timestamp('last_used_at')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->timestamps();

                $table->foreign('id_user')
                    ->references('id_user')
                    ->on($usersTable)
                    ->onDelete('cascade');

                $table->index('id_user');
                $table->index('hashed_token');
                $table->index('expires_at');
            }
        );
    }

    /**
     * Reverts the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        $prefix = (string) config('swift-auth.table_prefix', '');
        Schema::dropIfExists($prefix . 'UserTokens');
    }
};
